@extends('layouts.app')
@section('title', 'Historial de taller')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clock-history me-2 text-danger"></i>Historial de taller</h1>
            <p class="text-muted mb-0 small">Órdenes de trabajo entregadas y anuladas.</p>
        </div>
        <a href="{{ route('workshop.deliveries.index') }}" class="btn btn-light border">
            <i class="bi bi-box-arrow-right me-1"></i>Entregas
        </a>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Mecánico</label>
                    <select name="mechanic_id" class="form-select form-select-sm">
                        <option value="">Todos los mecánicos</option>
                        @foreach($mechanics as $m)
                        <option value="{{ $m->id }}" {{ request('mechanic_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Cliente</label>
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                        @if(request()->hasAny(['date_from','date_to','mechanic_id','client_id']))
                        <a href="{{ route('workshop.history') }}" class="btn btn-light border btn-sm">
                            <i class="bi bi-x me-1"></i>Limpiar
                        </a>
                        @endif
                    </div>
                </div>
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
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Fecha entrega</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase pe-4" style="font-size:.72rem;letter-spacing:.04em;">Pago</th>
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
                                {{ $order->delivered_at ? \Carbon\Carbon::parse($order->delivered_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-2 text-end fw-semibold small">{{ money($order->total, null, 2) }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle" style="font-size:.7rem;">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="py-2 pe-4">
                                <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle" style="font-size:.7rem;">
                                    {{ $order->payment_status_label }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">Sin registros en el historial.</p>
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
