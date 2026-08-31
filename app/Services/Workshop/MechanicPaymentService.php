<?php

namespace App\Services\Workshop;

use App\Models\CashMovement;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\MechanicPayment;
use App\Models\Workshop\WorkOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Liquidación de comisiones a mecánicos POR OT. Cada OT entregada con mecánico
 * y % de comisión genera una comisión; el pago liquida OTs concretas (las marca
 * como pagadas y las vincula al pago), descontando de caja o tesorería.
 */
class MechanicPaymentService
{
    /** Resumen por mecánico: pendiente (OTs no pagadas) y pagado. */
    public function summary(int $companyId): Collection
    {
        // Mano de obra de OTs entregadas NO pagadas, por mecánico.
        $pendingByMech = WorkOrder::where('status', 'entregada')
            ->whereNotNull('mechanic_id')
            ->whereNull('mechanic_payment_id')
            ->selectRaw('mechanic_id, SUM(subtotal_services) as labor, COUNT(*) as cnt')
            ->groupBy('mechanic_id')
            ->get()
            ->keyBy('mechanic_id');

        $paidByMech = MechanicPayment::selectRaw('mechanic_id, SUM(amount) as paid')
            ->groupBy('mechanic_id')
            ->pluck('paid', 'mechanic_id');

        return Mechanic::orderBy('name')->get()
            ->map(function (Mechanic $m) use ($pendingByMech, $paidByMech) {
                $rate    = (float) ($m->commission_rate ?? 0);
                $row     = $pendingByMech[$m->id] ?? null;
                $labor   = $row ? (float) $row->labor : 0;
                $pending = round($labor * $rate / 100, 2);

                return [
                    'id'              => $m->id,
                    'name'            => $m->name,
                    'active'          => (bool) $m->active,
                    'commission_rate' => $rate,
                    'pending'         => $pending,
                    'pending_count'   => $row ? (int) $row->cnt : 0,
                    'paid'            => (float) ($paidByMech[$m->id] ?? 0),
                ];
            })
            ->filter(fn ($r) => $r['active'] || $r['pending'] != 0 || $r['paid'] != 0)
            ->values();
    }

    /** OTs del mecánico: pendientes (comisión en vivo) y pagadas (congelada). */
    public function detail(Mechanic $mechanic): array
    {
        $rate = (float) ($mechanic->commission_rate ?? 0);

        // OTs entregadas NO pagadas (comisión en vivo).
        $pending = [];
        $orders = WorkOrder::where('mechanic_id', $mechanic->id)
            ->where('status', 'entregada')
            ->whereNull('mechanic_payment_id')
            ->orderByDesc('reception_date')->orderByDesc('id')
            ->get();
        foreach ($orders as $o) {
            $commission = round((float) $o->subtotal_services * $rate / 100, 2);
            if ($commission <= 0) {
                continue; // sin comisión: no se lista para pagar
            }
            $pending[] = [
                'order_id'   => $o->id,
                'code'       => $o->code,
                'date'       => optional($o->delivered_at ?? $o->reception_date)->toDateString(),
                'labor'      => (float) $o->subtotal_services,
                'commission' => $commission,
            ];
        }

        // Pagos (agrupados) con sus OTs liquidadas.
        $payments = MechanicPayment::where('mechanic_id', $mechanic->id)
            ->latest('payment_date')->latest('id')
            ->with([
                'workOrders:id,mechanic_payment_id,code,commission_amount,reception_date,delivered_at',
                'treasuryAccount:id,name',
            ])
            ->get()
            ->map(fn (MechanicPayment $p) => [
                'id'      => $p->id,
                'date'    => optional($p->payment_date)->toDateString(),
                'amount'  => (float) $p->amount,
                'method'  => $p->method,
                'source'  => $p->payment_source,
                'account' => $p->treasuryAccount?->name,
                'notes'   => $p->notes,
                'orders'  => $p->workOrders->map(fn (WorkOrder $o) => [
                    'order_id'   => $o->id,
                    'code'       => $o->code,
                    'date'       => optional($o->delivered_at ?? $o->reception_date)->toDateString(),
                    'commission' => (float) ($o->commission_amount ?? 0),
                ])->values(),
            ])->values();

        return [
            'mechanic' => [
                'id'              => $mechanic->id,
                'name'            => $mechanic->name,
                'commission_rate' => $rate,
                'pending_total'   => round(array_sum(array_column($pending, 'commission')), 2),
                'paid_total'      => (float) MechanicPayment::where('mechanic_id', $mechanic->id)->sum('amount'),
            ],
            'pending'  => $pending,
            'payments' => $payments,
        ];
    }

    /**
     * Liquida las OTs indicadas pagando `$amount` (editable) y registra el gasto.
     * Las OTs quedan vinculadas al pago y su comisión se congela (valor calculado).
     * Devuelve el pago creado.
     */
    public function pay(
        Mechanic $mechanic,
        array $workOrderIds,
        float $amount,
        string $source,
        ?TreasuryAccount $account,
        $session,
        ?string $method,
        ?string $notes
    ): MechanicPayment {
        $rate = (float) ($mechanic->commission_rate ?? 0);
        $amount = round($amount, 2);

        return DB::transaction(function () use ($mechanic, $workOrderIds, $amount, $rate, $source, $account, $session, $method, $notes) {
            $method ??= 'efectivo';

            // OTs válidas: del mecánico, entregadas y no pagadas.
            $orders = WorkOrder::whereIn('id', $workOrderIds)
                ->where('mechanic_id', $mechanic->id)
                ->where('status', 'entregada')
                ->whereNull('mechanic_payment_id')
                ->lockForUpdate()
                ->get();

            $payment = MechanicPayment::create([
                'company_id'               => $mechanic->company_id,
                'mechanic_id'              => $mechanic->id,
                'amount'                   => $amount,
                'payment_source'           => $source,
                'treasury_account_id'      => $source === 'treasury' ? $account->id : null,
                'cash_register_session_id' => $source === 'cash' ? $session->id : null,
                'method'                   => $method,
                'notes'                    => $notes,
                'payment_date'             => now()->toDateString(),
                'created_by'               => auth()->id(),
            ]);

            // Marca cada OT como pagada y congela su comisión.
            foreach ($orders as $o) {
                $o->update([
                    'mechanic_payment_id' => $payment->id,
                    'commission_amount'   => round((float) $o->subtotal_services * $rate / 100, 2),
                ]);
            }

            if ($source === 'treasury') {
                TreasuryMovement::create([
                    'company_id'          => $mechanic->company_id,
                    'treasury_account_id' => $account->id,
                    'user_id'             => auth()->id(),
                    'type'                => 'out',
                    'category'            => 'payroll',
                    'amount'              => $amount,
                    'reference_type'      => MechanicPayment::class,
                    'reference_id'        => $payment->id,
                    'description'         => 'Pago a mecánico ' . $mechanic->name,
                    'movement_date'       => now(),
                ]);
                $account->decrement('balance', $amount);
            } else {
                CashMovement::create([
                    'company_id'               => $mechanic->company_id,
                    'cash_register_id'         => $session->cash_register_id,
                    'cash_register_session_id' => $session->id,
                    'user_id'                  => auth()->id(),
                    'type'                     => 'expense',
                    'category'                 => 'expense_payroll',
                    'amount'                   => $amount,
                    'method'                   => $method,
                    'reference_type'           => MechanicPayment::class,
                    'reference_id'             => $payment->id,
                    'description'              => 'Pago a mecánico ' . $mechanic->name,
                    'movement_date'            => now(),
                ]);
            }

            return $payment;
        });
    }
}
