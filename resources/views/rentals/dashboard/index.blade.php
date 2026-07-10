@extends('layouts.app')
@section('title', 'Alquileres · Dashboard')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-speedometer2 me-2 text-danger"></i>Alquileres</h1>
            <p class="text-muted mb-0 small">Panel general del módulo de alquiler de motos.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('rentals.calendar') }}" class="btn btn-light border"><i class="bi bi-calendar3 me-1"></i>Calendario</a>
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.create', auth()->user()->getCurrentCompany()))
            <a href="{{ route('rentals.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva reserva</a>
            @endif
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1"><i class="bi bi-bicycle"></i> En alquiler</div>
                    <div class="fs-3 fw-bold">{{ $activeRentals }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1"><i class="bi bi-bookmark-plus"></i> Reservas</div>
                    <div class="fs-3 fw-bold">{{ $reservations }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1"><i class="bi bi-cash-coin"></i> Ingresos del mes</div>
                    <div class="fs-3 fw-bold">Bs. {{ number_format($monthIncome, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1"><i class="bi bi-safe2"></i> Depósitos retenidos</div>
                    <div class="fs-3 fw-bold">Bs. {{ number_format($depositsHeld, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas de cobros de renta --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <a href="{{ route('rentals.collections', ['due' => 'vencidas']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 {{ $overdueCount > 0 ? 'border-start border-danger border-4' : '' }}">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="d-flex align-items-center gap-2 text-muted small mb-1"><i class="bi bi-exclamation-octagon {{ $overdueCount > 0 ? 'text-danger' : '' }}"></i> Cuotas vencidas</div>
                            <div class="fs-4 fw-bold {{ $overdueCount > 0 ? 'text-danger' : '' }}">{{ $overdueCount }}</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Saldo vencido</div>
                            <div class="fw-semibold">Bs. {{ number_format($overdueAmount, 2) }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('rentals.collections', ['due' => 'todas']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><i class="bi bi-alarm me-1 text-warning"></i>Cuotas que vencen en ≤3 días</span>
                        <span class="fs-4 fw-bold">{{ $upcomingDueCount }}</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Estado de flota --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-check-circle text-success me-1"></i>Disponibles</span>
                    <span class="fs-4 fw-bold">{{ $fleetAvailable }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-bicycle text-warning me-1"></i>Alquiladas</span>
                    <span class="fs-4 fw-bold">{{ $fleetRented }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted small"><i class="bi bi-tools text-primary me-1"></i>En mantenimiento</span>
                    <span class="fs-4 fw-bold">{{ $fleetMaintenance }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Próximas reservas --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-event me-2 text-muted"></i>Próximas reservas</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <tbody>
                            @forelse($upcoming as $r)
                            <tr>
                                <td class="ps-4 py-2">
                                    <a href="{{ route('rentals.show', $r) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $r->code }}</a>
                                    <div class="text-muted small">{{ $r->client?->full_name }}</div>
                                </td>
                                <td class="py-2 small">{{ $r->motoUnit?->display_name }}</td>
                                <td class="py-2 small text-muted text-end pe-4">{{ $r->start_date?->format('d/m/Y') }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-center py-4 text-muted small">Sin reservas próximas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Devoluciones esperadas --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-arrow-in-down me-2 text-muted"></i>Devoluciones esperadas</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <tbody>
                            @forelse($dueReturns as $r)
                            <tr>
                                <td class="ps-4 py-2">
                                    <a href="{{ route('rentals.show', $r) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $r->code }}</a>
                                    <div class="text-muted small">{{ $r->client?->full_name }}</div>
                                </td>
                                <td class="py-2 small">{{ $r->motoUnit?->display_name }}</td>
                                <td class="py-2 small text-end pe-4">
                                    @if($r->end_date && $r->end_date->isPast())
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Vencido {{ $r->end_date->format('d/m') }}</span>
                                    @else
                                        <span class="text-muted">{{ $r->end_date?->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td class="text-center py-4 text-muted small">Sin devoluciones pendientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Cobros de renta: vencidas y próximas --}}
    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Cuotas vencidas</h6>
                    <a href="{{ route('rentals.collections', ['due' => 'vencidas']) }}" class="small text-decoration-none">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <tbody>
                            @forelse($overdueList as $inst)
                            <tr>
                                <td class="ps-4 py-2">
                                    <a href="{{ route('rentals.show', $inst->rental_contract_id) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $inst->contract?->code }}</a>
                                    <div class="text-muted small">{{ $inst->contract?->client?->full_name }}</div>
                                </td>
                                <td class="py-2 small">#{{ $inst->number }} · {{ $inst->period_label }}</td>
                                <td class="py-2 small text-end pe-4">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $inst->overdue_days }}d</span>
                                    <div class="fw-semibold mt-1">Bs. {{ number_format($inst->balance, 2) }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr><td class="text-center py-4 text-muted small">Sin cuotas vencidas. 🎉</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-alarm me-2 text-warning"></i>Próximos vencimientos (≤3 días)</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <tbody>
                            @forelse($upcomingInstallments as $inst)
                            <tr>
                                <td class="ps-4 py-2">
                                    <a href="{{ route('rentals.show', $inst->rental_contract_id) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $inst->contract?->code }}</a>
                                    <div class="text-muted small">{{ $inst->contract?->client?->full_name }}</div>
                                </td>
                                <td class="py-2 small">#{{ $inst->number }} · {{ $inst->period_label }}</td>
                                <td class="py-2 small text-end pe-4">
                                    <span class="text-muted">{{ $inst->due_date?->format('d/m/Y') }}</span>
                                    <div class="fw-semibold mt-1">Bs. {{ number_format($inst->balance, 2) }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr><td class="text-center py-4 text-muted small">Sin vencimientos próximos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
