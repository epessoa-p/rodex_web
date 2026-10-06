<?php

namespace App\Support;

use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseOrder;

/**
 * Descuento del proveedor sobre una compra u orden de compra. Un solo lugar
 * para el cálculo (web y API):
 *
 * - El descuento es un MONTO sobre el subtotal (la pantalla lo puede pedir en
 *   %, en monto o editando el total: siempre llega como monto).
 * - Las líneas guardan el precio de lista; el descuento va en la cabecera y se
 *   reparte proporcionalmente en el costo de cada producto al entrar al kardex.
 * - En una OC recibida por partes, cada recepción lleva su parte del descuento.
 */
class PurchaseDiscount
{
    /** Acota el descuento entre 0 y el subtotal. */
    public static function clamp(float $subtotal, ?float $discount): float
    {
        return round(min(max(0.0, (float) $discount), max(0.0, $subtotal)), 2);
    }

    /** Multiplicador del costo: 1 − descuento/subtotal (1 = sin descuento). */
    public static function factor(float $subtotal, float $discount): float
    {
        return $subtotal > 0 ? max(0.0, 1 - ($discount / $subtotal)) : 1.0;
    }

    /** Costo unitario con el descuento repartido (para el kardex). */
    public static function netUnitCost(float $unitCost, float $factor): float
    {
        return round($unitCost * $factor, 4);
    }

    /**
     * Parte del descuento de una OC que corresponde a una recepción.
     * $alreadyApplied = descuento ya aplicado en recepciones anteriores; si con
     * esta se completa la orden, se le da exactamente lo que falta (sin
     * diferencias de redondeo).
     */
    public static function portion(
        float $orderDiscount,
        float $orderSubtotal,
        float $receivedValue,
        float $alreadyApplied = 0.0,
        bool $completesOrder = false,
    ): float {
        if ($orderDiscount <= 0 || $orderSubtotal <= 0) {
            return 0.0;
        }
        $left = max(0.0, round($orderDiscount - $alreadyApplied, 2));
        if ($completesOrder) {
            return $left;
        }

        return min($left, round($orderDiscount * ($receivedValue / $orderSubtotal), 2));
    }

    /**
     * Descuento que lleva la compra generada por una recepción de [$order]
     * ($receivedValue = cantidad × precio de lista de lo recibido). Llamar
     * DESPUÉS de sumar lo recibido a la OC: si ya quedó completa, se le da el
     * resto exacto del descuento.
     */
    public static function forReceipt(PurchaseOrder $order, float $receivedValue): float
    {
        $orderDiscount = (float) $order->discount;
        if ($orderDiscount <= 0) {
            return 0.0;
        }
        $applied = (float) Purchase::where('purchase_order_id', $order->id)->sum('discount');
        $order->load('items');

        return self::portion($orderDiscount, (float) $order->subtotal, $receivedValue, $applied, $order->isFullyReceived());
    }
}
