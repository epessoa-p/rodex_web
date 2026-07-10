<?php

namespace App\Http\Controllers\CashRegister;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CashSessionController extends Controller
{
    public function open()
    {
        $validated = request()->validate([
            'cash_register_id' => 'required|exists:cash_registers,id',
            'opening_amount'   => 'required|numeric|min:0',
            'opening_notes'    => 'nullable|string|max:1000',
        ]);

        $register = CashRegister::with('assignedPersonal')->findOrFail($validated['cash_register_id']);
        $this->authorizeSessionAction($register->company_id);

        if (!$register->assigned_personal_id) {
            return back()->withErrors(['error' => 'La caja no tiene un cajero asignado.']);
        }

        if ($register->activeSession()) {
            return back()->withErrors(['error' => 'Esta caja ya tiene una sesión activa.']);
        }

        try {
            CashRegisterSession::create([
                'cash_register_id' => $register->id,
                'personal_id'      => $register->assigned_personal_id,
                'opened_by'        => auth()->id(),
                'opening_amount'   => $validated['opening_amount'],
                'status'           => 'open',
                'notes'            => $validated['opening_notes'] ?? null,
                'opened_at'        => now(),
            ]);

            return redirect()->back()->with('success', 'Caja abierta exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al abrir caja', ['message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al abrir la caja: ' . $e->getMessage()]);
        }
    }

