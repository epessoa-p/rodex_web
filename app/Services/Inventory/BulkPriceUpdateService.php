<?php

namespace App\Services\Inventory;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Ajuste masivo de precios: sube o baja un porcentaje a los productos
 * seleccionados (los que el usuario está viendo con sus filtros), con
 * redondeo opcional. Lo usan la vista de Stock y su vista previa.
 */
class BulkPriceUpdateService
{
    /** Redondeos ofrecidos: valor => paso (0 = sin redondeo). */
    public const ROUNDINGS = ['none' => 0, '0.50' => 0.5, '1' => 1, '5' => 5];

    /**
     * Calcula los precios resultantes sin guardar nada (vista previa).
     *
     * @return array{rows: array<int, array>, count: int}
     */
    public function preview(int $companyId, array $ids, float $percent, string $mode, bool $withCost, string $rounding): array
    {
        $factor = $this->factor($percent, $mode);
        $step   = self::ROUNDINGS[$rounding] ?? 0;

        $rows = Product::where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'price'])
            ->map(fn (Product $p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'sku'       => $p->sku,
                'price'     => (float) $p->price,
                'new_price' => $this->adjust((float) $p->price, $factor, $step),
                'cost'      => (float) $p->cost,
                'new_cost'  => $withCost
                    ? $this->adjust((float) $p->cost, $factor, $step)
                    : (float) $p->cost,
            ])->values()->all();

        return ['rows' => $rows, 'count' => count($rows)];
    }

    /**
     * Aplica el ajuste. Devuelve el mapa id => [price, cost] para refrescar
     * la tabla sin recargar.
     *
     * @return array{updated: int, products: array<int, array{price: float, cost: float}>}
     */
    public function apply(int $companyId, array $ids, float $percent, string $mode, bool $withCost, string $rounding): array
    {
        $preview = $this->preview($companyId, $ids, $percent, $mode, $withCost, $rounding);
        $map     = [];

        DB::transaction(function () use ($preview, $withCost, &$map) {
            foreach ($preview['rows'] as $row) {
                $data = ['price' => $row['new_price']];
                if ($withCost) {
                    $data['cost'] = $row['new_cost'];
                }
                Product::whereKey($row['id'])->update($data);
                $map[$row['id']] = [
                    'price' => $row['new_price'],
                    'cost'  => $withCost ? $row['new_cost'] : $row['cost'],
                ];
            }
        });

        return ['updated' => count($map), 'products' => $map];
    }

    /** +5 % → 1.05 · −5 % → 0.95 */
    private function factor(float $percent, string $mode): float
    {
        $p = max(0, $percent) / 100;

        return $mode === 'decrease' ? max(0, 1 - $p) : 1 + $p;
    }

    /** Aplica el factor y redondea al paso elegido (nunca negativo). */
    private function adjust(float $value, float $factor, float $step): float
    {
        $new = $value * $factor;
        if ($step > 0) {
            $new = round($new / $step) * $step;
        }

        return round(max(0, $new), 2);
    }
}
