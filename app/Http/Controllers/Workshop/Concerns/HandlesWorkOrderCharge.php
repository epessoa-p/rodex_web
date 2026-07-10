<?php

namespace App\Http\Controllers\Workshop\Concerns;

use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Workshop\WorkOrder;
use App\Models\Workshop\WorkOrderInstallment;
use App\Models\Workshop\WorkOrderPayment;
use Illuminate\Validation\ValidationException;

trait HandlesWorkOrderCharge
{
    use ResolvesCashSession;

    /**
     * Entrega y cobro de una OT dentro de una transacción:
     * descuenta stock de repuestos, registra el cobro (contado o crédito)
     * y marca la orden como entregada.
     *
     * $data: payment_type ('contado'|'credito'), discount, tax, delivered_to,
     *        delivery_notes, method, installments[], down_payment
     */
    protected function deliverWorkOrder(WorkOrder $wo, array $data, ?CashRegisterSession $session): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($wo, $data, $session) {
            $wo->load(['parts', 'services', 'branch']);

            if ($wo->parts->isEmpty() && $wo->services->isEmpty()) {
                throw ValidationException::withMessages(['error' => 'La orden no tiene servicios ni repuestos para cobrar.']);
            }

            // 1. Descontar stock de repuestos
            $warehouseId = $wo->branch?->warehouse_id;
            foreach ($wo->parts as $part) {
                $product = Product::lockForUpdate()->find($part->product_id);
                if ($product && (float) $product->current_stock < (float) $part->quantity) {
                    throw ValidationException::withMessages([
                        'error' => "Stock insuficiente de «{$product->name}» (disponible: {$product->current_stock}).",
                    ]);
                }

                if ($warehouseId) {
                    InventoryMovement::create([
                        'company_id'    => $wo->company_id,
                        'warehouse_id'  => $warehouseId,
                        'branch_id'     => $wo->branch_id,
                        'product_id'    => $part->product_id,
                        'user_id'       => auth()->id(),
                        'type'          => 'out',
                        'quantity'      => $part->quantity,
                        'unit_cost'     => $part->unit_price,
                        'reference'     => $wo->code,
                        'notes'         => 'Repuesto OT ' . $wo->code,
                        'movement_date' => now(),
                    ]);
                }
                Product::where('id', $part->product_id)->decrement('current_stock', $part->quantity);
            }

            // 2. Recalcular totales con descuento/impuesto definitivos
            $discount = (float) ($data['discount'] ?? 0);
            $tax      = (float) ($data['tax'] ?? 0);
            $wo->update(['discount' => $discount, 'tax' => $tax]);
            $wo->refresh()->recalcTotals();
            $wo->refresh();

            $paymentType = $data['payment_type'] ?? 'contado';

            // 3. Marcar entregada
            $wo->update([
                'status'                   => 'entregada',
                'payment_type'             => $paymentType,
                'cash_register_session_id' => $session?->id,
                'delivered_at'             => now(),
                'delivered_to'             => $data['delivered_to'] ?? null,
                'delivery_notes'           => $data['delivery_notes'] ?? null,
            ]);

            // 4. Cobro
            if ($paymentType === 'contado') {
                $this->registerWoPayment($wo, null, (float) $wo->total, $session, [
                    'method' => $data['method'] ?? 'efectivo',
                    'notes'  => 'Pago de contado (entrega)',
                ]);
            } else {
                // Crédito: cronograma de cuotas
                $number = 1;
                foreach (($data['installments'] ?? []) as $inst) {
                    WorkOrderInstallment::create([
                        'company_id'    => $wo->company_id,
                        'work_order_id' => $wo->id,
                        'number'        => $number++,
                        'due_date'      => $inst['due_date'],
                        'amount'        => (float) $inst['amount'],
                        'paid_amount'   => 0,
                        'status'        => 'pendiente',
                    ]);
                }
                $downPayment = (float) ($data['down_payment'] ?? 0);
                if ($downPayment > 0) {
                    $this->registerWoPayment($wo, null, $downPayment, $session, [
                        'method' => $data['method'] ?? 'efectivo',
                        'notes'  => 'Pago inicial (entrega)',
                    ]);
                }
            }

            $wo->refresh()->recalcPaymentStatus();

            // Fidelización: acreditar puntos por la orden de taller entregada
            app(\App\Services\Loyalty\LoyaltyService::class)->awardWorkOrder($wo);
        });
    }

    /**
     * Registra un pago de OT: WorkOrderPayment + CashMovement (si hay caja) + actualiza montos.
     */
    protected function registerWoPayment(WorkOrder $wo, ?WorkOrderInstallment $installment, float $amount, ?CashRegisterSession $session, array $meta = []): WorkOrderPayment
    {
        $payment = WorkOrderPayment::create([
            'company_id'                => $wo->company_id,
            'work_order_id'             => $wo->id,
            'work_order_installment_id' => $installment?->id,
            'cash_register_session_id'  => $session?->id,
            'amount'                    => $amount,
            'payment_date'              => $meta['payment_date'] ?? now()->toDateString(),
            'method'                    => $meta['method'] ?? 'efectivo',
            'reference'                 => $meta['reference'] ?? null,
            'notes'                     => $meta['notes'] ?? null,
            'user_id'                   => auth()->id(),
        ]);

        if ($session) {
            CashMovement::create([
                'company_id'               => $wo->company_id,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
                'type'                     => 'income',
                'category'                 => 'sale',
                'amount'                   => $amount,
                'method'                   => $meta['method'] ?? 'efectivo',
                'reference_type'           => WorkOrder::class,
                'reference_id'             => $wo->id,
                'description'              => 'Taller ' . $wo->code . ($wo->client ? ' — ' . $wo->client->full_name : ''),
                'movement_date'            => now(),
            ]);
        }

        $wo->increment('paid_amount', $amount);
        if ($installment) {
            $installment->increment('paid_amount', $amount);
            $installment->refresh()->recalcStatus();
        }

        return $payment;
    }

    /**
     * Aplica un abono de crédito distribuyéndolo entre las cuotas pendientes (más antigua primero).
     */
    protected function applyWoCredit(WorkOrder $wo, float $amount, ?CashRegisterSession $session, array $meta = []): void
    {
        $remaining = $amount;

        $pending = $wo->installments()
            ->whereIn('status', ['pendiente', 'parcial'])
            ->orderBy('number')
            ->get();

        foreach ($pending as $installment) {
            if ($remaining <= 0.0001) {
                break;
            }
            $balance = (float) $installment->amount - (float) $installment->paid_amount;
            $apply   = min($remaining, $balance);
            if ($apply > 0) {
                $this->registerWoPayment($wo, $installment, $apply, $session, $meta);
                $remaining -= $apply;
            }
        }

        if ($remaining > 0.0001) {
            $this->registerWoPayment($wo, null, $remaining, $session, $meta);
        }
    }
}
