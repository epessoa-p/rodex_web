<?php

namespace App\Services\Dashboard;

use App\Models\Company;
use App\Models\Product;
use App\Models\Sales\Sale;
use App\Models\User;
use App\Models\Workshop\Appointment;
use App\Models\Workshop\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Resumen operativo del día de una empresa: el mismo que ve el móvil en su
 * Dashboard (Ventas hoy, OTs hoy, motos en taller, citas, stock, OTs por
 * estado, próxima cita, top servicios del mes y OTs recientes). Lo consumen
 * `Api\DashboardController::overview()` y el dashboard web.
 *
 * Cada sección se incluye solo si el plan tiene el módulo Y el usuario tiene
 * el permiso de ese dashboard; si no, va en null y la vista no la pinta.
 * Las consultas asumen el tenant activo (global scope) = $company.
 */
class OperationalOverviewService
{
    public const ACTIVE_WO = ['recibida', 'diagnosticada', 'en_proceso', 'terminada'];

    public function build(User $user, ?Company $company): array
    {
        $can = fn (string $module, string $perm) => $company
            && $company->planAllows($module)
            && ($user->is_super_admin || $user->hasPermissionInCompany($perm, $company));

        $sales    = $can('sales', 'sales-dashboard.view');
        $workshop = $can('workshop', 'workshop-dashboard.view');
        $stock    = $company?->planAllows('inventory')
            && ($can('sales', 'sales-dashboard.view') || $can('purchases', 'purchases-dashboard.view'));

        return [
            'date'     => today()->toDateString(),
            'sales'    => $sales ? $this->salesToday() : null,
            'workshop' => $workshop ? $this->workshopOverview($company->id) : null,
            'stock'    => $stock ? $this->stockOverview() : null,
        ];
    }

    private function salesToday(): array
    {
        $row = Sale::where('status', 'completed')
            ->whereDate('sale_date', today())
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as amount')
            ->first();

        return ['count' => (int) $row->cnt, 'total' => (float) $row->amount];
    }

    private function workshopOverview(int $companyId): array
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
            'top_services'     => $this->topServicesThisMonth($companyId),
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
                'client' => $next->display_name,
                'title'  => $next->title ?: $next->service?->name,
            ] : null,
        ];
    }

    /** Top 5 servicios por ingreso en el mes (mano de obra de OTs entregadas). */
    private function topServicesThisMonth(int $companyId): array
    {
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

    /** Últimas 5 OTs, con las mismas claves que Api\WorkOrderController::summary(). */
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
                'status_color'   => $o->status_color,
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
}
