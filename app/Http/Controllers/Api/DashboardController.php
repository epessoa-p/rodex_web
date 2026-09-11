<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Sale;
use App\Models\Workshop\Appointment;
use App\Models\Workshop\WorkOrder;
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

    /** Estados de OT "en taller" (aún no entregada ni anulada). */
    private const ACTIVE_WO = ['recibida', 'diagnosticada', 'en_proceso', 'terminada'];

    /**
     * Resumen operativo de toda la empresa. Cada sección se incluye solo si el
     * plan tiene el módulo Y el usuario tiene el permiso de ese dashboard; si
     * no, va en null y el móvil no pinta la tarjeta.
     */
    public function overview(Request $request)
    {
        $user    = $request->user();
        $company = $request->attributes->get('tenant_company');

        $can = fn (string $module, string $perm) => $company
            && $company->planAllows($module)
            && ($user->is_super_admin || $user->hasPermissionInCompany($perm, $company));

        $sales    = $can('sales', 'sales-dashboard.view');
        $workshop = $can('workshop', 'workshop-dashboard.view');
        $stock    = $company?->planAllows('inventory')
            && ($can('sales', 'sales-dashboard.view') || $can('purchases', 'purchases-dashboard.view'));

        return response()->json(['data' => [
            'date'     => today()->toDateString(),
            'sales'    => $sales ? $this->salesToday() : null,
            'workshop' => $workshop ? $this->workshopOverview() : null,
            'stock'    => $stock ? $this->stockOverview() : null,
        ]]);
    }

    private function salesToday(): array
    {
        $row = Sale::where('status', 'completed')
            ->whereDate('sale_date', today())
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as amount')
            ->first();

        return ['count' => (int) $row->cnt, 'total' => (float) $row->amount];
    }

    private function workshopOverview(): array
    {
        $active = WorkOrder::whereIn('status', self::ACTIVE_WO);

        $byStatus = (clone $active)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return [
            'received_today'   => WorkOrder::whereDate('reception_date', today())->count(),
            'active'           => (int) $byStatus->sum(),
            'vehicles_in_shop' => (clone $active)->whereNotNull('vehicle_id')->distinct('vehicle_id')->count('vehicle_id'),
            'by_status'        => collect(self::ACTIVE_WO)
                ->mapWithKeys(fn ($s) => [$s => (int) ($byStatus[$s] ?? 0)])
                ->all(),
            'appointments'     => $this->appointmentsToday(),
            'top_services'     => $this->topServicesThisMonth(),
            'recent'           => $this->recentWorkOrders(),
        ];
    }

    private function appointmentsToday(): array
    {
        $today = Appointment::whereDate('scheduled_at', today())
            ->whereIn('status', ['programada', 'confirmada']);

        $next = (clone $today)->with(['client', 'service'])
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->first();

        return [
            'total'   => (clone $today)->count(),
            'pending' => (clone $today)->where('scheduled_at', '>=', now())->count(),
            'next'    => $next ? [
                'id'     => $next->id,
                'time'   => $next->scheduled_at?->format('H:i'),
                'client' => $next->client?->full_name,
                'title'  => $next->title ?: $next->service?->name,
            ] : null,
        ];
    }

    /** Top 5 servicios por ingreso en el mes (mano de obra de OTs entregadas). */
    private function topServicesThisMonth(): array
    {
        $companyId = app(\App\Support\Tenancy::class)->id();

        return DB::table('work_order_services as s')
            ->join('work_orders as o', 'o.id', '=', 's.work_order_id')
            ->leftJoin('services as sv', 'sv.id', '=', 's.service_id')
            ->where('o.company_id', $companyId)
            ->where('o.status', 'entregada')
            ->whereNull('o.deleted_at')
            ->where('o.delivered_at', '>=', today()->startOfMonth())
            ->selectRaw('COALESCE(sv.name, s.description) as label, SUM(s.subtotal) as amount, COUNT(*) as cnt')
            ->groupBy('label')
            ->orderByDesc('amount')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'label'  => $r->label,
                'amount' => (float) $r->amount,
                'count'  => (int) $r->cnt,
            ])->values()->all();
    }

    /** Últimas 5 OTs, con las mismas claves que WorkOrderController::summary(). */
    private function recentWorkOrders(): array
    {
        return WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->where('status', '!=', 'anulada')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (WorkOrder $o) => [
                'id'             => $o->id,
                'code'           => $o->code,
                'status'         => $o->status,
                'status_label'   => $o->status_label,
                'payment_status' => $o->payment_status,
                'total'          => (float) $o->total,
                'balance'        => (float) $o->balance,
                'client'         => $o->client?->full_name,
                'client_phone'   => $o->client?->phone,
                'vehicle'        => $o->vehicle?->display_name,
                'mechanic'       => $o->mechanic?->name,
                'reception_date' => $o->reception_date?->toIso8601String(),
            ])->values()->all();
    }

    private function stockOverview(): array
    {
        $active = Product::where('active', true);

        return [
            'in_stock'  => (clone $active)->where('current_stock', '>', 0)->count(),
            'low_stock' => (clone $active)->where('min_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'min_stock')->count(),
        ];
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
