@extends('layouts.app')

@section('title', 'Inicio')

@section('page')
@php
    $user  = auth()->user();
    $can   = fn (string $p) => $user->is_super_admin || $user->hasPermissionInCompany($p, $company);
    $sales = $overview['sales'];
    $ws    = $overview['workshop'];
    $stock = $overview['stock'];
    $appts = $ws['appointments'] ?? null;
    $woStatuses = \App\Models\Workshop\WorkOrder::STATUSES;
    $hasAnything = $sales || $ws || $stock;
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Inicio</h4>
            <p class="text-muted mb-0">Resumen operativo de hoy · {{ \Illuminate\Support\Carbon::parse($overview['date'])->translatedFormat('l d \d\e F') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($ws && $can('workshop.create'))
            <a href="{{ route('workshop.reception') }}" class="btn btn-danger"><i class="bi bi-clipboard2-plus me-1"></i>Nueva recepción</a>
            @endif
            @if($sales && $can('pos.access'))
            <a href="{{ route('sales.index') }}" class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i>Ventas</a>
            @endif
        </div>
    </div>

    @if(! $hasAnything)
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-speedometer2 fs-1 d-block mb-2"></i>
            No tienes tableros habilitados. Pide a tu administrador los permisos de dashboard de Ventas, Taller o Compras.
        </div>
    </div>
    @endif

    {{-- ── KPIs del día ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @if($sales)
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('sales.index') }}" class="text-decoration-none">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ money($sales['total']) }}</div>
                        <div class="kpi-label">Ventas hoy</div>
                        <div class="kpi-trend text-muted">{{ $sales['count'] }} {{ $sales['count'] === 1 ? 'venta' : 'ventas' }}</div>
                    </div>
                    <div class="kpi-icon" style="background:#16a34a;"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
            </a>
        </div>
        @endif
        @if($ws)
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('workshop.orders.index') }}" class="text-decoration-none">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $ws['received_today'] }}</div>
                        <div class="kpi-label">OTs hoy</div>
                        <div class="kpi-trend text-muted">{{ $ws['active'] }} activas en taller</div>
                    </div>
                    <div class="kpi-icon" style="background:#7c3aed;"><i class="bi bi-tools"></i></div>
                </div>
            </div>
            </a>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $ws['vehicles_in_shop'] }}</div>
                        <div class="kpi-label">Motos en taller</div>
                        <div class="kpi-trend text-muted">con OT sin entregar</div>
                    </div>
                    <div class="kpi-icon" style="background:#4f46e5;"><i class="bi bi-bicycle"></i></div>
                </div>
            </div>
        </div>
        @if($can('appointments.view'))
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ route('workshop.agenda.index') }}" class="text-decoration-none">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $appts['total'] }}</div>
                        <div class="kpi-label">Citas hoy</div>
                        <div class="kpi-trend text-muted">{{ $appts['pending'] }} {{ $appts['pending'] === 1 ? 'pendiente' : 'pendientes' }}</div>
                    </div>
                    <div class="kpi-icon" style="background:#db2777;"><i class="bi bi-calendar2-week"></i></div>
                </div>
            </div>
            </a>
        </div>
        @endif
        @endif
        @if($stock)
        <div class="col-xl col-md-4 col-sm-6">
            <a href="{{ $can('products.view') ? route('products.index') : '#' }}" class="text-decoration-none">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $stock['in_stock'] }}</div>
                        <div class="kpi-label">Repuestos en stock</div>
                        <div class="kpi-trend {{ $stock['low_stock'] ? 'text-danger fw-semibold' : 'text-muted' }}">
                            @if($stock['low_stock'])<i class="bi bi-exclamation-triangle me-1"></i>{{ $stock['low_stock'] }} en stock bajo @else sin alertas de stock @endif
                        </div>
                    </div>
                    <div class="kpi-icon" style="background:#0284c7;"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
            </a>
        </div>
        @endif
    </div>

    @if($ws)
    <div class="row g-4 mb-4">
        {{-- ── OTs por estado + próxima cita ────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-kanban me-2 text-primary"></i>OTs en taller por estado</h6>
                </div>
                <div class="card-body">
                    @php $maxStatus = max(1, max($ws['by_status'] ?: [0])); @endphp
                    @foreach($ws['by_status'] as $status => $count)
                    @php $meta = $woStatuses[$status] ?? ['label' => $status, 'color' => 'secondary']; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><span class="badge bg-{{ $meta['color'] }} me-2">&nbsp;</span>{{ $meta['label'] }}</span>
                            <span class="fw-bold">{{ $count }}</span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-{{ $meta['color'] }}" style="width: {{ round($count / $maxStatus * 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                    @if($ws['active'] === 0)
                    <div class="text-muted small text-center py-2">No hay órdenes en el taller ahora mismo.</div>
                    @endif
                </div>
                @if($can('appointments.view'))
                <div class="card-footer bg-white border-top py-3">
                    <div class="small text-muted mb-1"><i class="bi bi-alarm me-1"></i>Próxima cita</div>
                    @if($appts['next'])
                    <div class="d-flex align-items-center gap-3">
                        <span class="fs-4 fw-bold text-primary">{{ $appts['next']['time'] }}</span>
                        <div class="lh-sm">
                            <div class="fw-semibold">{{ $appts['next']['client'] ?? 'Sin nombre' }}</div>
                            <div class="small text-muted">{{ $appts['next']['title'] ?? 'Sin detalle' }}</div>
                        </div>
                        <a href="{{ route('workshop.agenda.index') }}" class="btn btn-sm btn-outline-primary ms-auto">Agenda</a>
                    </div>
                    @else
                    <div class="text-muted small">No quedan citas pendientes hoy.</div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- ── Ventas por servicio (mes) ────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-wrench-adjustable me-2 text-primary"></i>Ventas por servicio</h6>
                    <span class="small text-muted">este mes</span>
                </div>
                <div class="card-body">
                    @php $maxSvc = max(1, collect($ws['top_services'])->max('amount') ?? 0); @endphp
                    @forelse($ws['top_services'] as $s)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-truncate fw-semibold me-2">{{ $s['label'] }}</span>
                            <span class="text-nowrap"><span class="text-muted me-2">×{{ $s['count'] }}</span><strong>{{ money($s['amount']) }}</strong></span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar" style="width: {{ round($s['amount'] / $maxSvc * 100) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <div class="text-muted small text-center py-3">Aún no hay servicios facturados este mes.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── OTs recientes ─────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>OTs recientes</h6>
                    <a href="{{ route('workshop.orders.index') }}" class="small">Ver todas</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($ws['recent'] as $o)
                    <a href="{{ route('workshop.orders.show', $o['id']) }}" class="list-group-item list-group-item-action py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="lh-sm">
                                <div class="fw-semibold small">{{ $o['code'] }} <span class="text-muted fw-normal">· {{ $o['client'] ?? 'Sin cliente' }}</span></div>
                                <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $o['vehicle'] ?? 'Sin vehículo' }}</div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $o['status_color'] }}">{{ $o['status_label'] }}</span>
                                @if($o['balance'] > 0)<div class="small text-danger">Saldo {{ money($o['balance']) }}</div>@endif
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="list-group-item text-muted small text-center py-3">Sin órdenes recientes.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Accesos rápidos ──────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Accesos rápidos</h6>
            <div class="row g-3">
                @php
                    $links = [
                        ['perm' => 'products.view',     'route' => 'products.index',        'icon' => 'bi-box-seam',       'color' => '#7c4dff', 'label' => 'Productos'],
                        ['perm' => 'sales.view',        'route' => 'sales.index',           'icon' => 'bi-receipt',        'color' => '#16a34a', 'label' => 'Ventas'],
                        ['perm' => 'workshop.view',     'route' => 'workshop.orders.index', 'icon' => 'bi-tools',          'color' => '#7c3aed', 'label' => 'Órdenes'],
                        ['perm' => 'appointments.view', 'route' => 'workshop.agenda.index', 'icon' => 'bi-calendar2-week', 'color' => '#db2777', 'label' => 'Agenda'],
                        ['perm' => 'warehouses.view',   'route' => 'warehouses.index',      'icon' => 'bi-building-add',   'color' => '#0288d1', 'label' => 'Almacenes'],
                        ['perm' => 'branches.view',     'route' => 'branches.index',        'icon' => 'bi-diagram-2',      'color' => '#00c853', 'label' => 'Sucursales'],
                        ['perm' => 'personal.view',     'route' => 'personal.index',        'icon' => 'bi-person-badge',   'color' => '#e91e63', 'label' => 'Personal'],
                    ];
                @endphp
                @foreach($links as $l)
                @if($can($l['perm']) && \Illuminate\Support\Facades\Route::has($l['route']))
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route($l['route']) }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi {{ $l['icon'] }} fs-2 mb-2 d-block" style="color:{{ $l['color'] }};"></i>
                            <small class="text-dark fw-semibold">{{ $l['label'] }}</small>
                        </div>
                    </a>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:10px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:0; height:100%; transition: transform .12s; }
    a:hover > .kpi-card { transform: translateY(-2px); }
    .kpi-body { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
    .kpi-value { font-size:1.6rem; font-weight:700; line-height:1; margin-bottom:4px; color:#111; }
    .kpi-label { font-size:.82rem; color:#777; margin-bottom:6px; }
    .kpi-trend { font-size:.75rem; }
    .kpi-icon { width:46px; height:46px; border-radius:12px; display:grid; place-items:center; color:#fff; font-size:1.2rem; flex-shrink:0; }
    .quick-link { background:#f8f9fa; transition:background .15s; }
    .quick-link:hover { background:#e9ecef; }
</style>
@endpush
@endsection
