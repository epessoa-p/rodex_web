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
            @php
                $hasFilters = request('mechanic_id') || request('status') || request('client_id')
                    || request('payment_status') || request('date_from') || request('date_to') || request('q');
            @endphp
            <form method="GET">
                {{-- Conserva el estado activo (elegido por las píldoras) al enviar el resto de filtros --}}
                <input type="hidden" name="status" value="{{ request('status') }}">

                {{-- Status pills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small mb-1 d-block">Estado</label>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except(['status', 'page']), [])) }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                            Todos
                        </a>
                        @foreach(['recibida' => 'Recibida', 'diagnosticada' => 'Diagnosticada', 'en_proceso' => 'En proceso', 'terminada' => 'Terminada', 'entregada' => 'Entregada', 'anulada' => 'Anulada'] as $val => $label)
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except(['status', 'page']), ['status' => $val])) }}"
                           class="btn btn-sm {{ request('status') === $val ? 'btn-primary' : 'btn-light border' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Buscar</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                   placeholder="Código OT, placa o cliente…">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Cliente</label>
                        <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Mecánico</label>
                        <select name="mechanic_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ request('mechanic_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Pago</label>
                        <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach(\App\Models\Workshop\WorkOrder::PAYMENT_STATUSES as $val => $ps)
                            <option value="{{ $val }}" {{ request('payment_status') === $val ? 'selected' : '' }}>{{ $ps['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Recepción (desde / hasta)</label>
                        <div class="d-flex gap-1">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm flex-grow-1" style="min-width:0;" onchange="this.form.submit()">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm flex-grow-1" style="min-width:0;" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                        @if($hasFilters)
                        <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border btn-sm">
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
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Recepción</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Pago</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $canDeliver = auth()->user()->is_super_admin
                                || auth()->user()->hasPermissionInCompany('workshop.deliver', auth()->user()->getCurrentCompany());
                        @endphp
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
                            <td class="py-2">
                                @if($order->status === 'entregada' || $order->paid_amount > 0)
                                    <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle" style="font-size:.7rem;">
                                        {{ $order->payment_status_label }}
                                    </span>
                                    @if($order->delivered_at)
                                    <div class="text-muted" style="font-size:.65rem;"><i class="bi bi-box-arrow-right me-1"></i>{{ $order->delivered_at->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold small">{{ money($order->total, null, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    @if($order->status === 'terminada' && $canDeliver)
                                    <a href="{{ route('workshop.deliveries.create', $order) }}"
                                       class="btn btn-sm btn-primary py-0 px-2" title="Entregar / Cobrar" style="font-size:.8rem;">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('workshop.orders.show', $order) }}"
                                       class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
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
