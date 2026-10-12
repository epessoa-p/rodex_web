<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reglas del kardex: qué es entrada, qué es salida y cómo se suman.
 *
 * - Entrada: compra/recepción (`in`) y ajuste positivo (`adjustment` > 0).
 * - Salida: venta / consumo (`out`) y ajuste negativo.
 * - Transferencia (`transfer`): mueve stock entre almacenes. Sin almacén
 *   elegido no cambia el total de la empresa (no es entrada ni salida); con
 *   un almacén elegido es entrada si llega a él y salida si sale de él.
 */
class KardexMath
{
    /** Filtra por almacén incluyendo las transferencias que llegan a él. */
    public static function whereWarehouse(Builder $query, ?int $warehouseId): Builder
    {
        if (! $warehouseId) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('warehouse_id', $warehouseId)
            ->orWhere(fn ($t) => $t->where('type', 'transfer')->where('destination_warehouse_id', $warehouseId)));
    }

    /** Cambio de stock con signo que produce un movimiento (0 = no cambia el total). */
    public static function delta(InventoryMovement $m, ?int $warehouseId = null): float
    {
        $q = (float) $m->quantity;

        return match ($m->type) {
            'in', 'adjustment' => $q,
            'out' => -$q,
            'transfer' => ! $warehouseId ? 0.0
                : ((int) $m->destination_warehouse_id === $warehouseId ? $q
                : ((int) $m->warehouse_id === $warehouseId ? -$q : 0.0)),
            default => 0.0,
        };
    }

    /**
     * Totales de TODOS los movimientos de la consulta (no solo de la página):
     * cantidad, entradas, salidas, neto y unidades transferidas sin efecto.
     */
    public static function totals(Builder $query, ?int $warehouseId = null): array
    {
        $wh = (int) $warehouseId;
        // Transferencias: con almacén elegido entran o salen; sin él son neutras.
        $trIn  = $wh ? "WHEN type = 'transfer' AND destination_warehouse_id = {$wh} THEN quantity" : '';
        $trOut = $wh ? "WHEN type = 'transfer' AND warehouse_id = {$wh} THEN quantity" : '';

        $row = (clone $query)->toBase()
            ->reorder()
            ->selectRaw("COUNT(*) AS n,
                COALESCE(SUM(CASE WHEN type = 'in' THEN quantity
                    WHEN type = 'adjustment' AND quantity > 0 THEN quantity {$trIn} ELSE 0 END), 0) AS entries,
                COALESCE(SUM(CASE WHEN type = 'out' THEN quantity
                    WHEN type = 'adjustment' AND quantity < 0 THEN -quantity {$trOut} ELSE 0 END), 0) AS exits,
                COALESCE(SUM(CASE WHEN type = 'transfer' THEN quantity ELSE 0 END), 0) AS transfers")
            ->first();

        $entries = (float) ($row->entries ?? 0);
        $exits   = (float) ($row->exits ?? 0);

        return [
            'count'     => (int) ($row->n ?? 0),
            'entries'   => $entries,
            'exits'     => $exits,
            'net'       => $entries - $exits,
            // Sin almacén elegido las transferencias no cuentan; se informan aparte.
            'transfers' => $wh ? 0.0 : (float) ($row->transfers ?? 0),
        ];
    }

    /** "12", "2.5", "0.25" (sin ceros de más). */
    public static function qty(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ','), '0'), '.');
    }
}
