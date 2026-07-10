@extends('layouts.app')
@section('title', 'Entregas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Entregas</h1>
            <p class="text-muted mb-0 small">Vehículos listos para entregar y entregas recientes.</p>
        </div>
        <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border">
            <i class="bi bi-clipboard2-pulse me-1"></i>Ver órdenes
        </a>
    </div>

    {{-- Listas para entregar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-check-circle me-2 text-success"></i>Listas para entregar</h6>
            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $pending->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Vehículo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Mecánico</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $order)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('workshop.orders.show', $order) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $order->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $order->vehicle?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $order->mechanic?->name ?? '—' }}</td>
                            <td class="py-2 text-end fw-semibold small">${{ number_format($order->total, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.deliver', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('workshop.deliveries.create', $order) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-box-arrow-right me-1"></i>Entregar
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay vehículos listos para entregar.</p>
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
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Entregas recientes</h6>
            <a href="{{ route('workshop.history') }}" class="btn btn-sm btn-light border">Ver historial</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Vehículo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Fecha entrega</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent as $order)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('workshop.orders.show', $order) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $order->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $order->vehicle?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">
                                {{ $order->delivered_at ? \Carbon\Carbon::parse($order->delivered_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-2 text-end fw-semibold small pe-4">${{ number_format($order->total, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-box-arrow-right fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">Sin entregas recientes.</p>
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
