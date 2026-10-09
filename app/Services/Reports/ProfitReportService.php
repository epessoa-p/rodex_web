<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ganancias (precio − costo) de ventas y taller en un período.
 *
 * - Ventas: completadas (sin ventas de motos). Ingreso por línea = subtotal
 *   menos su parte del descuento general de la venta. Costo = costo guardado
 *   al vender (`sale_items.unit_cost`); en líneas viejas sin ese dato se usa
 *   el costo actual del producto y se cuentan como "estimadas".
 * - Devoluciones del período: restan su ingreso y su costo.
 * - Ventas rápidas (sin producto): costo desconocido → aparte, salvo que se
 *   pida unirlas (entonces suman con costo 0).
 * - Taller: OTs entregadas. Ingreso = servicios + repuestos − descuento (sin
 *   impuesto). Costo = repuestos + comisión del mecánico (la pagada, o la que
 *   le corresponde si aún no se le pagó).
 */
class ProfitReportService
{
    private bool $hasSaleCost;
    private bool $hasPartCost;

    public function __construct()
    {
        // Antes de correr el SQL nuevo no existen las columnas: todo es estimado.
        $this->hasSaleCost = Schema::hasColumn('sale_items', 'unit_cost');
        $this->hasPartCost = Schema::hasColumn('work_order_parts', 'unit_cost');
    }

    /**
     * @param  string  $scope  sales | workshop | all
     */
    public function build(int $companyId, Carbon $from, Carbon $to, ?int $branchId = null, string $scope = 'all', bool $mergeQuick = false): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $withSales    = $scope !== 'workshop';
        $withWorkshop = $scope !== 'sales';

        $days = [];      // Y-m-d => [revenue, cost]
        $products = [];  // product_id => [name, qty, revenue, cost]
        $estimated = 0;

        $sales = ['revenue' => 0.0, 'cost' => 0.0, 'count' => 0, 'returns_revenue' => 0.0, 'returns_cost' => 0.0];
        $quick = ['revenue' => 0.0, 'count' => 0, 'merged' => $mergeQuick];
        $workshop = [
            'revenue' => 0.0, 'labor' => 0.0, 'parts' => 0.0, 'parts_cost' => 0.0,
            'commission_paid' => 0.0, 'commission_pending' => 0.0, 'count' => 0,
        ];

        if ($withSales) {
            $this->collectSales($companyId, $range, $branchId, $mergeQuick, $sales, $quick, $days, $products, $estimated);
        }
        if ($withWorkshop) {
            $this->collectWorkshop($companyId, $range, $branchId, $workshop, $days, $products, $estimated);
        }

        $salesProfit = $sales['revenue'] - $sales['cost'];
        $commission  = $workshop['commission_paid'] + $workshop['commission_pending'];
        $shopProfit  = $workshop['revenue'] - $workshop['parts_cost'] - $commission;

        $revenue = $sales['revenue'] + $workshop['revenue'];
        $cost    = $sales['cost'] + $workshop['parts_cost'];
        $profit  = $revenue - $cost - $commission;

        ksort($days);
        $byDay = [];
        foreach ($days as $date => [$r, $c]) {
            $byDay[] = ['date' => $date, 'revenue' => $this->r($r), 'cost' => $this->r($c), 'profit' => $this->r($r - $c)];
        }

        $rows = collect($products)
            ->map(fn ($p) => [
                'name'     => $p['name'],
                'quantity' => $p['qty'],
                'revenue'  => $this->r($p['revenue']),
                'cost'     => $this->r($p['cost']),
                'profit'   => $this->r($p['revenue'] - $p['cost']),
                'margin'   => $this->margin($p['revenue'] - $p['cost'], $p['revenue']),
            ])
            ->filter(fn ($p) => $p['revenue'] > 0)
            ->values();

