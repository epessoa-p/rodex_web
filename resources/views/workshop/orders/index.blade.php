@extends('layouts.app')
@section('title', 'Órdenes de Trabajo')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Órdenes de Trabajo
                @unless($canAllRecords)
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle align-middle ms-1" style="font-size:.6rem;"><i class="bi bi-person-check me-1"></i>Solo las mías</span>
                @endunless
            </h1>
            <p class="text-muted mb-0 small">Gestión de órdenes de trabajo del taller.</p>
        </div>
        <a href="{{ route('workshop.reception') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva recepción
        </a>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                {{-- Status pills --}}
                <div>
                    <label class="form-label fw-semibold small mb-1 d-block">Estado</label>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except('status'), [])) }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                            Todos
                        </a>
                        @foreach(['recibida' => 'Recibida', 'diagnosticada' => 'Diagnosticada', 'en_proceso' => 'En proceso', 'terminada' => 'Terminada'] as $val => $label)
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except('status'), ['status' => $val])) }}"
                           class="btn btn-sm {{ request('status') === $val ? 'btn-primary' : 'btn-light border' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>
                {{-- Mechanic filter --}}
                <div>
                    <label class="form-label fw-semibold small mb-1">Mecánico</label>
                    <select name="mechanic_id" class="form-select form-select-sm" onchange="this.form.submit()"
                            style="min-width:180px;">
                        <option value="">Todos los mecánicos</option>
                        @foreach($mechanics as $m)
                        <option value="{{ $m->id }}" {{ request('mechanic_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if(request('mechanic_id') || request('status'))
                <div>
                    <label class="form-label small d-block mb-1">&nbsp;</label>
                    <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-x me-1"></i>Limpiar
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Vehículo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Mecánico</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Recepción</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
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
                            <td class="py-2 small text-muted">
                                {{ $order->reception_date ? \Carbon\Carbon::parse($order->reception_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle" style="font-size:.7rem;">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end fw-semibold small">${{ number_format($order->total, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('workshop.orders.show', $order) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard2 fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay órdenes de trabajo registradas.</p>
                                <a href="{{ route('workshop.reception') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Nueva recepción
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $orders->withQueryString()->links() }}</div>

</div>
@endsection
