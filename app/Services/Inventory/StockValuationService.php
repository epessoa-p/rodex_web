<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;

/**
 * Valorización del inventario (productos, unidades, valor a costo, valor a
 * venta, ganancia potencial), consolidada o por almacén. La usan la web
 * (Inventario → Stock) y la API (reporte de inventario del móvil).
 */
class StockValuationService
{
    /**
     * @return array{product_count:int,total_units:float,value_cost:float,value_price:float,
     *               potential_profit:float,by_category:array,low_stock:array}
     */
    public function build(?int $companyId, ?int $warehouseId = null, int $lowStockLimit = 30): array
    {
        $products = Product::with('category')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $stockMap = $warehouseId ? $this->warehouseStockMap($companyId, $warehouseId) : null;
        $stockOf  = fn ($p) => $stockMap === null ? (float) $p->current_stock : (float) ($stockMap[$p->id] ?? 0);

        $totalUnits = 0.0;
        $valueCost  = 0.0;
        $valuePrice = 0.0;
        $byCategory = [];
        $lowStock   = [];

        foreach ($products as $p) {
            $s = $stockOf($p);
            $totalUnits += $s;
            $valueCost  += $s * (float) $p->cost;
            $valuePrice += $s * (float) $p->price;

            $cat = $p->category?->name ?: 'Sin categoría';
            $byCategory[$cat] ??= ['name' => $cat, 'products' => 0, 'units' => 0.0, 'value_cost' => 0.0, 'value_price' => 0.0];
            $byCategory[$cat]['products']++;
            $byCategory[$cat]['units']       += $s;
            $byCategory[$cat]['value_cost']  += $s * (float) $p->cost;
            $byCategory[$cat]['value_price'] += $s * (float) $p->price;

            // Bajo mínimo (o agotado con mínimo definido).
            if ((int) $p->min_stock > 0 && $s <= (int) $p->min_stock) {
                $lowStock[] = [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'sku'       => $p->sku,
                    'stock'     => $s,
                    'min_stock' => (int) $p->min_stock,
                    'unit'      => $p->unit,
                ];
            }
        }

        usort($byCategory, fn ($a, $b) => $b['value_price'] <=> $a['value_price']);
        usort($lowStock, fn ($a, $b) => ($a['stock'] - $a['min_stock']) <=> ($b['stock'] - $b['min_stock']));

        return [
            'product_count'    => $products->count(),
            'total_units'      => round($totalUnits, 2),
            'value_cost'       => round($valueCost, 2),
            'value_price'      => round($valuePrice, 2),
            'potential_profit' => round($valuePrice - $valueCost, 2),
            'by_category'      => array_map(fn ($c) => [
                'name'        => $c['name'],
                'products'    => $c['products'],
                'units'       => round($c['units'], 2),
                'value_cost'  => round($c['value_cost'], 2),
                'value_price' => round($c['value_price'], 2),
            ], array_values($byCategory)),
            'low_stock'        => array_slice($lowStock, 0, $lowStockLimit),
        ];
    }

    /**
     * Stock neto por producto en un almacén, derivado del kardex
     * (entradas/ajustes/transferencias recibidas − salidas/transferencias enviadas).
     * @return array<int,float> product_id => cantidad
     */
    public function warehouseStockMap(?int $companyId, int $warehouseId): array
    {
        $in = InventoryMovement::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($warehouseId) {
                $q->where(fn ($w) => $w->where('warehouse_id', $warehouseId)->whereIn('type', ['in', 'adjustment']))
                  ->orWhere(fn ($w) => $w->where('destination_warehouse_id', $warehouseId)->where('type', 'transfer'));
            })
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')->pluck('q', 'product_id');

        $out = InventoryMovement::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', ['out', 'transfer'])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')->pluck('q', 'product_id');

        $map = [];
        foreach ($in as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) + (float) $q;
        }
        foreach ($out as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) - (float) $q;
        }

        return $map;
    }
}
