<?php

namespace App\Services\Workshop;

use App\Models\CashMovement;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Workshop\WorkOrder;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Facades\DB;

/**
 * Reabrir una OT entregada para corregirla: anula el cobro, devuelve el stock
 * de los repuestos y la deja editable otra vez. Solo mientras la caja de ese
 * cobro siga ABIERTA (si ya se cerró, revertir descuadraría ese cierre).
 * Compartido por la web y la API.
 */
class WorkOrderReopenService
{
    /** Motivo por el que NO se puede reabrir, o null si sí se puede. */
    public function blockedReason(WorkOrder $order): ?string
    {
        if ($order->status !== 'entregada') {
            return $order->status === 'anulada'
                ? 'La orden está anulada.'
                : 'La orden aún no está entregada.';
        }

        if ($order->payment_type === 'credito') {
            return 'Las órdenes a crédito se corrigen desde la web.';
        }

        if ($order->mechanic_payment_id) {
            return 'La comisión del mecánico ya fue pagada.';
        }

        // Todas las sesiones donde se cobró deben seguir abiertas.
        $sessionIds = $order->payments()->pluck('cash_register_session_id')
            ->push($order->cash_register_session_id)
            ->filter()->unique();

        foreach ($sessionIds as $id) {
            $session = \App\Models\CashRegisterSession::find($id);
            if (! $session || $session->status !== 'open') {
                return 'La caja de ese cobro ya fue cerrada.';
            }
        }

        return null;
    }

    public function canReopen(WorkOrder $order): bool
    {
        return $this->blockedReason($order) === null;
    }

    /**
     * Anula cobro + devuelve stock + revierte puntos y deja la OT en
     * "terminada" (editable). Al volver a entregarla, deliverWorkOrder
     * descuenta el stock y cobra de nuevo: el kardex queda trazable.
     */
    public function reopen(WorkOrder $order, int $userId): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order->load(['parts', 'payments', 'branch']);

            // 1. Cobro: movimientos de caja + pagos registrados.
            CashMovement::where('company_id', $order->company_id)
                ->where('reference_type', WorkOrder::class)
                ->where('reference_id', $order->id)
                ->delete();
            $order->payments()->delete();
            $order->installments()->delete();
            $order->update(['paid_amount' => 0]);

            // 2. Stock de los repuestos (entrada al kardex, como al anular una venta).
            $warehouseId = $order->branch?->warehouse_id;
            foreach ($order->parts as $part) {
                if ($warehouseId) {
                    InventoryMovement::create([
                        'company_id'    => $order->company_id,
                        'warehouse_id'  => $warehouseId,
                        'branch_id'     => $order->branch_id,
                        'product_id'    => $part->product_id,
                        'user_id'       => $userId,
                        'type'          => 'in',
                        'quantity'      => $part->quantity,
                        'unit_cost'     => $part->unit_price,
                        'reference'     => $order->code,
                        'notes'         => 'Reapertura OT ' . $order->code,
                        'movement_date' => now(),
                    ]);
                }
                Product::where('id', $part->product_id)->increment('current_stock', $part->quantity);
            }

            // 3. Fidelización: quitar los puntos acreditados por la entrega.
            app(LoyaltyService::class)->reverseWorkOrder($order);

            // 4. Vuelve a estar en curso y editable.
            $order->update([
                'status'                   => 'terminada',
                'delivered_at'             => null,
                'delivered_to'             => null,
                'delivery_notes'           => null,
                'payment_type'             => null,
                'cash_register_session_id' => null,
            ]);
            $order->refresh()->recalcPaymentStatus();
        });
    }
}
