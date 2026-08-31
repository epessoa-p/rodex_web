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
 * Liquidación de comisiones a mecánicos. Calcula lo ganado (sobre la mano de
 * obra de las OTs entregadas × % de comisión), lo pagado y el pendiente; y
 * registra un pago descontándolo de caja o de una cuenta de tesorería.
 */
class MechanicPaymentService
{
    /** Resumen por mecánico: ganado / pagado / pendiente. */
    public function summary(int $companyId): Collection
    {
        // Mano de obra de OTs entregadas por mecánico.
        $laborByMech = WorkOrder::where('status', 'entregada')
            ->whereNotNull('mechanic_id')
            ->selectRaw('mechanic_id, SUM(subtotal_services) as labor')
            ->groupBy('mechanic_id')
            ->pluck('labor', 'mechanic_id');

        // Pagado por mecánico.
        $paidByMech = MechanicPayment::selectRaw('mechanic_id, SUM(amount) as paid')
            ->groupBy('mechanic_id')
            ->pluck('paid', 'mechanic_id');

        return Mechanic::orderBy('name')->get()
            ->map(function (Mechanic $m) use ($laborByMech, $paidByMech) {
                $rate   = (float) ($m->commission_rate ?? 0);
                $labor  = (float) ($laborByMech[$m->id] ?? 0);
                $earned = round($labor * $rate / 100, 2);
                $paid   = (float) ($paidByMech[$m->id] ?? 0);

                return [
                    'id'              => $m->id,
                    'name'            => $m->name,
                    'active'          => (bool) $m->active,
                    'commission_rate' => $rate,
                    'labor'           => $labor,
                    'earned'          => $earned,
                    'paid'            => $paid,
                    'pending'         => round($earned - $paid, 2),
                ];
            })
            // Solo activos, o inactivos con algún saldo/movimiento.
            ->filter(fn ($r) => $r['active'] || $r['earned'] != 0 || $r['paid'] != 0)
            ->values();
    }

    /** Registra un pago al mecánico y su gasto (caja o tesorería). */
    public function pay(
        Mechanic $mechanic,
        float $amount,
        string $source,
        ?TreasuryAccount $account,
        $session,
        ?string $method,
        ?string $notes
    ): MechanicPayment {
        return DB::transaction(function () use ($mechanic, $amount, $source, $account, $session, $method, $notes) {
            $method ??= 'efectivo';

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
