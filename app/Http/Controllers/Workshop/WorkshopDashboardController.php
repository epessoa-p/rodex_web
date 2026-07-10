<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\WorkOrder;

class WorkshopDashboardController extends Controller
{
    public function index()
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;
        $scope = fn ($q) => $cid ? $q->where('company_id', $cid) : $q;

        // OT activas por estado
        $statusCounts = WorkOrder::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereNotIn('status', ['entregada', 'anulada'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeCount = $statusCounts->sum();

        // Entregadas del mes
        $deliveredThisMonth = WorkOrder::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'entregada')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();

        // Ingresos del mes (pagos de OT)
        $incomeThisMonth = \App\Models\Workshop\WorkOrderPayment::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // Por cobrar (saldos de OT entregadas a crédito)
        $receivable = WorkOrder::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('payment_status', ['pendiente', 'parcial'])
            ->where('status', 'entregada')
            ->get()
            ->sum(fn ($o) => $o->total - $o->paid_amount);

        // Top mecánicos (por # OT)
        $topMechanics = Mechanic::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->withCount('workOrders')
            ->orderByDesc('work_orders_count')
            ->limit(5)
            ->get();

        // OT recientes
        $recentOrders = WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest()
            ->limit(8)
            ->get();

        return view('workshop.dashboard.index', compact(
            'statusCounts', 'activeCount', 'deliveredThisMonth', 'incomeThisMonth',
            'receivable', 'topMechanics', 'recentOrders'
        ));
    }
}
