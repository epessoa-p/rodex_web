<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashRegisterSession;
use App\Models\Personal;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    use ResolvesCashSession;

    /** Sesión de caja abierta del usuario actual (o null). */
    public function current()
    {
        $session = $this->currentOpenSession();

        return response()->json(['data' => $session ? $this->payload($session) : null]);
    }

    /** Cajas asignadas al usuario que puede abrir (sin sesión activa). */
    public function registers(Request $request)
    {
        $personal = Personal::where('user_id', $request->user()->id)->first();

        if (! $personal) {
            return response()->json(['data' => []]);
        }

        $registers = CashRegister::where('assigned_personal_id', $personal->id)
            ->with('branch')
            ->orderBy('name')
            ->get()
            ->map(fn (CashRegister $r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'branch'      => $r->branch?->name,
                'has_session' => (bool) $r->activeSession(),
            ]);

        return response()->json(['data' => $registers]);
    }

    /** Abrir una sesión de caja. */
    public function open(Request $request)
    {
        $data = $request->validate([
            'cash_register_id' => ['required', 'integer'],
            'opening_amount'   => ['required', 'numeric', 'min:0'],
            'opening_notes'    => ['nullable', 'string', 'max:1000'],
        ]);

        // find() está scoped a la empresa activa (global scope).
        $register = CashRegister::with('assignedPersonal')->find($data['cash_register_id']);

        if (! $register) {
            return response()->json(['message' => 'Caja no encontrada.'], 404);
        }

        $personal = Personal::where('user_id', $request->user()->id)->first();
        if (! $personal || $register->assigned_personal_id !== $personal->id) {
            return response()->json(['message' => 'Esta caja no está asignada a ti.'], 403);
        }

        if ($register->activeSession()) {
            return response()->json(['message' => 'Esta caja ya tiene una sesión abierta.'], 422);
        }

        $session = CashRegisterSession::create([
            'cash_register_id' => $register->id,
            'personal_id'      => $register->assigned_personal_id,
            'opened_by'        => $request->user()->id,
            'opening_amount'   => $data['opening_amount'],
            'status'           => 'open',
            'notes'            => $data['opening_notes'] ?? null,
            'opened_at'        => now(),
        ]);

        $session->load('cashRegister.branch');

        return response()->json(['data' => $this->payload($session)], 201);
    }

    /** Cerrar la sesión abierta del usuario. */
    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $session = $this->currentOpenSession();

        if (! $session) {
            return response()->json(['message' => 'No tienes una caja abierta.'], 422);
        }

        $income   = $session->totalIncome();
        $expense  = $session->totalExpense();
        $expected = (float) $session->opening_amount + $income - $expense;
        $closing  = (float) $data['closing_amount'];

        $session->update([
            'closed_by'       => $request->user()->id,
            'closing_amount'  => $closing,
            'expected_amount' => $expected,
            'difference'      => $closing - $expected,
            'status'          => 'closed',
            'notes'           => $data['notes'] ?? $session->notes,
            'closed_at'       => now(),
        ]);

        return response()->json([
            'data' => [
                'id'              => $session->id,
                'expected_amount' => $expected,
                'closing_amount'  => $closing,
                'difference'      => $closing - $expected,
            ],
        ]);
    }

    /** Movimientos de la sesión abierta (ingresos y gastos), más recientes primero. */
    public function movements()
    {
        $session = $this->currentOpenSession();

        if (! $session) {
            return response()->json(['data' => []]);
        }

        $movements = $session->movements()
            ->latest('movement_date')->latest('id')->get()
            ->map(fn (CashMovement $m) => [
                'id'          => $m->id,
                'type'        => $m->type, // income | expense
                'category'    => CashMovement::CATEGORIES[$m->category]['label'] ?? $m->category,
                'amount'      => (float) $m->amount,
                'method'      => $m->method,
                'description' => $m->description,
                'date'        => $m->movement_date?->toIso8601String(),
            ]);

        return response()->json(['data' => $movements]);
    }

    /**
     * Registra un GASTO simple contra la caja abierta (operativo, servicio o
     * transporte). Los gastos con integración (proveedor/CxP, personal) se
     * manejan en el web.
     */
    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'amount'   => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'in:expense_operational,expense_service,expense_transport'],
            'concept'  => ['nullable', 'string', 'max:255'],
            'method'   => ['nullable', 'string', 'max:30'],
        ]);

        $session = $this->currentOpenSession();
        if (! $session) {
            return response()->json([
                'message' => 'Necesitas tu caja abierta para registrar un gasto.',
                'code'    => 'cash_session_required',
            ], 422);
        }

        $category = $data['category'] ?? 'expense_operational';
        $concept  = trim((string) ($data['concept'] ?? ''));
        if ($concept === '') {
            $concept = CashMovement::CATEGORIES[$category]['label'] ?? 'Gasto';
        }

        CashMovement::create([
            'company_id'               => $session->cashRegister->company_id,
            'cash_register_id'         => $session->cash_register_id,
            'cash_register_session_id' => $session->id,
            'user_id'                  => auth()->id(),
            'type'                     => 'expense',
            'category'                 => $category,
            'amount'                   => (float) $data['amount'],
            'method'                   => $data['method'] ?? 'efectivo',
            'description'              => $concept,
            'movement_date'            => now(),
        ]);

        $session->load('cashRegister.branch');

        return response()->json(['data' => $this->payload($session)], 201);
    }

    private function payload(CashRegisterSession $s): array
    {
        return [
            'id'             => $s->id,
            'cash_register'  => $s->cashRegister?->name,
            'branch'         => $s->cashRegister?->branch?->name,
            'branch_id'      => $s->cashRegister?->branch_id,
            'opening_amount' => (float) $s->opening_amount,
            'opened_at'      => $s->opened_at?->toIso8601String(),
            'total_income'   => $s->totalIncome(),
            'total_expense'  => $s->totalExpense(),
            'expected_amount'=> (float) $s->opening_amount + $s->totalIncome() - $s->totalExpense(),
        ];
    }
}
