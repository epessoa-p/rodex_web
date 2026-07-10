<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoUnit;
use App\Models\Rentals\RentalContract;
use App\Models\Rentals\RentalInstallment;
use App\Models\Rentals\RentalPayment;
use Illuminate\Support\Carbon;

class RentalDashboardController extends Controller
{
    public function index()
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        $today      = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $contracts = fn () => RentalContract::query()->when($cid, fn ($q) => $q->where('company_id', $cid));
        $units     = fn () => MotoUnit::query()->when($cid, fn ($q) => $q->where('company_id', $cid));

        $activeRentals = $contracts()->where('status', 'entregada')->count();
        $reservations  = $contracts()->where('status', 'reservada')->count();
        $depositsHeld  = $contracts()
            ->whereIn('status', ['entregada', 'contrato'])
            ->where('deposit_status', 'retenido')
            ->sum('deposit');

        $monthIncome = RentalPayment::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('type', ['alquiler', 'penalizacion'])
            ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        // Flota de motos
        $fleetAvailable   = $units()->where('status', 'disponible')->count();
        $fleetRented      = $units()->where('status', 'alquilada')->count();
        $fleetMaintenance = $units()->where('status', 'mantenimiento')->count();

        // Próximas reservas (start_date >= hoy)
        $upcoming = $contracts()
            ->with(['client', 'motoUnit.model.brand'])
            ->where('status', 'reservada')
            ->whereDate('start_date', '>=', $today)
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        // Devoluciones esperadas hoy o vencidas
        $dueReturns = $contracts()
            ->with(['client', 'motoUnit.model.brand'])
            ->where('status', 'entregada')
            ->whereDate('end_date', '<=', $today)
            ->orderBy('end_date')
            ->limit(8)
            ->get();

        // ── Cuotas de renta: vencidas y próximas (Fase 2) ──────────
        $installments = fn () => RentalInstallment::query()
            ->whereIn('status', ['pendiente', 'parcial'])
            ->whereHas('contract', function ($q) use ($cid) {
                $q->whereIn('status', ['contrato', 'entregada'])
                  ->when($cid, fn ($w) => $w->where('company_id', $cid));
            });

        $overdueCount  = $installments()->whereDate('due_date', '<', $today->toDateString())->count();
        $overdueAmount = $installments()->whereDate('due_date', '<', $today->toDateString())
            ->selectRaw('COALESCE(SUM(amount - paid_amount),0) as bal')->value('bal');

        $soon = Carbon::today()->addDays(3)->toDateString();
        $upcomingDueCount = $installments()
            ->whereDate('due_date', '>=', $today->toDateString())
            ->whereDate('due_date', '<=', $soon)
            ->count();

        $overdueList = $installments()
            ->with(['contract.client', 'contract.motoUnit.model.brand'])
            ->whereDate('due_date', '<', $today->toDateString())
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $upcomingInstallments = $installments()
            ->with(['contract.client', 'contract.motoUnit.model.brand'])
            ->whereDate('due_date', '>=', $today->toDateString())
            ->whereDate('due_date', '<=', $soon)
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        return view('rentals.dashboard.index', compact(
            'activeRentals', 'reservations', 'depositsHeld', 'monthIncome',
            'fleetAvailable', 'fleetRented', 'fleetMaintenance',
            'upcoming', 'dueReturns',
            'overdueCount', 'overdueAmount', 'upcomingDueCount',
            'overdueList', 'upcomingInstallments'
        ));
    }
}
