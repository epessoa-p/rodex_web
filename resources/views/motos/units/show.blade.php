@extends('layouts.app')
@section('title', $unit->display_name)
@section('page')
<div class="container-fluid">

    {{-- Header card --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:5px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $unit->display_name }}</h1>
                        <span class="badge bg-{{ $unit->status_color }}-subtle text-{{ $unit->status_color }} border border-{{ $unit->status_color }}-subtle">
                            {{ $unit->status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-upc me-1"></i>Chasis: <span class="font-monospace">{{ $unit->chassis_number }}</span></span>
                        @if($unit->color)
                        <span><i class="bi bi-palette me-1"></i>{{ $unit->color }}</span>
                        @endif
                        @if($unit->year)
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $unit->year }}</span>
                        @endif
                        @if($unit->branch)
                        <span><i class="bi bi-diagram-2 me-1"></i>{{ $unit->branch->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('moto-units.edit', $unit) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('moto-units.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Datos técnicos --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2 text-muted"></i>Datos de la unidad</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-3 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Modelo</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $unit->model?->display_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">N° chasis</dt>
                        <dd class="col-7 mb-0 font-monospace" style="word-break:break-all;">{{ $unit->chassis_number }}</dd>

                        <dt class="col-5 text-muted fw-normal">N° motor</dt>
                        <dd class="col-7 mb-0 font-monospace">{{ $unit->engine_number ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Color</dt>
                        <dd class="col-7 mb-0">{{ $unit->color ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Placa</dt>
                        <dd class="col-7 mb-0 font-monospace">{{ $unit->placa ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Año</dt>
                        <dd class="col-7 mb-0">{{ $unit->year ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Sucursal</dt>
                        <dd class="col-7 mb-0">{{ $unit->branch?->name ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Costo</dt>
                        <dd class="col-7 mb-0">{{ money($unit->cost, null, 2) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Precio venta</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ money($unit->price, null, 2) }}</dd>

                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge bg-{{ $unit->status_color }}-subtle text-{{ $unit->status_color }} border border-{{ $unit->status_color }}-subtle">
                                {{ $unit->status_label }}
                            </span>
                        </dd>

                        @if($unit->notes)
                        <dt class="col-5 text-muted fw-normal">Notas</dt>
                        <dd class="col-7 mb-0" style="white-space:pre-line;">{{ $unit->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Venta / Acciones / Garantías --}}
        <div class="col-lg-7">

            {{-- Venta asociada --}}
            @if($unit->sale)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cart-check me-2 text-muted"></i>Venta asociada</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0">
                            <a href="{{ route('moto-sales.show', $unit->sale) }}" class="text-decoration-none fw-semibold">
                                {{ $unit->sale->code }}
                            </a>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Cliente</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $unit->sale->client?->full_name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $unit->sale->sale_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Total</dt>
                        <dd class="col-7 mb-0 fw-bold">{{ money($unit->sale->total, null, 2) }}</dd>
                    </dl>
                </div>
            </div>
            @endif

            {{-- Acciones de estado --}}
            @if($unit->status === 'vendida')
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-deliveries.manage', auth()->user()->getCurrentCompany()))
            <div class="card border-0 shadow-sm mb-4" style="border-left:3px solid #f59e0b!important;">
                <div class="card-body p-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="fw-semibold mb-1"><i class="bi bi-truck me-2 text-warning"></i>Pendiente de entrega</div>
                        <p class="text-muted small mb-0">Esta unidad está vendida pero aún no ha sido entregada al cliente.</p>
                    </div>
                    <a href="{{ route('moto-deliveries.create', $unit) }}" class="btn btn-primary btn-sm flex-shrink-0">
                        <i class="bi bi-truck me-1"></i>Registrar entrega
                    </a>
                </div>
            </div>
            @endif
            @endif

            @if($unit->status === 'entregada')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-2 text-success"></i>Entrega</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-3">
                        @if($unit->delivered_at)
                        <dt class="col-5 text-muted fw-normal">Fecha entrega</dt>
                        <dd class="col-7 mb-0">{{ \Carbon\Carbon::parse($unit->delivered_at)->format('d/m/Y') }}</dd>
                        @endif
                        @if($unit->delivered_to)
                        <dt class="col-5 text-muted fw-normal">Entregado a</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $unit->delivered_to }}</dd>
                        @endif
                        @if($unit->assigned_plate)
                        <dt class="col-5 text-muted fw-normal">Placa asignada</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge bg-light text-dark border" style="letter-spacing:.06em;">
                                {{ $unit->assigned_plate }}
                            </span>
                        </dd>
                        @endif
                    </dl>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.manage', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('warranties.create', ['unit_id' => $unit->id]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-shield-plus me-1"></i>Registrar garantía
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Garantías --}}
            @php $warranties = $unit->warranties ?? collect(); @endphp
            @if($warranties->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-check me-2 text-muted"></i>Garantías</h6>
                    <span class="badge bg-light text-dark border">{{ $warranties->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="font-size:.83rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-2 fw-semibold text-muted" style="font-size:.72rem;">Código</th>
                                    <th class="py-2 fw-semibold text-muted" style="font-size:.72rem;">Inicio</th>
                                    <th class="py-2 fw-semibold text-muted" style="font-size:.72rem;">Vence</th>
                                    <th class="py-2 fw-semibold text-muted" style="font-size:.72rem;">Estado</th>
                                    <th class="py-2 pe-4" style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warranties as $w)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2 fw-semibold">{{ $w->code }}</td>
                                    <td class="py-2 text-muted">{{ $w->start_date->format('d/m/Y') }}</td>
                                    <td class="py-2 text-muted">{{ $w->end_date->format('d/m/Y') }}</td>
                                    <td class="py-2">
                                        <span class="badge bg-{{ $w->status_color }}-subtle text-{{ $w->status_color }} border border-{{ $w->status_color }}-subtle" style="font-size:.68rem;">
                                            {{ $w->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-2 pe-4">
                                        <a href="{{ route('warranties.show', $w) }}" class="btn btn-sm btn-light border py-0 px-2" title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
