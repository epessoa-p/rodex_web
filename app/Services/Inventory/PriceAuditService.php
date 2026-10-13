<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductPriceChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría de precios para el dueño: historial de cambios y alertas de
 * "bajó el precio, vendió y lo volvió a subir".
 *
 * Una alerta = una baja de precio de un producto, seguida (en pocos días) de
 * un cambio que lo devuelve a ~su valor anterior, con ventas de ese producto
 * hechas al precio bajo entre ambos cambios.
 */
class PriceAuditService
{
    /** Días máximos entre la baja y la vuelta al precio anterior. */
    public const WINDOW_DAYS = 7;

    /** "Volvió" = el precio nuevo es al menos este % del anterior a la baja. */
    private const RESTORED_RATIO = 0.98;

    /**
     * Alertas en el período (por la fecha de la baja).
     *
     * @return Collection<int, array>
     */
    public function suspicious(int $companyId, Carbon $from, Carbon $to): Collection
    {
        $drops = ProductPriceChange::with(['product:id,name,sku', 'user:id,name'])
            ->where('company_id', $companyId)
            ->where('field', 'price')
            ->whereColumn('new_value', '<', 'old_value')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('created_at')
            ->get();

        $alerts = collect();
        foreach ($drops as $drop) {
            $restore = ProductPriceChange::with('user:id,name')
                ->where('company_id', $companyId)
                ->where('product_id', $drop->product_id)
                ->where('field', 'price')
                ->where('created_at', '>=', $drop->created_at)
                ->where('id', '>', $drop->id)
                ->where('created_at', '<=', $drop->created_at->copy()->addDays(self::WINDOW_DAYS))
                ->where('new_value', '>=', (float) $drop->old_value * self::RESTORED_RATIO)
                ->orderBy('created_at')->orderBy('id')
                ->first();
            if (! $restore) {
                continue;
            }

            $sales = DB::table('sale_items as si')
                ->join('sales as s', 's.id', '=', 'si.sale_id')
                ->leftJoin('users as u', 'u.id', '=', 's.created_by')
                ->where('s.company_id', $companyId)
                ->where('s.status', 'completed')
                ->whereNull('s.deleted_at')
                ->where('si.product_id', $drop->product_id)
                ->whereBetween('s.sale_date', [$drop->created_at, $restore->created_at])
                ->where('si.unit_price', '<=', (float) $drop->new_value + 0.01)
                ->orderBy('s.sale_date')
                ->get(['s.id', 's.code', 's.sale_date', 'si.quantity', 'si.unit_price', 'u.name as seller']);
            if ($sales->isEmpty()) {
                continue;
            }

            $old = (float) $drop->old_value;
            $loss = $sales->sum(fn ($r) => max(0, $old - (float) $r->unit_price) * (float) $r->quantity);

            $alerts->push([
                'product'  => $drop->product,
                'drop'     => $drop,
                'restore'  => $restore,
                'sales'    => $sales,
                'units'    => (float) $sales->sum('quantity'),
                'loss'     => round($loss, 2),
                // Misma persona bajó y subió el precio: más sospechoso.
                'same_user' => $drop->user_id && $drop->user_id === $restore->user_id,
                'minutes'  => (int) $drop->created_at->diffInMinutes($restore->created_at),
            ]);
        }

        return $alerts->sortByDesc('loss')->values();
    }
}
