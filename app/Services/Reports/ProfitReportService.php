<?php

namespace App\Services\Reports;

use Carbon\CarbonInterface;
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

    /** Máximo de filas del listado de ventas / OTs. */
    public const LIST_LIMIT = 300;

    public function __construct()
    {
        // Antes de correr el SQL nuevo no existen las columnas: todo es estimado.
        $this->hasSaleCost = Schema::hasColumn('sale_items', 'unit_cost');
        $this->hasPartCost = Schema::hasColumn('work_order_parts', 'unit_cost');
    }

    /**
     * @param  string  $scope  sales | workshop | all
     */
    public function build(int $companyId, CarbonInterface $from, CarbonInterface $to, ?int $branchId = null, string $scope = 'all', bool $mergeQuick = false): array
    {
        $from  = Carbon::instance($from);
        $to    = Carbon::instance($to);
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $withSales    = $scope !== 'workshop';
        $withWorkshop = $scope !== 'sales';

        $days = [];      // Y-m-d => [revenue, cost]
        $products = [];  // product_id => [name, qty, revenue, cost]
        $estimated = 0;
        $list = [];     // ventas y OTs con su ganancia

        $sales = [
            'revenue' => 0.0, 'cost' => 0.0, 'count' => 0, 'returns_revenue' => 0.0, 'returns_cost' => 0.0,
            'interest' => 0.0, 'credit_count' => 0, 'credit_profit' => 0.0, 'credit_pending' => 0.0,
        ];
        $quick = ['revenue' => 0.0, 'count' => 0, 'merged' => $mergeQuick];
        $workshop = [
            'revenue' => 0.0, 'labor' => 0.0, 'parts' => 0.0, 'parts_cost' => 0.0,
            'commission_paid' => 0.0, 'commission_pending' => 0.0, 'count' => 0,
            'credit_count' => 0, 'credit_profit' => 0.0, 'credit_pending' => 0.0,
        ];

        if ($withSales) {
            $this->collectSales($companyId, $range, $branchId, $mergeQuick, $sales, $quick, $days, $products, $estimated);
            $list = $this->salesList($companyId, $range, $branchId, $mergeQuick);
        }
        if ($withWorkshop) {
            $this->collectWorkshop($companyId, $range, $branchId, $workshop, $days, $products, $estimated, $list);
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
                'interest'   => $this->r($sales['interest']),
                // Parte de la ganancia que viene de ventas/OTs a crédito y saldo por cobrar.
                'credit_count'   => $sales['credit_count'] + $workshop['credit_count'],
                'credit_profit'  => $this->r($sales['credit_profit'] + $workshop['credit_profit']),
                'credit_pending' => $this->r($sales['credit_pending'] + $workshop['credit_pending']),
            ],
            'sales' => [
                'revenue'         => $this->r($sales['revenue']),
                'cost'            => $this->r($sales['cost']),
                'profit'          => $this->r($salesProfit),
                'margin'          => $this->margin($salesProfit, $sales['revenue']),
                'count'           => $sales['count'],
                'returns_revenue' => $this->r($sales['returns_revenue']),
                'returns_cost'    => $this->r($sales['returns_cost']),
                'interest'        => $this->r($sales['interest']),
                'credit_count'    => $sales['credit_count'],
                'credit_profit'   => $this->r($sales['credit_profit']),
                'credit_pending'  => $this->r($sales['credit_pending']),
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
                'credit_count'       => $workshop['credit_count'],
                'credit_profit'      => $this->r($workshop['credit_profit']),
                'credit_pending'     => $this->r($workshop['credit_pending']),
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
            // Ventas y OTs del período con su ganancia (las más recientes primero).
            'transactions'       => collect($list)->sortByDesc('date')->take(self::LIST_LIMIT)->values()->all(),
            'transactions_total' => $sales['count'] + $workshop['count'],
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

        // Intereses de crédito: ingreso sin costo, una vez por venta, en su fecha.
        $salesBase = fn () => DB::table('sales as s')
            ->where('s.company_id', $cid)
            ->where('s.status', 'completed')
            ->where(fn ($q) => $q->whereNull('s.sale_category')->orWhere('s.sale_category', '!=', 'moto'))
            ->whereNull('s.deleted_at')
            ->when($branchId, fn ($q) => $q->where('s.branch_id', $branchId))
            ->whereBetween('s.sale_date', $range);

        $interestRows = $salesBase()->where('s.interest', '>', 0)
            ->groupBy(DB::raw('DATE(s.sale_date)'))
            ->selectRaw('DATE(s.sale_date) d, SUM(s.interest) i')
            ->get();
        foreach ($interestRows as $i) {
            $sales['interest'] += (float) $i->i;
            $sales['revenue']  += (float) $i->i;
            $days[$i->d][0] = ($days[$i->d][0] ?? 0) + (float) $i->i;
            $days[$i->d][1] = ($days[$i->d][1] ?? 0);
        }

        // Ventas a crédito: su ganancia ya se contó al vender; aquí se mide
        // cuánto de ella es a crédito y cuánto falta cobrar (saldo a hoy).
        $credit = $salesBase()->where('s.sale_type', 'credit')
            ->selectRaw('COUNT(*) n,
                SUM(CASE WHEN s.total - COALESCE(s.paid_amount, 0) > 0 THEN s.total - COALESCE(s.paid_amount, 0) ELSE 0 END) pending,
                SUM(COALESCE(s.interest, 0)) interest')
            ->first();
        $quickPart = $mergeQuick ? "si.subtotal * COALESCE($factor, 1)" : '0';
        $creditItems = (float) $base()->where('s.sale_type', 'credit')
            ->selectRaw("SUM(CASE WHEN si.product_id IS NOT NULL
                THEN si.subtotal * COALESCE($factor, 1) - si.quantity * $unitCost
                ELSE $quickPart END) p")
            ->value('p');
        $sales['credit_count']   = (int) ($credit->n ?? 0);
        $sales['credit_pending'] = (float) ($credit->pending ?? 0);
        $sales['credit_profit']  = $creditItems + (float) ($credit->interest ?? 0);

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

    /** Ganancia de cada venta del período (las más recientes primero). */
    private function salesList(int $cid, array $range, ?int $branchId, bool $mergeQuick): array
    {
        $factor   = '(1 - COALESCE(s.discount, 0) * 1.0 / NULLIF(s.subtotal, 0))';
        $unitCost = $this->hasSaleCost ? 'COALESCE(si.unit_cost, p.cost, 0)' : 'COALESCE(p.cost, 0)';
        $isEstimated = $this->hasSaleCost ? 'si.unit_cost IS NULL' : '1 = 1';

        $rows = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->leftJoin('products as p', 'p.id', '=', 'si.product_id')
            ->leftJoin('clients as c', 'c.id', '=', 's.client_id')
            ->where('s.company_id', $cid)
            ->where('s.status', 'completed')
            ->where(fn ($q) => $q->whereNull('s.sale_category')->orWhere('s.sale_category', '!=', 'moto'))
            ->whereNull('s.deleted_at')
            ->when($branchId, fn ($q) => $q->where('s.branch_id', $branchId))
            ->whereBetween('s.sale_date', $range)
            ->groupBy('s.id', 's.code', 's.sale_date', 'c.full_name', 's.sale_type', 's.interest', 's.total', 's.paid_amount')
            ->orderByDesc('s.sale_date')->orderByDesc('s.id')
            ->limit(self::LIST_LIMIT)
            ->selectRaw("s.id, s.code, s.sale_date d, c.full_name client, s.sale_type, s.interest, s.total, s.paid_amount,
                SUM(CASE WHEN si.product_id IS NOT NULL THEN si.subtotal * COALESCE($factor, 1) ELSE 0 END) revenue,
                SUM(CASE WHEN si.product_id IS NULL THEN si.subtotal * COALESCE($factor, 1) ELSE 0 END) quick,
                SUM(CASE WHEN si.product_id IS NOT NULL THEN si.quantity * $unitCost ELSE 0 END) cost,
                SUM(CASE WHEN si.product_id IS NOT NULL AND $isEstimated THEN 1 ELSE 0 END) est")
            ->get();

        return $rows->map(function ($r) use ($mergeQuick) {
            $interest = (float) $r->interest;
            $revenue  = (float) $r->revenue + ($mergeQuick ? (float) $r->quick : 0) + $interest;
            $profit   = $revenue - (float) $r->cost;
            $isCredit = $r->sale_type === 'credit';

            return [
                'type'      => 'sale',
                'id'        => (int) $r->id,
                'code'      => $r->code,
                'date'      => Carbon::parse($r->d)->format('Y-m-d H:i'),
                'client'    => $r->client,
                'revenue'   => $this->r($revenue),
                'cost'      => $this->r((float) $r->cost),
                'profit'    => $this->r($profit),
                'margin'    => $this->margin($profit, $revenue),
                // Venta rápida que no se suma (sin costo conocido).
                'quick'     => $mergeQuick ? 0.0 : $this->r((float) $r->quick),
                'estimated' => (int) $r->est > 0,
                'credit'    => $isCredit,
                // Lo que el cliente todavía debe de esta venta (a hoy).
                'balance'   => $isCredit ? $this->r(max(0, (float) $r->total - (float) $r->paid_amount)) : 0.0,
                'interest'  => $this->r($interest),
            ];
        })->all();
    }

    // ── Taller ────────────────────────────────────────────────────────────

    private function collectWorkshop(int $cid, array $range, ?int $branchId, array &$shop, array &$days, array &$products, int &$estimated, array &$list): void
    {
        $orders = DB::table('work_orders as o')
            ->leftJoin('mechanics as m', 'm.id', '=', 'o.mechanic_id')
            ->leftJoin('clients as c', 'c.id', '=', 'o.client_id')
            ->where('o.company_id', $cid)
            ->where('o.status', 'entregada')
            ->whereNull('o.deleted_at')
            ->when($branchId, fn ($q) => $q->where('o.branch_id', $branchId))
            ->whereBetween('o.delivered_at', $range)
            ->get([
                'o.id', 'o.code', 'c.full_name as client', 'o.delivered_at', 'o.subtotal_services', 'o.subtotal_parts', 'o.discount',
                'o.mechanic_id', 'o.mechanic_payment_id', 'o.commission_amount', 'm.commission_rate',
                'o.payment_type', 'o.total', 'o.paid_amount',
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
            $otRevenue = $labor + $partsNet;
            $otProfit  = $otRevenue - $partsCost - $commission;
            $isCredit  = $o->payment_type === 'credito';
            $balance   = $isCredit ? max(0, (float) $o->total - (float) $o->paid_amount) : 0.0;
            if ($isCredit) {
                $shop['credit_count']++;
                $shop['credit_profit']  += $otProfit;
                $shop['credit_pending'] += $balance;
            }
            $list[] = [
                'type'      => 'ot',
                'id'        => (int) $o->id,
                'code'      => $o->code,
                'date'      => Carbon::parse($o->delivered_at)->format('Y-m-d H:i'),
                'client'    => $o->client,
                'revenue'   => $this->r($otRevenue),
                'cost'      => $this->r($partsCost + $commission),
                'profit'    => $this->r($otProfit),
                'margin'    => $this->margin($otProfit, $otRevenue),
                'quick'     => 0.0,
                'estimated' => false,
                'credit'    => $isCredit,
                'balance'   => $this->r($balance),
                'interest'  => 0.0,
            ];
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
