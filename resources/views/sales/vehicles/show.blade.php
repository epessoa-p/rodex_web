@extends('layouts.app')
@section('title', $vehicle->brand . ' ' . $vehicle->model)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $vehicle->brand }} {{ $vehicle->model }}</h1>
                        @if($vehicle->plate)
                        <span class="badge bg-light text-dark border">{{ $vehicle->plate }}</span>
                        @endif
                        <span class="badge {{ $vehicle->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                            {{ $vehicle->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        @if($vehicle->client)
                        <span><i class="bi bi-person me-1"></i>{{ $vehicle->client->full_name }}</span>
                        @endif
                        @if($vehicle->year)
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $vehicle->year }}</span>
                        @endif
                        @if($vehicle->engine_cc)
                        <span><i class="bi bi-speedometer me-1"></i>{{ $vehicle->engine_cc }} cc</span>
                        @endif
                        @if($vehicle->color)
                        <span><i class="bi bi-palette me-1"></i>{{ $vehicle->color }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('vehicles.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Detail card --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2 text-muted"></i>Datos del vehículo</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-3 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Marca</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $vehicle->brand }}</dd>

                        <dt class="col-5 text-muted fw-normal">Modelo</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->model ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Placa</dt>
                        <dd class="col-7 mb-0">
                            @if($vehicle->plate)
                            <span class="badge bg-light text-dark border" style="font-size:.8rem;letter-spacing:.06em;">
                                {{ $vehicle->plate }}
                            </span>
                            @else —
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Cilindrada</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->engine_cc ? $vehicle->engine_cc . ' cc' : '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Año</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->year ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Color</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->color ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">VIN / Chasis</dt>
                        <dd class="col-7 mb-0 text-break" style="word-break:break-all;">{{ $vehicle->vin ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge {{ $vehicle->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                {{ $vehicle->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Registrado</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Client card --}}
        <div class="col-lg-6">
            @if($vehicle->client)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Cliente propietario</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:44px;height:44px;font-size:.9rem;font-weight:700;color:#9a9a9a;">
                            {{ strtoupper(substr($vehicle->client->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $vehicle->client->full_name }}</div>
                            @if($vehicle->client->id_number)
                            <small class="text-muted">CI: {{ $vehicle->client->id_number }}</small>
                            @endif
                        </div>
                    </div>
                    <dl class="row g-2 small mb-0">
                        @if($vehicle->client->phone)
                        <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                        <dd class="col-7 mb-0">{{ $vehicle->client->phone }}</dd>
                        @endif
                        @if($vehicle->client->email)
                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 mb-0 text-break">{{ $vehicle->client->email }}</dd>
                        @endif
                    </dl>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('clients.view', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('clients.show', $vehicle->client) }}" class="btn btn-light border btn-sm mt-3">
                        <i class="bi bi-person-circle me-1"></i>Ver perfil del cliente
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if($vehicle->notes)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-muted"></i>Notas</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-0" style="white-space:pre-line;">{{ $vehicle->notes }}</p>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
