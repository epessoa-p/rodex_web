@extends('layouts.app')
@section('title', 'Entregas de Motos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-truck me-2 text-danger"></i>Entregas de Motos</h1>
            <p class="text-muted mb-0 small">Gestión de entregas a clientes de unidades vendidas.</p>
        </div>
    </div>

    {{-- Pendientes --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-warning"></i>Pendientes de entrega</h6>
            @if(isset($pending) && $pending->isNotEmpty())
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                {{ $pending->count() }} pendiente{{ $pending->count() > 1 ? 's' : '' }}
            </span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Precio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $unit)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 fw-semibold">{{ $unit->display_name }}</td>
                            <td class="py-2 small">{{ $unit->sale?->client?->full_name ?? '—' }}</td>
                            <td class="py-2 text-end fw-semibold">{{ money($unit->price, null, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-deliveries.manage', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-deliveries.create', $unit) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-truck me-1"></i>Entregar
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle fs-2 d-block mb-2 opacity-25"></i>
                                <span class="small">No hay entregas pendientes.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Entregas recientes --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-check-circle me-2 text-success"></i>Entregas realizadas</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha entrega</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Placa</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $unit)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 fw-semibold">{{ $unit->display_name }}</td>
                            <td class="py-2 small">{{ $unit->sale?->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">
                                {{ $unit->delivered_at ? \Carbon\Carbon::parse($unit->delivered_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-2">
                                @if($unit->assigned_plate)
                                <span class="badge bg-light text-dark border" style="letter-spacing:.06em;font-size:.75rem;">
                                    {{ $unit->assigned_plate }}
                                </span>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('moto-units.show', $unit) }}" class="btn btn-sm btn-light border" title="Ver unidad">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-truck fs-2 d-block mb-2 opacity-25"></i>
                                <span class="small">Sin entregas recientes.</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
