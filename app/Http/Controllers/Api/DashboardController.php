<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use App\Services\Dashboard\OperationalOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard móvil.
 *  - overview(): vista OPERATIVA del día (ventas hoy, OTs, agenda, stock,
 *    ranking de servicios, OTs recientes) en un solo request.
 *  - sales()/workshop()/purchases(): series comparativas (semana vs. semana,
 *    últimas 8 semanas, últimos 6 meses) para la vista de Análisis.
 * Aislamiento por empresa vía global scope.
 */
class DashboardController extends Controller
{
    private const WEEKS = 8;
    private const MONTHS = 6;

    public function __construct(private OperationalOverviewService $overview) {}

    /**
     * Resumen operativo de toda la empresa (mismo que el dashboard web).
     * Cada sección va en null si falta plan o permiso; el móvil no la pinta.
     */
    public function overview(Request $request)
    {
        return response()->json([
            'data' => $this->overview->build($request->user(), $request->attributes->get('tenant_company')),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Top: ingresos por origen + rankings (Análisis → Top)
    // ═══════════════════════════════════════════════════════════════

    private const TOP_LIMIT = 8;

    /**
     * Rankings del período: ingresos por origen (el "versus" Ventas / Taller /
     * Alquileres), top repuestos (POS + taller), servicios, compras y clientes.
     * `by` = amount | qty decide el ORDEN y el CORTE en el servidor, para que
     * "Cantidad" muestre lo que más se mueve aunque no sea lo de mayor monto.
     */
    public function top(Request $request)
    {
        $user    = $request->user();
        $company = $request->attributes->get('tenant_company');
        $cid     = $company?->id;

        $data = $request->validate([
            'period' => ['nullable', 'in:month,last_month,quarter,year'],
            'by'     => ['nullable', 'in:amount,qty'],
        ]);
        [$from, $to, $label] = $this->topPeriod($data['period'] ?? 'month');
        $by = $data['by'] ?? 'amount';

        $can = fn (string $module, string $perm) => $company
            && $company->planAllows($module)
            && ($user->is_super_admin || $user->hasPermissionInCompany($perm, $company));

        $sales     = $can('sales', 'sales-dashboard.view');
        $workshop  = $can('workshop', 'workshop-dashboard.view');
        $rentals   = $can('rentals', 'rentals-dashboard.view');
        $purchases = $can('purchases', 'purchases-dashboard.view');

        $range = [$from->toDateString() . ' 00:00:00', $to->toDateString() . ' 23:59:59'];

        // ── Ingresos por origen ──
        $revenue = [];
        if ($sales) {
            $r = Sale::where('status', 'completed')->whereBetween('sale_date', $range)
                ->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) a')->first();
            $revenue[] = ['key' => 'sales', 'label' => 'Ventas', 'amount' => (float) $r->a, 'count' => (int) $r->c];
        }
        if ($workshop) {
            $r = WorkOrder::where('status', 'entregada')->whereBetween('delivered_at', $range)
                ->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) a')->first();
            $revenue[] = ['key' => 'workshop', 'label' => 'Taller', 'amount' => (float) $r->a, 'count' => (int) $r->c];
        }
        if ($rentals) {
            $r = DB::table('rental_contracts')->where('company_id', $cid)->whereNull('deleted_at')
                ->where('status', '!=', 'anulada')->whereBetween('start_date', $range)
                ->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) a')->first();
            $revenue[] = ['key' => 'rentals', 'label' => 'Alquileres', 'amount' => (float) $r->a, 'count' => (int) $r->c];
        }

        // ── Rankings ──
        $orderCol = $by === 'qty' ? 'qty' : 'amount';
        $rank = fn ($rows) => collect($rows)
            ->map(fn ($r) => ['label' => $r->label, 'amount' => round((float) $r->amount, 2), 'qty' => round((float) $r->qty, 2)])
            ->sortByDesc($orderCol)->take(self::TOP_LIMIT)->values()->all();

        // Repuestos: líneas de ventas completadas ∪ repuestos de OTs entregadas.
        $productParts = [];
        if ($sales) {
            $productParts[] = DB::table('sale_items as i')
                ->join('sales as s', 's.id', '=', 'i.sale_id')
                ->where('s.company_id', $cid)->whereNull('s.deleted_at')
                ->where('s.status', 'completed')->whereBetween('s.sale_date', $range)
                ->selectRaw('i.product_id, SUM(i.subtotal) amount, SUM(i.quantity) qty')
                ->groupBy('i.product_id');
        }
        if ($workshop) {
            $productParts[] = DB::table('work_order_parts as p')
                ->join('work_orders as o', 'o.id', '=', 'p.work_order_id')
                ->where('o.company_id', $cid)->whereNull('o.deleted_at')
                ->where('o.status', 'entregada')->whereBetween('o.delivered_at', $range)
                ->selectRaw('p.product_id, SUM(p.subtotal) amount, SUM(p.quantity) qty')
                ->groupBy('p.product_id');
        }
        $topProducts = [];
        if ($productParts) {
            $union = array_shift($productParts);
            foreach ($productParts as $q) {
                $union->unionAll($q);
            }
            $topProducts = $rank(DB::query()->fromSub($union, 'u')
                ->join('products as pr', 'pr.id', '=', 'u.product_id')
                ->selectRaw('pr.name label, SUM(u.amount) amount, SUM(u.qty) qty')
                ->groupBy('pr.id', 'pr.name')
                ->orderByDesc($orderCol)->limit(self::TOP_LIMIT)->get());
        }

        $topServices = $workshop ? $rank(DB::table('work_order_services as s')
            ->join('work_orders as o', 'o.id', '=', 's.work_order_id')
            ->leftJoin('services as sv', 'sv.id', '=', 's.service_id')
            ->where('o.company_id', $cid)->whereNull('o.deleted_at')
            ->where('o.status', 'entregada')->whereBetween('o.delivered_at', $range)
            ->selectRaw('COALESCE(sv.name, s.description) label, SUM(s.subtotal) amount, SUM(s.quantity) qty')
            ->groupBy('label')->orderByDesc($orderCol)->limit(self::TOP_LIMIT)->get()) : [];

        $topPurchases = $purchases ? $rank(DB::table('purchase_items as i')
            ->join('purchases as p', 'p.id', '=', 'i.purchase_id')
            ->join('products as pr', 'pr.id', '=', 'i.product_id')
            ->where('p.company_id', $cid)->whereNull('p.deleted_at')
            ->whereBetween('p.purchase_date', $range)
            ->selectRaw('pr.name label, SUM(i.subtotal) amount, SUM(i.quantity) qty')
            ->groupBy('pr.id', 'pr.name')->orderByDesc($orderCol)->limit(self::TOP_LIMIT)->get()) : [];

        // Clientes: ventas + OTs por cliente (qty = operaciones).
        $clientParts = [];
        if ($sales) {
            $clientParts[] = DB::table('sales')->where('company_id', $cid)->whereNull('deleted_at')
                ->whereNotNull('client_id')->where('status', 'completed')->whereBetween('sale_date', $range)
                ->selectRaw('client_id, SUM(total) amount, COUNT(*) qty')->groupBy('client_id');
        }
        if ($workshop) {
            $clientParts[] = DB::table('work_orders')->where('company_id', $cid)->whereNull('deleted_at')
                ->whereNotNull('client_id')->where('status', 'entregada')->whereBetween('delivered_at', $range)
                ->selectRaw('client_id, SUM(total) amount, COUNT(*) qty')->groupBy('client_id');
        }
        $topClients = [];
        if ($clientParts) {
            $union = array_shift($clientParts);
            foreach ($clientParts as $q) {
                $union->unionAll($q);
            }
            $topClients = $rank(DB::query()->fromSub($union, 'u')
                ->join('clients as c', 'c.id', '=', 'u.client_id')
                ->selectRaw('c.full_name label, SUM(u.amount) amount, SUM(u.qty) qty')
                ->groupBy('c.id', 'c.full_name')
                ->orderByDesc($orderCol)->limit(self::TOP_LIMIT)->get());
        }

        return response()->json(['data' => [
            'period'        => ['key' => $data['period'] ?? 'month', 'label' => $label,
                                'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'by'            => $by,
            'revenue'       => $revenue,
            'top_products'  => $topProducts,
            'top_services'  => $topServices,
            'top_purchases' => $topPurchases,
            'top_clients'   => $topClients,
        ]]);
    }

    /** [from, to, label] para el período pedido. */
    private function topPeriod(string $key): array
    {
        $today = Carbon::today();

        return match ($key) {
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->startOfMonth()->subDay(),
                'Mes anterior',
            ],
            'quarter' => [$today->copy()->subMonthsNoOverflow(3)->addDay(), $today, 'Últimos 3 meses'],
            'year'    => [$today->copy()->startOfYear(), $today, 'Este año'],
            default   => [$today->copy()->startOfMonth(), $today, 'Este mes'],
        };
    }

