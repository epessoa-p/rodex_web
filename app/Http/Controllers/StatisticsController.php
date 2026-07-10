<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Motos\MotoUnit;
use App\Models\Product;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\Supplier;
use App\Models\Rentals\RentalContract;
use App\Models\Rentals\RentalInstallment;
use App\Models\Rentals\RentalPayment;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleItem;
use App\Models\Sales\SaleReturn;
use App\Models\User;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatisticsController extends Controller
{
    private string $period = 'monthly';
    private string $periodNoun = 'mes';
    private ?int $branchId = null;

    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        // Sucursales (tabs con color) + sucursal activa
        $branches = \App\Models\Branch::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get(['id', 'name', 'color']);
        $branchParam = $request->get('branch');
        $this->branchId = ($branchParam && $branchParam !== 'all' && $branches->contains('id', (int) $branchParam))
            ? (int) $branchParam : null;
        $branch = $this->branchId ?? 'all';

        // Período: diario / semanal / quincenal / mensual (default mensual) + comparativa contra el período anterior
        $period = in_array($request->get('period'), ['daily', 'weekly', 'quincenal', 'monthly'], true) ? $request->get('period') : 'monthly';
        $this->period = $period;

        if ($period === 'daily') {
            $d    = (string) $request->get('date', '');
            $base = ($d && strtotime($d)) ? Carbon::parse($d) : Carbon::now();
            $cur  = [$base->copy()->startOfDay(), $base->copy()->endOfDay()];
            $prev = [$base->copy()->subDay()->startOfDay(), $base->copy()->subDay()->endOfDay()];
            $periodLabel = $base->isToday() ? 'Hoy' : $base->format('d/m/Y');
            $this->periodNoun = 'día';
        } elseif ($period === 'weekly') {
            $w = (string) $request->get('week', '');
            $base = preg_match('/^(\d{4})-W(\d{1,2})$/', $w, $m)
                ? Carbon::now()->setISODate((int) $m[1], (int) $m[2])
                : Carbon::now();
            $cur  = [$base->copy()->startOfWeek(), $base->copy()->endOfWeek()];
            $prev = [$base->copy()->subWeek()->startOfWeek(), $base->copy()->subWeek()->endOfWeek()];
            $periodLabel = 'Semana del ' . $base->copy()->startOfWeek()->format('d/m') . ' al ' . $base->copy()->endOfWeek()->format('d/m/Y');
            $this->periodNoun = 'semana';
        } elseif ($period === 'quincenal') {
            $qd   = (string) $request->get('qdate', '');
            $base = ($qd && strtotime($qd)) ? Carbon::parse($qd) : Carbon::now();
            $half = $base->day <= 15 ? 1 : 2;
            if ($half === 1) {
                $cur  = [$base->copy()->startOfMonth(), $base->copy()->startOfMonth()->addDays(14)->endOfDay()];
                $prev = [$base->copy()->subMonthNoOverflow()->startOfMonth()->addDays(15), $base->copy()->subMonthNoOverflow()->endOfMonth()];
            } else {
                $cur  = [$base->copy()->startOfMonth()->addDays(15), $base->copy()->endOfMonth()];
                $prev = [$base->copy()->startOfMonth(), $base->copy()->startOfMonth()->addDays(14)->endOfDay()];
            }
            $periodLabel = ($half === 1 ? '1ª' : '2ª') . ' quincena de ' . ucfirst($base->translatedFormat('F Y'));
            $this->periodNoun = 'quincena';
        } else {
            $month = (string) $request->get('month', '');
            $base  = preg_match('/^\d{4}-\d{2}$/', $month) ? Carbon::parse($month . '-01') : Carbon::now()->startOfMonth();
            $cur   = [$base->copy()->startOfMonth(), $base->copy()->endOfMonth()];
            $prev  = [$base->copy()->subMonth()->startOfMonth(), $base->copy()->subMonth()->endOfMonth()];
            $periodLabel = ucfirst($base->translatedFormat('F Y'));
            $this->periodNoun = 'mes';
        }

        // Valores precargados de cada selector
        $dateValue     = $period === 'daily'     ? $base->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $weekValue     = $period === 'weekly'    ? $base->format('o-\WW') : Carbon::now()->format('o-\WW');
        $monthValue    = $period === 'monthly'   ? $base->format('Y-m')   : Carbon::now()->format('Y-m');
        $quincenaValue = $period === 'quincenal' ? $base->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $stats = [
            'ventas'     => $this->salesStats($cid, $base, $cur, $prev),
            'personal'   => $this->staffStats($cid, $cur),
            'clientes'   => $this->clientsStats($cid, $base, $cur, $prev),
            'compras'    => $this->purchasesStats($cid, $base, $cur, $prev),
            'inventario' => $this->inventoryStats($cid),
            'taller'     => $this->workshopStats($cid, $cur, $prev),
            'alquileres' => $this->rentalsStats($cid, $cur, $prev),
        ];
        $stats['resumen'] = $this->summaryStats($stats);
        $chartData = $this->buildChartData($stats);

        $viewData = compact('stats', 'period', 'periodLabel', 'dateValue', 'weekValue', 'monthValue', 'quincenaValue', 'chartData', 'branches', 'branch');

        // AJAX: devolver solo el contenido (panes + datos de gráficos) para refrescar sin recargar
        if ($request->boolean('partial')) {
            return response()->json([
                'html'        => view('statistics.partials.content', $viewData)->render(),
                'periodLabel' => $periodLabel,
                'period'      => $period,
            ]);
        }

        return view('statistics.index', $viewData);
    }

    /** Arma el arreglo de datos para los gráficos (compartido por vista inicial y AJAX). */
    private function buildChartData(array $stats): array
    {
        return [
            'ventasTrend'      => $stats['ventas']['trend'],
            'ventasCashCredit' => $stats['ventas']['cashCredit'],
            'ventasTop'        => $stats['ventas']['topProducts'],
            'ventasComparison' => $stats['ventas']['comparison'],
            'personal'         => $stats['personal']['chart'],
            'clientesNew'      => $stats['clientes']['newTrend'],
            'clientesTop'      => $stats['clientes']['topBuyers'],
            'comprasTrend'     => $stats['compras']['trend'],
            'comprasTop'       => $stats['compras']['topSuppliers'],
            'inventario'       => $stats['inventario']['distribution'],
            'taller'           => $stats['taller']['distribution'],
            'alquileres'       => $stats['alquileres']['fleet'],
        ];
    }

    // ── Helpers ───────────────────────────────────────────────
    private function pct(float $curV, float $prevV): float
    {
        if ($prevV <= 0) return $curV > 0 ? 100.0 : 0.0;
        return round((($curV - $prevV) / $prevV) * 100, 1);
    }

    private function insight(string $severity, string $title, string $text): array
    {
        $icons = ['good' => 'bi-check-circle', 'warning' => 'bi-exclamation-triangle', 'danger' => 'bi-exclamation-octagon', 'info' => 'bi-info-circle'];
        return ['severity' => $severity, 'icon' => $icons[$severity] ?? 'bi-info-circle', 'title' => $title, 'text' => $text];
    }

    /** Serie de tendencia adaptada al período activo (día/semana/mes). $expr p.ej. 'SUM(total)' o 'COUNT(*)'. */
    private function trendSeries($query, string $dateCol, string $expr, Carbon $base): array
    {
        $labels = []; $data = [];

        if ($this->period === 'daily' || $this->period === 'quincenal') {
            // Últimos 14 días
            $from = $base->copy()->subDays(13)->startOfDay();
            $to   = $base->copy()->endOfDay();
            $rows = (clone $query)->whereBetween($dateCol, [$from, $to])
                ->selectRaw("DATE($dateCol) as ym, $expr as s")->groupBy('ym')->pluck('s', 'ym');
            $c = $from->copy();
            while ($c <= $to) {
                $labels[] = $c->format('d/m');
                $data[]   = (float) ($rows[$c->format('Y-m-d')] ?? 0);
                $c->addDay();
            }
        } elseif ($this->period === 'weekly') {
            // Últimas 8 semanas (ISO)
            $from = $base->copy()->subWeeks(7)->startOfWeek();
            $to   = $base->copy()->endOfWeek();
            $rows = (clone $query)->whereBetween($dateCol, [$from, $to])
                ->selectRaw("YEARWEEK($dateCol, 3) as ym, $expr as s")->groupBy('ym')->pluck('s', 'ym');
            $c = $from->copy();
            while ($c <= $to) {
                $labels[] = $c->format('d/m');
                $data[]   = (float) ($rows[$c->format('oW')] ?? 0);
                $c->addWeek();
            }
        } else {
            // Últimos 6 meses
            $from = $base->copy()->subMonths(5)->startOfMonth();
            $to   = $base->copy()->endOfMonth();
            $rows = (clone $query)->whereBetween($dateCol, [$from, $to])
                ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, $expr as s")->groupBy('ym')->pluck('s', 'ym');
            $c = $from->copy();
            while ($c <= $to) {
                $labels[] = ucfirst($c->translatedFormat('M'));
                $data[]   = (float) ($rows[$c->format('Y-m')] ?? 0);
                $c->addMonth();
            }
        }
        return ['labels' => $labels, 'data' => $data];
    }

    private function scopedSales(?int $cid)
    {
        return Sale::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->where('status', 'completed');
    }

    /** Ganancia (ingreso − costo) de las ventas completadas en un rango. */
    private function salesProfit(?int $cid, array $range): float
    {
        $row = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->when($cid, fn ($q) => $q->where('sales.company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->whereBetween('sales.sale_date', $range)
            ->selectRaw('COALESCE(SUM(sale_items.subtotal),0) rev, COALESCE(SUM(sale_items.quantity*products.cost),0) cost')
            ->first();

        return (float) $row->rev - (float) $row->cost;
    }

    /** Comparativa período actual vs. anterior comparable, segmentada según el filtro activo. */
    private function salesComparison(?int $cid, string $period, Carbon $base, array $cur, array $prev): array
    {
        $labels = []; $curData = []; $prevData = [];
        $mode = 'grouped'; $single = []; $highlight = 0;

        if ($period === 'daily') {
            // Por franjas de 2h; comparado con el mismo día de la semana anterior
            $curRange  = [$base->copy()->startOfDay(), $base->copy()->endOfDay()];
            $prevRange = [$base->copy()->subWeek()->startOfDay(), $base->copy()->subWeek()->endOfDay()];
            $labels = ['12am', '2am', '4am', '6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'];
            $bucket = function ($range) use ($cid) {
                $rows = $this->scopedSales($cid)->whereBetween('created_at', $range)
                    ->selectRaw('FLOOR(HOUR(created_at)/2) b, SUM(total) t')->groupBy('b')->pluck('t', 'b');
                $arr = array_fill(0, 12, 0.0);
                foreach ($rows as $b => $t) { $bi = (int) $b; if ($bi >= 0 && $bi < 12) $arr[$bi] = (float) $t; }
                return $arr;
            };
            $curData = $bucket($curRange); $prevData = $bucket($prevRange);
            $wd = $base->translatedFormat('l');
            $prevLabel = ucfirst($wd) . ' anterior';
            $curLabel  = $base->isToday() ? 'Hoy' : ucfirst($wd);
            $note = 'Comparado con el ' . mb_strtolower($wd) . ' de la semana anterior';
        } elseif ($period === 'weekly') {
            // Por día de la semana (Lun→Dom)
            $curRange = $cur; $prevRange = $prev;
            $dows  = [2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado', 1 => 'Domingo'];
            $order = [2, 3, 4, 5, 6, 7, 1];
            $bucket = fn ($range) => $this->scopedSales($cid)->whereBetween('sale_date', $range)
                ->selectRaw('DAYOFWEEK(sale_date) dw, SUM(total) t')->groupBy('dw')->pluck('t', 'dw');
            $cb = $bucket($curRange); $pb = $bucket($prevRange);
            foreach ($order as $dw) { $labels[] = $dows[$dw]; $curData[] = (float) ($cb[$dw] ?? 0); $prevData[] = (float) ($pb[$dw] ?? 0); }
            $prevLabel = 'Semana anterior'; $curLabel = 'Semana actual'; $note = 'Comparado con la semana anterior';
        } elseif ($period === 'quincenal') {
            // Por día, alineado por posición (día 1º del rango actual vs 1º del anterior)
            $curRange = $cur; $prevRange = $prev;
            $bucket = fn ($range) => $this->scopedSales($cid)->whereBetween('sale_date', $range)
                ->selectRaw('DATE(sale_date) d, SUM(total) t')->groupBy('d')->pluck('t', 'd');
            $cb = $bucket($curRange); $pb = $bucket($prevRange);
            $curStart  = Carbon::parse($cur[0])->startOfDay();
            $curEnd    = Carbon::parse($cur[1])->startOfDay();
            $prevStart = Carbon::parse($prev[0])->startOfDay();
            $nDays = $curStart->diffInDays($curEnd) + 1;
            for ($i = 0; $i < $nDays; $i++) {
                $cd = $curStart->copy()->addDays($i);
                $pd = $prevStart->copy()->addDays($i);
                $labels[]   = $cd->format('j');
                $curData[]  = (float) ($cb[$cd->format('Y-m-d')] ?? 0);
                $prevData[] = (float) ($pb[$pd->format('Y-m-d')] ?? 0);
            }
            $prevLabel = 'Quincena anterior'; $curLabel = 'Quincena actual'; $note = 'Comparado con la quincena anterior';
        } else {
            // Mensual: una barra por mes (total mensual), últimos 6 meses; mes seleccionado resaltado
            $curRange = $cur; $prevRange = $prev;
            $from = Carbon::parse($cur[0])->copy()->subMonths(5)->startOfMonth();
            $to   = Carbon::parse($cur[1]);
            $rows = $this->scopedSales($cid)->whereBetween('sale_date', [$from, $to])
                ->selectRaw("DATE_FORMAT(sale_date,'%Y-%m') ym, SUM(total) t")->groupBy('ym')->pluck('t', 'ym');
            $c = $from->copy();
            while ($c <= $to) {
                $labels[]  = ucfirst($c->translatedFormat('M Y'));
                $single[]  = (float) ($rows[$c->format('Y-m')] ?? 0);
                $c->addMonth();
            }
            $mode = 'single';
            $highlight = count($single) - 1;
            $prevLabel = 'Mes anterior'; $curLabel = 'Mes actual'; $note = 'Comparado con el mes anterior';
        }

        if ($mode === 'single') {
            $totalCur  = (float) $this->scopedSales($cid)->whereBetween('sale_date', $curRange)->sum('total');
            $totalPrev = (float) $this->scopedSales($cid)->whereBetween('sale_date', $prevRange)->sum('total');
        } else {
            $totalCur  = array_sum($curData);
            $totalPrev = array_sum($prevData);
        }
        $profitCur  = $this->salesProfit($cid, $curRange);
        $profitPrev = $this->salesProfit($cid, $prevRange);

        return [
            'mode'      => $mode,
            'labels'    => $labels,
            'cur'       => $curData,
            'prev'      => $prevData,
            'data'      => $single,
            'highlight' => $highlight,
            'prevLabel' => $prevLabel,
            'curLabel'  => $curLabel,
            'note'      => $note,
            'totalCur'  => $totalCur,  'totalPrev'  => $totalPrev,  'totalPct'  => $this->pct($totalCur, $totalPrev),
            'profitCur' => $profitCur, 'profitPrev' => $profitPrev, 'profitPct' => $this->pct($profitCur, $profitPrev),
        ];
    }

    private function fmt(float $n): string
    {
        return 'Bs. ' . number_format($n, 2);
    }

    // ── VENTAS ─────────────────────────────────────────────────
    private function salesStats(?int $cid, Carbon $base, array $cur, array $prev): array
    {
        $curTotal  = (float) $this->scopedSales($cid)->whereBetween('sale_date', $cur)->sum('total');
        $prevTotal = (float) $this->scopedSales($cid)->whereBetween('sale_date', $prev)->sum('total');
        $curCount  = (int) $this->scopedSales($cid)->whereBetween('sale_date', $cur)->count();
        $cash      = (float) $this->scopedSales($cid)->whereBetween('sale_date', $cur)->where('sale_type', 'cash')->sum('total');
        $credit    = (float) $this->scopedSales($cid)->whereBetween('sale_date', $cur)->where('sale_type', 'credit')->sum('total');
        $avg       = $curCount > 0 ? $curTotal / $curCount : 0;
        $creditPct = $curTotal > 0 ? round($credit / $curTotal * 100, 1) : 0;
        $pctTotal  = $this->pct($curTotal, $prevTotal);

        // Por cobrar (crédito pendiente, vigente)
        $porCobrar = (float) $this->scopedSales($cid)->where('sale_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get(['total', 'paid_amount'])->sum(fn ($s) => (float) $s->total - (float) $s->paid_amount);

        // Devoluciones del período
        $devol = (float) SaleReturn::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereBetween('return_date', $cur)->sum('total');

        // Top productos (mes)
        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->when($cid, fn ($q) => $q->where('sales.company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->whereBetween('sales.sale_date', $cur)
            ->selectRaw('products.name as name, SUM(sale_items.subtotal) as total')
            ->groupBy('products.id', 'products.name')->orderByDesc('total')->limit(6)->get();

        $trend = $this->trendSeries($this->scopedSales($cid), 'sale_date', 'SUM(total)', $base);

        // Insights
        $ins = [];
        if ($curCount === 0) {
            $ins[] = $this->insight('info', 'Sin ventas en el período', 'No hay ventas registradas este período. Revisa la actividad comercial.');
        } else {
            if ($pctTotal >= 5)      $ins[] = $this->insight('good', 'Ventas en alza (+' . $pctTotal . '%)', 'Las ventas crecieron respecto al período anterior (' . $this->fmt($prevTotal) . ' → ' . $this->fmt($curTotal) . ').');
            elseif ($pctTotal <= -10) $ins[] = $this->insight('danger', 'Caída de ventas (' . $pctTotal . '%)', 'Las ventas bajaron frente al período anterior. Considera promociones o revisar el equipo de ventas.');
            else                      $ins[] = $this->insight('info', 'Ventas estables', 'Variación de ' . $pctTotal . '% respecto al período anterior.');

            if ($creditPct >= 40)    $ins[] = $this->insight('warning', 'Alta proporción a crédito (' . $creditPct . '%)', 'Mucha venta a crédito puede tensar el flujo de caja; refuerza la cobranza.');
            if ($topProducts->count()) $ins[] = $this->insight('info', 'Producto estrella', $topProducts->first()->name . ' es el más vendido del período (' . $this->fmt((float) $topProducts->first()->total) . ').');
        }
        if ($porCobrar > 0) $ins[] = $this->insight($porCobrar > $curTotal ? 'danger' : 'warning', 'Cuentas por cobrar: ' . $this->fmt($porCobrar), 'Tienes saldo pendiente de cobro en ventas a crédito. Prioriza la gestión de cobranza.');

        return [
            'kpis' => [
                ['label' => 'Ventas del período', 'value' => $this->fmt($curTotal), 'pct' => $pctTotal],
                ['label' => 'N° de ventas',   'value' => number_format($curCount)],
                ['label' => 'Ticket promedio','value' => $this->fmt($avg)],
                ['label' => '% a crédito',    'value' => $creditPct . '%'],
                ['label' => 'Devoluciones',   'value' => $this->fmt($devol)],
                ['label' => 'Por cobrar',     'value' => $this->fmt($porCobrar)],
            ],
            'trend'       => $trend,
            'cashCredit'  => ['cash' => $cash, 'credit' => $credit],
            'topProducts' => ['labels' => $topProducts->pluck('name'), 'data' => $topProducts->pluck('total')],
            'comparison'  => $this->salesComparison($cid, $this->period, $base, $cur, $prev),
            'insights'    => $ins,
        ];
    }

    // ── PERSONAL (vendedores) ─────────────────────────────────
    private function staffStats(?int $cid, array $cur): array
    {
        $rows = $this->scopedSales($cid)->whereBetween('sale_date', $cur)
            ->selectRaw('created_by, COUNT(*) c, SUM(total) t')
            ->groupBy('created_by')->orderByDesc('t')->get();

        $names = User::whereIn('id', $rows->pluck('created_by')->filter())->pluck('name', 'id');
        $total = (float) $rows->sum('t');

        $ranking = $rows->map(fn ($r) => [
            'name'  => $names[$r->created_by] ?? 'Sistema',
            'count' => (int) $r->c,
            'total' => (float) $r->t,
            'pct'   => $total > 0 ? round((float) $r->t / $total * 100, 1) : 0,
        ])->values();

        // Vendedores con ventas históricas pero inactivos este período
        $activeIds   = $rows->pluck('created_by')->filter()->all();
        $allSellerIds = $this->scopedSales($cid)->distinct()->pluck('created_by')->filter()->all();
        $idleCount   = count(array_diff($allSellerIds, $activeIds));

        $ins = [];
        if ($ranking->isEmpty()) {
            $ins[] = $this->insight('info', 'Sin actividad de vendedores', 'No hay ventas asignadas a personal este período.');
        } else {
            $top = $ranking->first();
            $ins[] = $this->insight('good', 'Mejor vendedor: ' . $top['name'], $top['name'] . ' generó ' . $this->fmt($top['total']) . ' (' . $top['pct'] . '% del total) en ' . $top['count'] . ' ventas.');
            if ($top['pct'] >= 50 && $ranking->count() > 1) {
                $ins[] = $this->insight('warning', 'Ventas concentradas', $top['pct'] . '% de las ventas dependen de una sola persona. Distribuir reduce el riesgo.');
            }
            if ($idleCount > 0) {
                $ins[] = $this->insight('warning', $idleCount . ' vendedor(es) sin ventas este período', 'Personal que vendió antes pero no este período. Revisa motivación o asignación.');
            }
        }

        return [
            'kpis' => [
                ['label' => 'Vendedores activos', 'value' => $ranking->count()],
                ['label' => 'Ventas del equipo',  'value' => $this->fmt($total)],
                ['label' => 'Inactivos del período',  'value' => $idleCount],
            ],
            'ranking'   => $ranking,
            'chart'     => ['labels' => $ranking->pluck('name'), 'data' => $ranking->pluck('total')],
            'insights'  => $ins,
        ];
    }

    // ── CLIENTES ───────────────────────────────────────────────
    private function clientsStats(?int $cid, Carbon $base, array $cur, array $prev): array
    {
        $clientsQ = Client::query()->when($cid, fn ($q) => $q->where('company_id', $cid));
        $newCur   = (int) (clone $clientsQ)->whereBetween('created_at', $cur)->count();
        $newPrev  = (int) (clone $clientsQ)->whereBetween('created_at', $prev)->count();
        $totalCli = (int) (clone $clientsQ)->where('active', true)->count();
        $pctNew   = $this->pct($newCur, $newPrev);

        // Top compradores (mes)
        $topBuyers = $this->scopedSales($cid)->whereBetween('sale_date', $cur)->whereNotNull('client_id')
            ->selectRaw('client_id, SUM(total) t')->groupBy('client_id')->orderByDesc('t')->limit(6)->get();
        $buyerNames = Client::whereIn('id', $topBuyers->pluck('client_id'))->pluck('full_name', 'id');
        $topBuyers  = $topBuyers->map(fn ($r) => ['name' => $buyerNames[$r->client_id] ?? 'Cliente', 'total' => (float) $r->t]);

        // Deudores (crédito pendiente)
        $debtors = $this->scopedSales($cid)->where('sale_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])->whereNotNull('client_id')
            ->get(['client_id', 'total', 'paid_amount']);
        $debtorCount = $debtors->pluck('client_id')->unique()->count();
        $debtTotal   = (float) $debtors->sum(fn ($s) => (float) $s->total - (float) $s->paid_amount);

        // Inactivos: con ventas pero ninguna en 60 días
        $cutoff = Carbon::now()->subDays(60);
        $lastSale = $this->scopedSales($cid)->whereNotNull('client_id')
            ->selectRaw('client_id, MAX(sale_date) last')->groupBy('client_id')->pluck('last', 'client_id');
        $inactive = collect($lastSale)->filter(fn ($d) => Carbon::parse($d)->lt($cutoff))->count();

        $newTrend = $this->trendSeries((clone $clientsQ), 'created_at', 'COUNT(*)', $base);

        $ins = [];
        if ($newCur > 0) $ins[] = $this->insight($pctNew >= 0 ? 'good' : 'warning', $newCur . ' clientes nuevos', 'Captación de ' . $newCur . ' clientes este período (' . ($pctNew >= 0 ? '+' : '') . $pctNew . '% vs período anterior).');
        else             $ins[] = $this->insight('warning', 'Sin clientes nuevos', 'No se registraron clientes nuevos este período. Refuerza la captación.');
        if ($debtorCount > 0) $ins[] = $this->insight('danger', $debtorCount . ' clientes con deuda (' . $this->fmt($debtTotal) . ')', 'Hay saldos de crédito por cobrar. Contacta a estos clientes.');
        if ($inactive > 0)    $ins[] = $this->insight('warning', $inactive . ' clientes inactivos', 'Clientes sin compras en más de 60 días. Una campaña de reactivación puede recuperarlos.');

        return [
            'kpis' => [
                ['label' => 'Clientes nuevos', 'value' => $newCur, 'pct' => $pctNew],
                ['label' => 'Clientes activos','value' => number_format($totalCli)],
                ['label' => 'Con deuda',       'value' => $debtorCount],
                ['label' => 'Inactivos (60d)', 'value' => $inactive],
            ],
            'newTrend'  => $newTrend,
            'topBuyers' => ['labels' => $topBuyers->pluck('name'), 'data' => $topBuyers->pluck('total')],
            'insights'  => $ins,
        ];
    }

    // ── COMPRAS ────────────────────────────────────────────────
    private function purchasesStats(?int $cid, Carbon $base, array $cur, array $prev, ?float $salesCur = null): array
    {
        $pQ = Purchase::query()->when($cid, fn ($q) => $q->where('company_id', $cid));
        $curTotal  = (float) (clone $pQ)->whereBetween('purchase_date', $cur)->sum('total');
        $prevTotal = (float) (clone $pQ)->whereBetween('purchase_date', $prev)->sum('total');
        $count     = (int) (clone $pQ)->whereBetween('purchase_date', $cur)->count();
        $pctTotal  = $this->pct($curTotal, $prevTotal);

        $porPagar = (float) (clone $pQ)->whereIn('payment_status', ['pending', 'partial'])
            ->get(['total', 'paid_amount'])->sum(fn ($p) => (float) $p->total - (float) $p->paid_amount);

        $topSuppliers = (clone $pQ)->whereBetween('purchase_date', $cur)->whereNotNull('supplier_id')
            ->selectRaw('supplier_id, SUM(total) t')->groupBy('supplier_id')->orderByDesc('t')->limit(6)->get();
        $supNames = Supplier::whereIn('id', $topSuppliers->pluck('supplier_id'))->pluck('name', 'id');
        $topSuppliers = $topSuppliers->map(fn ($r) => ['name' => $supNames[$r->supplier_id] ?? 'Proveedor', 'total' => (float) $r->t]);

        $trend = $this->trendSeries((clone $pQ), 'purchase_date', 'SUM(total)', $base);

        $salesCur = $salesCur ?? (float) $this->scopedSales($cid)->whereBetween('sale_date', $cur)->sum('total');
        $ratio    = $salesCur > 0 ? round($curTotal / $salesCur * 100, 1) : 0;

        $ins = [];
        if ($porPagar > 0) $ins[] = $this->insight('warning', 'Cuentas por pagar: ' . $this->fmt($porPagar), 'Tienes obligaciones pendientes con proveedores. Planifica los pagos para no afectar la relación comercial.');
        if ($count > 0 && $topSuppliers->count()) $ins[] = $this->insight('info', 'Proveedor principal', $topSuppliers->first()['name'] . ' concentra tus compras del período (' . $this->fmt($topSuppliers->first()['total']) . ').');
        if ($salesCur > 0) {
            if ($ratio > 80) $ins[] = $this->insight('danger', 'Compras altas vs ventas (' . $ratio . '%)', 'Las compras representan un % alto de las ventas; vigila el margen y el sobre-stock.');
            else             $ins[] = $this->insight('good', 'Relación compra/venta saludable (' . $ratio . '%)', 'Las compras del período son ' . $ratio . '% de las ventas.');
        }

        return [
            'kpis' => [
                ['label' => 'Compras del período', 'value' => $this->fmt($curTotal), 'pct' => $pctTotal, 'invert' => true],
                ['label' => 'N° de compras',   'value' => number_format($count)],
                ['label' => 'Por pagar',       'value' => $this->fmt($porPagar)],
                ['label' => 'Compra/venta',    'value' => $ratio . '%'],
            ],
            'trend'        => $trend,
            'topSuppliers' => ['labels' => $topSuppliers->pluck('name'), 'data' => $topSuppliers->pluck('total')],
            'insights'     => $ins,
        ];
    }

    // ── INVENTARIO ─────────────────────────────────────────────
    private function inventoryStats(?int $cid): array
    {
        $agg = Product::query()->when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)
            ->selectRaw('COUNT(*) n, COALESCE(SUM(current_stock),0) units, COALESCE(SUM(current_stock*cost),0) vc, COALESCE(SUM(current_stock*price),0) vp')
            ->first();

        $noStock = (int) Product::query()->when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)
            ->where('current_stock', '<=', 0)->count();
        $lowStock = (int) Product::query()->when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)
            ->where('min_stock', '>', 0)->whereColumn('current_stock', '<=', 'min_stock')->where('current_stock', '>', 0)->count();
        $okStock  = max(0, (int) $agg->n - $noStock - $lowStock);

        // Capital inmovilizado: productos sin movimiento en 90 días, con stock
        $cutoff = Carbon::now()->subDays(90);
        $movedIds = InventoryMovement::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('movement_date', '>=', $cutoff)->distinct()->pluck('product_id')->all();
        $stale = Product::query()->when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)
            ->where('current_stock', '>', 0)->whereNotIn('id', $movedIds ?: [0]);
        $staleCount = (int) (clone $stale)->count();
        $staleValue = (float) (clone $stale)->selectRaw('COALESCE(SUM(current_stock*cost),0) v')->value('v');

        $ins = [];
        $ins[] = $this->insight('info', 'Valor del inventario: ' . $this->fmt((float) $agg->vc), 'A precio de venta sería ' . $this->fmt((float) $agg->vp) . ' (ganancia potencial ' . $this->fmt((float) $agg->vp - (float) $agg->vc) . ').');
        if ($noStock > 0)  $ins[] = $this->insight('danger', $noStock . ' productos sin stock', 'Productos en cero. Reponer los de mayor rotación para no perder ventas.');
        if ($lowStock > 0) $ins[] = $this->insight('warning', $lowStock . ' productos con stock bajo', 'Por debajo del mínimo. Conviene generar órdenes de compra.');
        if ($staleCount > 0) $ins[] = $this->insight('warning', 'Capital inmovilizado: ' . $this->fmt($staleValue), $staleCount . ' productos sin movimiento en 90 días. Evalúa promociones o liquidación.');

        return [
            'kpis' => [
                ['label' => 'Productos',     'value' => number_format((int) $agg->n)],
                ['label' => 'Unidades',      'value' => number_format((float) $agg->units)],
                ['label' => 'Valor a costo', 'value' => $this->fmt((float) $agg->vc)],
                ['label' => 'Valor a venta', 'value' => $this->fmt((float) $agg->vp)],
                ['label' => 'Sin stock',     'value' => $noStock],
                ['label' => 'Bajo stock',    'value' => $lowStock],
            ],
            'distribution' => ['ok' => $okStock, 'low' => $lowStock, 'none' => $noStock],
            'insights'     => $ins,
        ];
    }

    // ── TALLER ─────────────────────────────────────────────────
    private function workshopStats(?int $cid, array $cur, array $prev): array
    {
        $woQ = WorkOrder::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));

        $byStatus = (clone $woQ)->whereNotIn('status', ['anulada'])
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        $open = (int) (clone $woQ)->whereIn('status', ['recibida', 'diagnosticada', 'en_proceso', 'terminada'])->count();

        $incomeCur  = (float) (clone $woQ)->where('status', 'entregada')->whereBetween('delivered_at', $cur)->sum('total');
        $incomePrev = (float) (clone $woQ)->where('status', 'entregada')->whereBetween('delivered_at', $prev)->sum('total');
        $pctIncome  = $this->pct($incomeCur, $incomePrev);

        // OT estancadas: abiertas con recepción > 7 días
        $stale = (int) (clone $woQ)->whereIn('status', ['recibida', 'diagnosticada', 'en_proceso'])
            ->whereDate('reception_date', '<', Carbon::now()->subDays(7))->count();

        $byMechanic = (clone $woQ)->whereBetween('reception_date', $cur)->whereNotNull('mechanic_id')
            ->selectRaw('mechanic_id, COUNT(*) c')->groupBy('mechanic_id')->orderByDesc('c')->limit(6)->get();
        $mechNames = Mechanic::whereIn('id', $byMechanic->pluck('mechanic_id'))->pluck('name', 'id');
        $byMechanic = $byMechanic->map(fn ($r) => ['name' => $mechNames[$r->mechanic_id] ?? 'Mecánico', 'count' => (int) $r->c]);

        $statusLabels = WorkOrder::STATUSES;
        $distLabels = []; $distData = [];
        foreach ($byStatus as $st => $c) { $distLabels[] = $statusLabels[$st]['label'] ?? $st; $distData[] = (int) $c; }

        $ins = [];
        $ins[] = $this->insight('info', $open . ' órdenes abiertas', 'Órdenes de trabajo en proceso o por entregar actualmente.');
        if ($stale > 0) $ins[] = $this->insight('warning', $stale . ' OT estancadas (+7 días)', 'Órdenes sin avanzar hace más de una semana. Revisa cuellos de botella en el taller.');
        if ($incomeCur > 0) $ins[] = $this->insight($pctIncome >= 0 ? 'good' : 'danger', 'Ingresos de taller: ' . $this->fmt($incomeCur), 'Variación de ' . $pctIncome . '% respecto al período anterior.');

        return [
            'kpis' => [
                ['label' => 'OT abiertas',       'value' => $open],
                ['label' => 'Entregadas',  'value' => (int) ($byStatus['entregada'] ?? 0)],
                ['label' => 'Ingresos del período',  'value' => $this->fmt($incomeCur), 'pct' => $pctIncome],
                ['label' => 'Estancadas',        'value' => $stale],
            ],
            'distribution' => ['labels' => $distLabels, 'data' => $distData],
            'byMechanic'   => $byMechanic,
            'insights'     => $ins,
        ];
    }

    // ── ALQUILERES ─────────────────────────────────────────────
    private function rentalsStats(?int $cid, array $cur, array $prev): array
    {
        $unitQ = MotoUnit::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId));
        $disp  = (int) (clone $unitQ)->where('status', 'disponible')->count();
        $alq   = (int) (clone $unitQ)->where('status', 'alquilada')->count();
        $mant  = (int) (clone $unitQ)->where('status', 'mantenimiento')->count();
        $fleet = $disp + $alq + $mant;
        $ocupacion = $fleet > 0 ? round($alq / $fleet * 100, 1) : 0;

        $payQ = RentalPayment::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->whereHas('contract', fn ($w) => $w->where('branch_id', $this->branchId)))
            ->whereIn('type', ['alquiler', 'penalizacion']);
        $incomeCur  = (float) (clone $payQ)->whereBetween('payment_date', $cur)->sum('amount');
        $incomePrev = (float) (clone $payQ)->whereBetween('payment_date', $prev)->sum('amount');
        $pctIncome  = $this->pct($incomeCur, $incomePrev);

        // Cuotas vencidas
        $overdueQ = RentalInstallment::query()->whereIn('status', ['pendiente', 'parcial'])
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->whereHas('contract', function ($q) use ($cid) {
                $q->whereIn('status', ['contrato', 'entregada'])
                  ->when($cid, fn ($w) => $w->where('company_id', $cid))
                  ->when($this->branchId, fn ($w) => $w->where('branch_id', $this->branchId));
            });
        $overdueCount = (int) (clone $overdueQ)->count();
        $overdueAmt   = (float) (clone $overdueQ)->selectRaw('COALESCE(SUM(amount - paid_amount),0) v')->value('v');

        $deposits = (float) RentalContract::query()->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->whereIn('status', ['contrato', 'entregada'])->where('deposit_status', 'retenido')->sum('deposit');

        $ins = [];
        if ($fleet > 0) $ins[] = $this->insight($ocupacion >= 60 ? 'good' : 'info', 'Ocupación de flota: ' . $ocupacion . '%', $alq . ' de ' . $fleet . ' motos en alquiler. ' . ($ocupacion < 40 ? 'Hay capacidad ociosa; impulsa promociones de renta.' : 'Buen aprovechamiento de la flota.'));
        if ($overdueCount > 0) $ins[] = $this->insight('danger', $overdueCount . ' cuotas de renta vencidas (' . $this->fmt($overdueAmt) . ')', 'Gestiona el cobro de las cuotas atrasadas en la sección Cobros.');
        if ($incomeCur > 0) $ins[] = $this->insight($pctIncome >= 0 ? 'good' : 'warning', 'Ingresos por renta: ' . $this->fmt($incomeCur), 'Variación de ' . $pctIncome . '% vs período anterior.');

        return [
            'kpis' => [
                ['label' => 'Ocupación',        'value' => $ocupacion . '%'],
                ['label' => 'En alquiler',      'value' => $alq],
                ['label' => 'Ingresos del período', 'value' => $this->fmt($incomeCur), 'pct' => $pctIncome],
                ['label' => 'Cuotas vencidas',  'value' => $overdueCount],
                ['label' => 'Depósitos',        'value' => $this->fmt($deposits)],
            ],
            'fleet'    => ['disp' => $disp, 'alq' => $alq, 'mant' => $mant],
            'insights' => $ins,
        ];
    }

    // ── RESUMEN GENERAL ────────────────────────────────────────
    private function summaryStats(array $stats): array
    {
        // KPIs globales (toma los primeros de cada dominio)
        $pick = fn ($tab, $i) => $stats[$tab]['kpis'][$i] ?? null;
        $kpis = array_values(array_filter([
            $pick('ventas', 0),
            $pick('compras', 0),
            $pick('clientes', 0),
            $pick('inventario', 2),
            $pick('taller', 2),
            $pick('alquileres', 2),
        ]));

        // Top recomendaciones: ordenar por severidad (danger > warning > good > info)
        $rank = ['danger' => 0, 'warning' => 1, 'good' => 2, 'info' => 3];
        $all = [];
        foreach (['ventas', 'personal', 'clientes', 'compras', 'inventario', 'taller', 'alquileres'] as $tab) {
            foreach ($stats[$tab]['insights'] as $ins) {
                $ins['tab'] = $tab;
                $all[] = $ins;
            }
        }
        usort($all, fn ($a, $b) => ($rank[$a['severity']] ?? 9) <=> ($rank[$b['severity']] ?? 9));
        $topInsights = array_slice($all, 0, 8);

        return ['kpis' => $kpis, 'insights' => $topInsights];
    }
}