    public function close(CashRegisterSession $session)
    {
        if (!$session->isOpen()) {
            return back()->withErrors(['error' => 'Esta sesión ya está cerrada.']);
        }

        $validated = request()->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
        ]);

        try {
            $income         = $session->totalIncome();
            $expense        = $session->totalExpense();
            $expectedAmount = (float) $session->opening_amount + $income - $expense;
            $closingAmount  = (float) $validated['closing_amount'];

            $session->update([
                'closed_by'       => auth()->id(),
                'closing_amount'  => $closingAmount,
                'expected_amount' => $expectedAmount,
                'difference'      => $closingAmount - $expectedAmount,
                'status'          => 'closed',
                'notes'           => $validated['notes'] ?? $session->notes,
                'closed_at'       => now(),
            ]);

            return redirect()->back()->with('success', 'Caja cerrada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al cerrar caja', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al cerrar la caja: ' . $e->getMessage()]);
        }
    }

    public function show(CashRegisterSession $session)
    {
        $session->load(['cashRegister.branch', 'openedBy', 'closedBy']);

        $movements = $session->movements()
            ->with('user')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();

        $availableRegisters = $session->cashRegister
            ? CashRegister::where('company_id', $session->cashRegister->company_id)
                ->where('active', true)
                ->with('branch')
                ->get()
            : collect();

        $user = auth()->user();
        $canAdjustCash = $user->is_super_admin
            || $user->hasPermissionInCompany('cash.adjust', $user->getCurrentCompany());

        return view('cash.session.show', compact('session', 'movements', 'availableRegisters', 'canAdjustCash'));
    }

    public function addMovement(CashRegisterSession $session)
    {
        if (!$session->isOpen()) {
            return back()->withErrors(['error' => 'No se pueden agregar movimientos a una sesión cerrada.']);
        }

        $categoryKeys = array_keys(CashMovement::CATEGORIES);

        $validated = request()->validate([
            'type'          => ['required', Rule::in(['income', 'expense'])],
            'category'      => ['required', Rule::in($categoryKeys)],
            'amount'        => 'required|numeric|min:0.01',
            'method'        => 'nullable|string|max:50',
            'description'   => 'nullable|string|max:500',
            'movement_date' => 'required|date',
        ]);

        try {
            $session->load('cashRegister');

            CashMovement::create([
                ...$validated,
                'company_id'               => $session->cashRegister->company_id,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
            ]);

            return redirect()->route('cash.session.show', $session)->with('success', 'Movimiento registrado.');
        } catch (\Throwable $e) {
            Log::error('Error al agregar movimiento', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar el movimiento: ' . $e->getMessage()]);
        }
    }

    /**
     * Registra un ajuste sobre la diferencia de un cierre de caja.
     * Crea un movimiento de ajuste (ingreso/egreso según el signo de la
     * diferencia) y recalcula el esperado y la diferencia de la sesión.
     */
    public function adjustDifference(CashRegisterSession $session)
    {
        $session->load('cashRegister');
        $this->authorizeSessionAction($session->cashRegister->company_id);

        if ($session->isOpen()) {
            return back()->with('error', 'Solo se pueden ajustar sesiones ya cerradas.');
        }

        $difference = (float) $session->difference;
        if (abs($difference) < 0.01) {
            return back()->with('error', 'Esta caja no tiene diferencia por ajustar.');
        }

        $validated = request()->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . round(abs($difference), 2)],
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'amount.max' => 'El monto no puede superar la diferencia actual (' . number_format(abs($difference), 2) . ').',
            'reason.required' => 'Debes indicar el motivo del ajuste.',
        ]);

        // Sobrante (dif > 0) → ajuste positivo (ingreso). Faltante (dif < 0) → ajuste negativo (egreso).
        $isSurplus = $difference > 0;
        $type      = $isSurplus ? 'income' : 'expense';
        $category  = $isSurplus ? 'cash_adjustment_in' : 'cash_adjustment_out';

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($session, $validated, $type, $category) {
                CashMovement::create([
                    'company_id'               => $session->cashRegister->company_id,
                    'cash_register_id'         => $session->cash_register_id,
                    'cash_register_session_id' => $session->id,
                    'user_id'                  => auth()->id(),
                    'type'                     => $type,
                    'category'                 => $category,
                    'amount'                   => $validated['amount'],
                    'method'                   => 'efectivo',
                    'description'              => 'Ajuste de caja: ' . $validated['reason'],
                    'movement_date'            => now(),
                ]);

                // Recalcular esperado y diferencia con el nuevo movimiento incluido.
                $session->refresh();
                $expected = (float) $session->opening_amount + $session->totalIncome() - $session->totalExpense();
                $session->update([
                    'expected_amount' => $expected,
                    'difference'      => (float) $session->closing_amount - $expected,
                ]);
            });

            return back()->with('success', 'Ajuste registrado correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al ajustar diferencia de caja', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->with('error', 'No se pudo registrar el ajuste: ' . $e->getMessage());
        }
    }

    /**
     * Corrige el monto contado de un cierre cuando el cajero se equivocó al
     * contar/teclear (el dinero físico siempre estuvo correcto). NO crea ningún
     * movimiento de caja: solo actualiza el conteo, recalcula la diferencia y
     * deja traza en las notas.
     */
    public function recountClosing(CashRegisterSession $session)
    {
        $session->load('cashRegister');
        $this->authorizeSessionAction($session->cashRegister->company_id);

        if ($session->isOpen()) {
            return back()->with('error', 'Solo se puede corregir el conteo de sesiones ya cerradas.');
        }

        $validated = request()->validate([
            'counted_amount' => ['required', 'numeric', 'min:0'],
            'reason'         => ['required', 'string', 'max:500'],
        ], [
            'counted_amount.required' => 'Indica el monto real contado.',
            'reason.required'         => 'Debes indicar el motivo de la corrección.',
        ]);

        try {
            $old      = (float) $session->closing_amount;
            $new      = (float) $validated['counted_amount'];
            $expected = (float) $session->opening_amount + $session->totalIncome() - $session->totalExpense();

            $note = sprintf(
                '[Corrección de conteo · %s · %s] %s. Monto contado: %s → %s',
                auth()->user()->name,
                now()->format('d/m/Y H:i'),
                $validated['reason'],
                number_format($old, 2),
                number_format($new, 2)
            );
            $notes = trim(($session->notes ? $session->notes . "\n" : '') . $note);

            $session->update([
                'closing_amount'  => $new,
                'expected_amount' => $expected,
                'difference'      => $new - $expected,
                'notes'           => $notes,
            ]);

            return back()->with('success', 'Conteo corregido correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al corregir conteo de caja', ['session_id' => $session->id, 'message' => $e->getMessage()]);
            return back()->with('error', 'No se pudo corregir el conteo: ' . $e->getMessage());
        }
    }

    private function authorizeSessionAction(int $companyId): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $user->getCurrentCompany()?->id !== $companyId) {
            abort(403);
        }
    }
}