    public function sales()
    {
        return response()->json([
            'data' => $this->buildSeries(fn () => Sale::where('status', 'completed'), 'sale_date'),
        ]);
    }

    public function workshop()
    {
        return response()->json([
            'data' => $this->buildSeries(
                fn () => WorkOrder::where('status', 'entregada')->whereNotNull('delivered_at'),
                'delivered_at'
            ),
        ]);
    }

    public function purchases()
    {
        return response()->json([
            'data' => $this->buildSeries(fn () => Purchase::query(), 'purchase_date'),
        ]);
    }

    /** @param \Closure():\Illuminate\Database\Eloquent\Builder $freshQuery */
    private function buildSeries(\Closure $freshQuery, string $dateCol): array
    {
        return [
            'weekly'       => $this->weekly($freshQuery, $dateCol),
            'monthly'      => $this->monthly($freshQuery, $dateCol),
            'week_compare' => $this->weekCompare($freshQuery, $dateCol),
        ];
    }

    /** Comparativa día por día: semana anterior vs. semana actual (lun-dom). */
    private function weekCompare(\Closure $freshQuery, string $dateCol): array
    {
        $thisMonday = Carbon::today()->startOfWeek(Carbon::MONDAY);
        $prevMonday = $thisMonday->copy()->subWeek();

        // Totales por día en el rango [semana anterior .. fin de esta semana].
        $rows = $freshQuery()
            ->whereBetween($dateCol, [
                $prevMonday->toDateString() . ' 00:00:00',
                $thisMonday->copy()->addDays(6)->toDateString() . ' 23:59:59',
            ])
            ->selectRaw("DATE($dateCol) as d, SUM(total) as amount, COUNT(*) as cnt")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $days = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $out = [];
        for ($i = 0; $i < 7; $i++) {
            $cur = $rows[$thisMonday->copy()->addDays($i)->toDateString()] ?? null;
            $prev = $rows[$prevMonday->copy()->addDays($i)->toDateString()] ?? null;
            $out[] = [
                'label'        => $days[$i],
                'current_amount' => (float) ($cur->amount ?? 0),
                'current_count'  => (int) ($cur->cnt ?? 0),
                'prev_amount'    => (float) ($prev->amount ?? 0),
                'prev_count'     => (int) ($prev->cnt ?? 0),
            ];
        }

        return $out;
    }

