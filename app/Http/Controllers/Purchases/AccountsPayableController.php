<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\SupplierPayment;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountsPayableController extends Controller
{
    use ResolvesCashSession;

    public function index()
    {
        $user  = auth()->user();
        $cid   = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $query = Purchase::with(['supplier', 'payments'])
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('purchase_date');

        if ($supplierId = request('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $payables = $query->paginate(15);

        // KPIs
        $allPending = Purchase::whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))->get();
        $totalOwed = $allPending->sum(fn ($p) => $p->total - $p->paid_amount);

        $accounts = TreasuryAccount::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        // Caja abierta del personal logueado (para pagar desde caja)
        $cashSession = $this->currentOpenSession();

        return view('purchases.payables.index', compact('payables', 'totalOwed', 'accounts', 'cashSession'));
    }

    public function registerPayment(Purchase $purchase)
    {
        $this->authorizePurchase($purchase);

        if ($purchase->payment_status === 'paid') {
            return back()->withErrors(['error' => 'Esta compra ya está pagada por completo.']);
        }

        $validated = request()->validate([
            'payment_source'      => 'required|in:caja,tesoreria',
            'treasury_account_id' => 'required_if:payment_source,tesoreria|nullable|exists:treasury_accounts,id',
            'amount'              => 'required|numeric|min:0.01',
            'payment_date'        => 'required|date',
            'method'              => 'nullable|string|max:50',
            'reference'           => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        $balance = (float) $purchase->total - (float) $purchase->paid_amount;
        $amount  = (float) $validated['amount'];

        if ($amount > $balance + 0.001) {
            return back()->withErrors(['error' => 'El monto (' . number_format($amount, 2) . ') supera el saldo pendiente (' . number_format($balance, 2) . ').']);
        }

        // ── Pago desde la CAJA del personal logueado ──────────────────
        if ($validated['payment_source'] === 'caja') {
            return $this->payFromCash($purchase, $validated, $amount);
        }

        // ── Pago desde una CUENTA DE TESORERÍA ────────────────────────
        $account = TreasuryAccount::findOrFail($validated['treasury_account_id']);
        $this->authorizeCompany($account->company_id);

        if ($amount > (float) $account->balance + 0.001) {
            return back()->withErrors(['error' => 'La cuenta «' . $account->name . '» no tiene saldo suficiente (disponible: ' . number_format($account->balance, 2) . ').']);
        }

        try {
            DB::transaction(function () use ($purchase, $account, $validated, $amount) {
                // 1. Registrar el pago
                SupplierPayment::create([
                    'company_id'          => $purchase->company_id,
                    'purchase_id'         => $purchase->id,
                    'treasury_account_id' => $account->id,
                    'amount'              => $amount,
                    'payment_date'        => $validated['payment_date'],
                    'method'              => $validated['method'] ?? null,
                    'reference'           => $validated['reference'] ?? null,
                    'notes'               => $validated['notes'] ?? null,
                    'user_id'             => auth()->id(),
                ]);

                // 2. Movimiento de tesorería (egreso)
                TreasuryMovement::create([
                    'company_id'          => $purchase->company_id,
                    'treasury_account_id' => $account->id,
                    'user_id'             => auth()->id(),
                    'type'                => 'out',
                    'category'            => 'supplier_payment',
                    'amount'              => $amount,
                    'reference_type'      => Purchase::class,
                    'reference_id'        => $purchase->id,
                    'description'         => 'Pago compra ' . $purchase->code . ' — ' . ($purchase->supplier?->name ?? ''),
                    'movement_date'       => now(),
                ]);

                // 3. Descontar saldo de la cuenta
                $account->decrement('balance', $amount);

                // 4. Actualizar la compra
                $purchase->increment('paid_amount', $amount);
                $purchase->refresh()->recalcPaymentStatus();
            });

            return back()->with('success', 'Pago registrado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al registrar pago proveedor', ['purchase' => $purchase->id, 'msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    /** Registra el pago a proveedor saliendo de la caja abierta del personal (sin tocar tesorería). */
    private function payFromCash(Purchase $purchase, array $validated, float $amount)
    {
        $session = $this->currentOpenSession();
        if (!$session) {
            return back()->withErrors(['error' => 'No tienes una caja abierta para pagar desde caja.']);
        }

        $available = $session->expectedBalance();
        if ($amount > $available + 0.001) {
            return back()->withErrors(['error' => 'La caja no tiene saldo suficiente (disponible: ' . money($available) . ').']);
        }

        try {
            DB::transaction(function () use ($purchase, $session, $validated, $amount) {
                $method = $validated['method'] ?? 'efectivo';
                $desc   = 'Pago compra ' . $purchase->code . ' — ' . ($purchase->supplier?->name ?? 'Proveedor');

                // 1. Registrar el pago (desde caja, sin cuenta de tesorería)
                SupplierPayment::create([
                    'company_id'          => $purchase->company_id,
                    'purchase_id'         => $purchase->id,
                    'treasury_account_id' => null,
                    'amount'              => $amount,
                    'payment_date'        => $validated['payment_date'],
                    'method'              => $method,
                    'reference'           => $validated['reference'] ?? 'CAJA',
                    'notes'               => $validated['notes'] ?? 'Pago desde caja',
                    'user_id'             => auth()->id(),
                ]);

                // 2. Egreso en la caja
                CashMovement::create([
                    'company_id'               => $purchase->company_id,
                    'cash_register_id'         => $session->cash_register_id,
                    'cash_register_session_id' => $session->id,
                    'user_id'                  => auth()->id(),
                    'type'                     => 'expense',
                    'category'                 => 'expense_supplier',
                    'amount'                   => $amount,
                    'method'                   => $method,
                    'reference_type'           => Purchase::class,
                    'reference_id'             => $purchase->id,
                    'description'              => $desc,
                    'movement_date'            => $validated['payment_date'],
                ]);

                // 3. Actualizar la compra
                $purchase->increment('paid_amount', $amount);
                $purchase->refresh()->recalcPaymentStatus();
            });

            return back()->with('success', 'Pago registrado desde caja exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al pagar proveedor desde caja', ['purchase' => $purchase->id, 'msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    private function authorizePurchase(Purchase $purchase): void
    {
        $this->authorizeCompany($purchase->company_id);
    }

    private function authorizeCompany(int $companyId): void
    {
        if (!auth()->user()->is_super_admin && $companyId !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
