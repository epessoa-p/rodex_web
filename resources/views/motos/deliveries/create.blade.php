@extends('layouts.app')
@section('title', 'Registrar Entrega')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-truck me-2 text-danger"></i>Registrar Entrega</h1>
            <p class="text-muted mb-0 small">Confirma la entrega de la moto al cliente.</p>
        </div>
        <a href="{{ route('moto-deliveries.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    {{-- Info moto --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:4px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 60%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:52px;height:52px;font-size:1.4rem;">
                    <i class="bi bi-bicycle text-muted"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5">{{ $unit->display_name }}</div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-upc me-1"></i>{{ $unit->chassis_number }}</span>
                        @if($unit->color)
                        <span><i class="bi bi-palette me-1"></i>{{ $unit->color }}</span>
                        @endif
                        @if($unit->sale?->client)
                        <span><i class="bi bi-person me-1"></i>{{ $unit->sale->client->full_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                        <i class="bi bi-clock-history me-1"></i>Pendiente de entrega
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('moto-deliveries.store', $unit) }}" method="POST">
        @csrf

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2 text-muted"></i>Datos de la entrega</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="delivered_to">
                                    Entregado a <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="delivered_to" name="delivered_to"
                                       class="form-control @error('delivered_to') is-invalid @enderror"
                                       value="{{ old('delivered_to', $unit->sale?->client?->full_name ?? '') }}"
                                       required maxlength="255"
                                       placeholder="Nombre completo del receptor">
                                @error('delivered_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text text-muted small">Puede ser el cliente u otra persona autorizada.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="assigned_plate">Placa asignada</label>
                                <input type="text" id="assigned_plate" name="assigned_plate"
                                       class="form-control font-monospace @error('assigned_plate') is-invalid @enderror"
                                       value="{{ old('assigned_plate') }}"
                                       maxlength="20"
                                       placeholder="Ej: ABC-1234"
                                       style="text-transform:uppercase;"
                                       oninput="this.value=this.value.toUpperCase()">
                                @error('assigned_plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text text-muted small">Opcional si aún no tiene placa.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="delivery_notes">Notas de entrega</label>
                                <textarea id="delivery_notes" name="delivery_notes"
                                          class="form-control @error('delivery_notes') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Condiciones de entrega, accesorios incluidos, observaciones...">{{ old('delivery_notes') }}</textarea>
                                @error('delivery_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded-2 border bg-light">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               id="register_vehicle" name="register_vehicle" value="1"
                                               {{ old('register_vehicle', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="register_vehicle">
                                            <i class="bi bi-person-vcard me-1"></i>Registrar esta moto como vehículo del cliente
                                        </label>
                                        <div class="text-muted small mt-1">
                                            Se creará automáticamente el vehículo en el perfil del cliente para el módulo de taller.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Confirmar entrega</h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-0">
                            Al confirmar, la unidad cambiará su estado a <strong>Entregada</strong>
                            y quedará registrada la fecha de entrega de hoy.
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-truck me-1"></i>Confirmar entrega
                            </button>
                            <a href="{{ route('moto-deliveries.index') }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </form>

</div>
@endsection