    private function weekly(\Closure $freshQuery, string $dateCol): array
    {
        // Lunes de la semana más antigua a incluir.
        $start = Carbon::today()->startOfWeek(Carbon::MONDAY)->subWeeks(self::WEEKS - 1);

        $rows = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("YEARWEEK($dateCol, 3) as yw, SUM(total) as amount, COUNT(*) as cnt")
            ->groupBy('yw')
            ->pluck('amount', 'yw');   // amount por yw
        $counts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("YEARWEEK($dateCol, 3) as yw, COUNT(*) as cnt")
            ->groupBy('yw')
            ->pluck('cnt', 'yw');

        $out = [];
        for ($i = 0; $i < self::WEEKS; $i++) {
            $monday = $start->copy()->addWeeks($i);
            $yw = (int) $monday->format('oW'); // ISO year+week -> coincide con YEARWEEK modo 3
            $out[] = [
                'label'  => $monday->format('d/m'),
                'amount' => (float) ($rows[$yw] ?? 0),
                'count'  => (int) ($counts[$yw] ?? 0),
            ];
        }

        return $out;
    }

    private function monthly(\Closure $freshQuery, string $dateCol): array
    {
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow(self::MONTHS - 1);

        $amounts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, SUM(total) as amount")
            ->groupBy('ym')
            ->pluck('amount', 'ym');
        $counts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $out = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $m = $start->copy()->addMonthsNoOverflow($i);
            $ym = $m->format('Y-m');
            $out[] = [
                'label'  => $months[$m->month - 1],
                'amount' => (float) ($amounts[$ym] ?? 0),
                'count'  => (int) ($counts[$ym] ?? 0),
            ];
        }

        return $out;
    }
}
