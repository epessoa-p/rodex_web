<?php

namespace App\Http\Controllers\Rentals\Concerns;

use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\Rentals\RentalContract;
use App\Models\Rentals\RentalInstallment;
use App\Models\Rentals\RentalPayment;
use App\Models\Rentals\RentalPenalty;

trait HandlesRentalCharge
{
    use ResolvesCashSession;

    /**
     * Mapeo type de pago de alquiler → categoría/tipo de movimiento de caja.
     */
    protected array $rentalCashMap = [
        'alquiler'            => ['category' => 'rental_payment',        'type' => 'income'],
        'deposito'            => ['category' => 'rental_deposit',        'type' => 'income'],
        'penalizacion'        => ['category' => 'rental_penalty',        'type' => 'income'],
        'devolucion_deposito' => ['category' => 'rental_deposit_refund', 'type' => 'expense'],
    ];

    /**
     * Registra un cobro/reembolso de un contrato de alquiler:
     * crea el RentalPayment, el CashMovement en la caja abierta (si hay sesión)
     * y actualiza los acumulados del contrato (paid_amount / deposit_refunded).
     *
     * $type: alquiler | deposito | penalizacion | devolucion_deposito
     */
    protected function chargeToCaja(RentalContract $contract, string $type, float $amount, ?CashRegisterSession $session, array $meta = []): RentalPayment
    {
        $payment = RentalPayment::create([
            'company_id'               => $contract->company_id,
            'rental_contract_id'       => $contract->id,
            'rental_installment_id'    => $meta['rental_installment_id'] ?? null,
            'cash_register_session_id' => $session?->id,
            'type'                     => $type,
            'amount'                   => $amount,
            'method'                   => $meta['method'] ?? 'efectivo',
            'payment_date'             => $meta['payment_date'] ?? now()->toDateString(),
            'reference'                => $meta['reference'] ?? null,
            'notes'                    => $meta['notes'] ?? null,
            'user_id'                  => auth()->id(),
        ]);

        $map = $this->rentalCashMap[$type] ?? ['category' => 'rental_payment', 'type' => 'income'];

        if ($session) {
            $clientName = $contract->client?->full_name ?? $contract->client?->name;
            CashMovement::create([
                'company_id'               => $contract->company_id,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
                'type'                     => $map['type'],
                'category'                 => $map['category'],
                'amount'                   => $amount,
                'method'                   => $meta['method'] ?? 'efectivo',
                'reference_type'           => RentalContract::class,
                'reference_id'             => $contract->id,
                'description'              => RentalPayment::TYPES[$type] . ' ' . $contract->code . ($clientName ? ' — ' . $clientName : ''),
                'movement_date'            => now(),
            ]);
        }

        // Actualizar acumulados del contrato
        if (in_array($type, ['alquiler', 'penalizacion'], true)) {
            $contract->increment('paid_amount', $amount);
            $contract->refresh()->recalcPaymentStatus();
        } elseif ($type === 'devolucion_deposito') {
            $contract->increment('deposit_refunded', $amount);
        }

        return $payment;
    }

    /**
     * Aplica un cobro de renta distribuyéndolo entre las cuotas pendientes
     * (de la más antigua a la más nueva), cobrando cada parte a la caja.
     */
    protected function applyRentPayment(RentalContract $contract, float $amount, ?CashRegisterSession $session, array $meta = []): void
    {
        $remaining = $amount;

        $pending = $contract->installments()
            ->whereIn('status', ['pendiente', 'parcial'])
            ->orderBy('number')
            ->get();

        foreach ($pending as $installment) {
            if ($remaining <= 0.001) {
                break;
            }
            $balance = (float) $installment->amount - (float) $installment->paid_amount;
            $apply   = min($remaining, $balance);

            if ($apply > 0) {
                $this->chargeToCaja($contract, 'alquiler', $apply, $session, $meta + [
                    'rental_installment_id' => $installment->id,
                    'notes' => $meta['notes'] ?? ('Cobro de renta · cuota ' . $installment->number),
                ]);
                $installment->increment('paid_amount', $apply);
                $installment->refresh()->recalcStatus();
                $remaining -= $apply;
            }
        }

        // Sobrante (pago mayor al saldo de cuotas) → pago suelto contra el contrato
        if ($remaining > 0.001) {
            $this->chargeToCaja($contract, 'alquiler', $remaining, $session, $meta + [
                'notes' => $meta['notes'] ?? 'Abono de renta',
            ]);
        }
    }

    /**
     * Aplica la mora acumulada de una cuota vencida: crea la penalización,
     * la suma a penalties_total del contrato y la cobra a caja.
     * Idempotente: solo cobra la mora aún no cobrada (accrued_late_fee).
     */
    protected function accrueLateFee(RentalInstallment $installment, ?CashRegisterSession $session, array $meta = []): float
    {
        $accrued = (float) $installment->accrued_late_fee;
        if ($accrued <= 0) {
            return 0.0;
        }

        $contract = $installment->contract;

        RentalPenalty::create([
            'company_id'         => $contract->company_id,
            'rental_contract_id' => $contract->id,
            'concept'            => 'Mora por atraso · cuota ' . $installment->number,
            'amount'             => $accrued,
            'penalty_date'       => now()->toDateString(),
            'created_by'         => auth()->id(),
        ]);

        $contract->increment('penalties_total', $accrued);
        $contract->refresh()->recalcTotals();

        $this->chargeToCaja($contract, 'penalizacion', $accrued, $session, $meta + [
            'rental_installment_id' => $installment->id,
            'notes' => 'Mora por atraso · cuota ' . $installment->number,
        ]);

        $installment->increment('late_fee_charged', $accrued);

        return $accrued;
    }

    protected function nextRentalCode(int $companyId): string
    {
        $count = RentalContract::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'ALQ-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
