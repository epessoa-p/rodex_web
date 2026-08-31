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

        $orders = WorkOrder::where('mechanic_id', $mechanic->id)
            ->where('status', 'entregada')
            ->orderByDesc('reception_date')->orderByDesc('id')
            ->with('mechanicPayment:id,payment_date')
            ->get();

        $pending = [];
        $paid = [];

        foreach ($orders as $o) {
            $date = optional($o->delivered_at ?? $o->reception_date)->toDateString();
            if ($o->mechanic_payment_id) {
                $paid[] = [
                    'order_id'     => $o->id,
                    'code'         => $o->code,
                    'date'         => $date,
                    'commission'   => (float) ($o->commission_amount ?? 0),
                    'payment_date' => optional($o->mechanicPayment?->payment_date)->toDateString(),
                ];
            } else {
                $commission = round((float) $o->subtotal_services * $rate / 100, 2);
                if ($commission <= 0) {
                    continue; // sin comisión: no se lista para pagar
                }
                $pending[] = [
                    'order_id'   => $o->id,
                    'code'       => $o->code,
                    'date'       => $date,
                    'labor'      => (float) $o->subtotal_services,
                    'commission' => $commission,
                ];
            }
        }

        return [
            'mechanic' => [
                'id'              => $mechanic->id,
                'name'            => $mechanic->name,
                'commission_rate' => $rate,
                'pending_total'   => round(array_sum(array_column($pending, 'commission')), 2),
                'paid_total'      => (float) MechanicPayment::where('mechanic_id', $mechanic->id)->sum('amount'),
            ],
            'pending' => $pending,
            'paid'    => $paid,
        ];
    }

    /**
     * Liquida las OTs indicadas (+ bono opcional) y registra el gasto.
     * Devuelve el pago creado.
     */
    public function pay(
        Mechanic $mechanic,
        array $workOrderIds,
        float $bonus,
        string $source,
        ?TreasuryAccount $account,
        $session,
        ?string $method,
        ?string $notes
    ): MechanicPayment {
        $rate = (float) ($mechanic->commission_rate ?? 0);

        return DB::transaction(function () use ($mechanic, $workOrderIds, $bonus, $rate, $source, $account, $session, $method, $notes) {
            $method ??= 'efectivo';

            // OTs válidas: del mecánico, entregadas y no pagadas.
            $orders = WorkOrder::whereIn('id', $workOrderIds)
                ->where('mechanic_id', $mechanic->id)
                ->where('status', 'entregada')
                ->whereNull('mechanic_payment_id')
                ->lockForUpdate()
                ->get();

            $commissionSum = 0;
            foreach ($orders as $o) {
                $commissionSum += round((float) $o->subtotal_services * $rate / 100, 2);
            }
            $amount = round($commissionSum + $bonus, 2);

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

    /**
     * Total a liquidar por las OTs indicadas (+ bono), para validar el origen
     * (saldo/caja) antes de pagar.
     */
    public function quote(Mechanic $mechanic, array $workOrderIds, float $bonus): float
    {
        $rate = (float) ($mechanic->commission_rate ?? 0);

        $labor = (float) WorkOrder::whereIn('id', $workOrderIds)
            ->where('mechanic_id', $mechanic->id)
            ->where('status', 'entregada')
            ->whereNull('mechanic_payment_id')
            ->sum('subtotal_services');

        return round($labor * $rate / 100 + $bonus, 2);
    }
}