        return [
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'scope'  => $scope,
            'totals' => [
                'revenue'    => $this->r($revenue),
                'cost'       => $this->r($cost),
                'commission' => $this->r($commission),
                'profit'     => $this->r($profit),
                'margin'     => $this->margin($profit, $revenue),
            ],
            'sales' => [
                'revenue'         => $this->r($sales['revenue']),
                'cost'            => $this->r($sales['cost']),
                'profit'          => $this->r($salesProfit),
                'margin'          => $this->margin($salesProfit, $sales['revenue']),
                'count'           => $sales['count'],
                'returns_revenue' => $this->r($sales['returns_revenue']),
                'returns_cost'    => $this->r($sales['returns_cost']),
                'enabled'         => $withSales,
            ],
            'workshop' => [
                'revenue'            => $this->r($workshop['revenue']),
                'labor'              => $this->r($workshop['labor']),
                'parts'              => $this->r($workshop['parts']),
                'parts_cost'         => $this->r($workshop['parts_cost']),
                'commission_paid'    => $this->r($workshop['commission_paid']),
                'commission_pending' => $this->r($workshop['commission_pending']),
                'profit'             => $this->r($shopProfit),
                'margin'             => $this->margin($shopProfit, $workshop['revenue']),
                'count'              => $workshop['count'],
                'enabled'            => $withWorkshop,
            ],
            'quick' => [
                'revenue' => $this->r($quick['revenue']),
                'count'   => $quick['count'],
                'merged'  => $mergeQuick,
            ],
            'by_day'          => $byDay,
            'top_products'    => $rows->sortByDesc('profit')->take(10)->values()->all(),
            'low_margin'      => $rows->filter(fn ($p) => $p['margin'] < 15)->sortBy('margin')->take(10)->values()->all(),
            'estimated_lines' => $estimated,
        ];
    }

    // ── Ventas ────────────────────────────────────────────────────────────

    private function collectSales(int $cid, array $range, ?int $branchId, bool $mergeQuick, array &$sales, array &$quick, array &$days, array &$products, int &$estimated): void
    {
        // Parte de la línea que queda tras el descuento general de la venta.
        $factor  = '(1 - COALESCE(s.discount, 0) * 1.0 / NULLIF(s.subtotal, 0))';
        $unitCost = $this->hasSaleCost ? 'COALESCE(si.unit_cost, p.cost, 0)' : 'COALESCE(p.cost, 0)';
        $isEstimated = $this->hasSaleCost ? 'si.unit_cost IS NULL' : '1 = 1';

        $base = fn () => DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->where('s.company_id', $cid)
            ->where('s.status', 'completed')
            ->where(fn ($q) => $q->whereNull('s.sale_category')->orWhere('s.sale_category', '!=', 'moto'))
            ->whereNull('s.deleted_at')
            ->when($branchId, fn ($q) => $q->where('s.branch_id', $branchId))
            ->whereBetween('s.sale_date', $range);

        $sales['count'] = (int) $base()->distinct()->count('s.id');

        // Por producto y día (con producto: costo conocido o estimado).
        $lines = $base()->whereNotNull('si.product_id')
            ->groupBy('si.product_id', DB::raw('DATE(s.sale_date)'))
            ->selectRaw("si.product_id, MAX(COALESCE(p.name, si.description)) name, DATE(s.sale_date) d,
                SUM(si.quantity) qty,
                SUM(si.subtotal * COALESCE($factor, 1)) revenue,
                SUM(si.quantity * $unitCost) cost,
                SUM(CASE WHEN $isEstimated THEN 1 ELSE 0 END) est")
            ->get();
        foreach ($lines as $l) {
            $this->add($days, $products, $l->d, (int) $l->product_id, $l->name, (float) $l->qty, (float) $l->revenue, (float) $l->cost);
            $sales['revenue'] += (float) $l->revenue;
            $sales['cost']    += (float) $l->cost;
            $estimated        += (int) $l->est;
        }

        // Ventas rápidas: sin producto, costo desconocido.
        $quickRows = $base()->whereNull('si.product_id')
            ->groupBy(DB::raw('DATE(s.sale_date)'))
            ->selectRaw("DATE(s.sale_date) d, COUNT(*) n, SUM(si.subtotal * COALESCE($factor, 1)) revenue")
            ->get();
        foreach ($quickRows as $q) {
            $quick['revenue'] += (float) $q->revenue;
            $quick['count']   += (int) $q->n;
            if ($mergeQuick) {
                $sales['revenue'] += (float) $q->revenue;
                $days[$q->d][0] = ($days[$q->d][0] ?? 0) + (float) $q->revenue;
                $days[$q->d][1] = ($days[$q->d][1] ?? 0);
            }
        }

        // Devoluciones del período: restan ingreso y costo de lo devuelto.
        if (! Schema::hasTable('sale_return_items')) {
            return;
        }
        $returns = DB::table('sale_return_items as ri')
            ->join('sale_returns as r', 'r.id', '=', 'ri.sale_return_id')
            ->join('sale_items as si', 'si.id', '=', 'ri.sale_item_id')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->where('r.company_id', $cid)
            ->whereNull('r.deleted_at')
            ->when($branchId, fn ($q) => $q->where('s.branch_id', $branchId))
            ->whereBetween('r.return_date', $range)
            ->groupBy('si.product_id', DB::raw('DATE(r.return_date)'))
            ->selectRaw("si.product_id, MAX(COALESCE(p.name, si.description)) name, DATE(r.return_date) d,
                SUM(ri.quantity) qty,
                SUM(ri.subtotal * COALESCE($factor, 1)) revenue,
                SUM(CASE WHEN si.product_id IS NULL THEN 0 ELSE ri.quantity * $unitCost END) cost")
            ->get();
        foreach ($returns as $l) {
            if ($l->product_id === null) {
                // Devolución de una venta rápida.
                $quick['revenue'] -= (float) $l->revenue;
                if (! $mergeQuick) {
                    continue;
                }
            }
            $sales['returns_revenue'] += (float) $l->revenue;
            $sales['returns_cost']    += (float) $l->cost;
            $sales['revenue']         -= (float) $l->revenue;
            $sales['cost']            -= (float) $l->cost;
            $this->add($days, $products, $l->d, (int) $l->product_id, $l->name, -(float) $l->qty, -(float) $l->revenue, -(float) $l->cost);
        }
    }

    // ── Taller ────────────────────────────────────────────────────────────

    private function collectWorkshop(int $cid, array $range, ?int $branchId, array &$shop, array &$days, array &$products, int &$estimated): void
    {
        $orders = DB::table('work_orders as o')
            ->leftJoin('mechanics as m', 'm.id', '=', 'o.mechanic_id')
            ->where('o.company_id', $cid)
            ->where('o.status', 'entregada')
            ->whereNull('o.deleted_at')
            ->when($branchId, fn ($q) => $q->where('o.branch_id', $branchId))
            ->whereBetween('o.delivered_at', $range)
            ->get([
                'o.id', 'o.delivered_at', 'o.subtotal_services', 'o.subtotal_parts', 'o.discount',
                'o.mechanic_id', 'o.mechanic_payment_id', 'o.commission_amount', 'm.commission_rate',
            ]);
        if ($orders->isEmpty()) {
            return;
        }

        // Costo de repuestos por OT y por producto.
        $unitCost = $this->hasPartCost ? 'COALESCE(wp.unit_cost, p.cost, 0)' : 'COALESCE(p.cost, 0)';
        $isEstimated = $this->hasPartCost ? 'wp.unit_cost IS NULL' : '1 = 1';
        $parts = DB::table('work_order_parts as wp')
            ->leftJoin('products as p', 'p.id', '=', 'wp.product_id')
            ->whereIn('wp.work_order_id', $orders->pluck('id'))
            ->groupBy('wp.work_order_id', 'wp.product_id')
            ->selectRaw("wp.work_order_id, wp.product_id, MAX(p.name) name, SUM(wp.quantity) qty,
                SUM(wp.subtotal) revenue, SUM(wp.quantity * $unitCost) cost,
                SUM(CASE WHEN $isEstimated THEN 1 ELSE 0 END) est")
            ->get()
            ->groupBy('work_order_id');

        foreach ($orders as $o) {
            $services = (float) $o->subtotal_services;
            $partsSub = (float) $o->subtotal_parts;
            $gross    = $services + $partsSub;
            $discount = min((float) $o->discount, $gross);
            // El descuento de la OT se reparte entre mano de obra y repuestos.
            $factor   = $gross > 0 ? 1 - $discount / $gross : 1;

            $labor    = $services * $factor;
            $partsNet = $partsSub * $factor;
            $date     = Carbon::parse($o->delivered_at)->toDateString();

            // Comisión: la pagada, o la que corresponde si aún no se pagó.
            if ($o->mechanic_payment_id) {
                $commission = (float) $o->commission_amount;
                $shop['commission_paid'] += $commission;
            } else {
                $commission = $o->mechanic_id ? round($services * (float) $o->commission_rate / 100, 2) : 0.0;
                $shop['commission_pending'] += $commission;
            }

            $partsCost = 0.0;
            foreach ($parts->get($o->id, collect()) as $p) {
                $partsCost += (float) $p->cost;
                $estimated += (int) $p->est;
                if ($p->product_id) {
                    $this->add($days, $products, null, (int) $p->product_id, $p->name, (float) $p->qty, (float) $p->revenue * $factor, (float) $p->cost);
                }
            }

            $shop['count']++;
            $shop['labor']      += $labor;
            $shop['parts']      += $partsNet;
            $shop['parts_cost'] += $partsCost;
            $shop['revenue']    += $labor + $partsNet;

            $days[$date][0] = ($days[$date][0] ?? 0) + $labor + $partsNet;
            $days[$date][1] = ($days[$date][1] ?? 0) + $partsCost + $commission;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Acumula en el día (si se indica) y en el producto. */
    private function add(array &$days, array &$products, ?string $date, int $productId, ?string $name, float $qty, float $revenue, float $cost): void
    {
        if ($date !== null) {
            $days[$date][0] = ($days[$date][0] ?? 0) + $revenue;
            $days[$date][1] = ($days[$date][1] ?? 0) + $cost;
        }
        if ($productId) {
            $p = $products[$productId] ?? ['name' => $name ?: 'Producto', 'qty' => 0.0, 'revenue' => 0.0, 'cost' => 0.0];
            $p['qty']     += $qty;
            $p['revenue'] += $revenue;
            $p['cost']    += $cost;
            $products[$productId] = $p;
        }
    }

    private function r(float $v): float
    {
        return round($v, 2);
    }

    private function margin(float $profit, float $revenue): float
    {
        return $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0;
    }
}
