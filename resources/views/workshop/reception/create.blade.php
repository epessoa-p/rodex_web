@extends('layouts.app')
@section('title', 'Nueva recepción de vehículo')
@section('page')
@php
    $authUser          = auth()->user();
    $currentCompany    = $authUser->getCurrentCompany();
    $canCreateClient   = $authUser->is_super_admin || $authUser->hasPermissionInCompany('clients.create', $currentCompany);
    $canCreateMechanic = $authUser->is_super_admin || $authUser->hasPermissionInCompany('mechanics.create', $currentCompany);
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard2-plus me-2 text-danger"></i>Nueva recepción</h1>
            <p class="text-muted mb-0 small">Registra la entrada de un vehículo al taller y crea la orden de trabajo.</p>
        </div>
        <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
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

    <form action="{{ route('workshop.reception.store') }}" method="POST" id="receptionForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="vehicle_mode" id="vehicle_mode" value="{{ old('vehicle_mode', 'existing') }}">

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Cliente --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Cliente</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold mb-0" for="client_id">
                                        Cliente <span class="text-danger">*</span>
                                    </label>
                                    @if($canCreateClient)
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openQuickClient()">
                                        <i class="bi bi-person-plus me-1"></i>Nuevo
                                    </button>
                                    @endif
                                </div>
                                <select id="client_id" name="client_id"
                                        class="form-select @error('client_id') is-invalid @enderror"
                                        required onchange="filterVehiclesByClient()">
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if(isset($branches) && $branches->count() > 1)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="branch_id">Sucursal</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">— Seleccionar sucursal —</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Vehículo (card distintivo con toggle existente / nuevo) --}}
                @php $vehMode = old('vehicle_mode', 'existing'); @endphp
                <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid var(--brand-red,#e63946) !important;">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2 text-danger"></i>Vehículo</h6>
                        <div class="btn-group btn-group-sm" role="group" id="vehicleModeToggle">
                            <button type="button" class="btn {{ $vehMode !== 'new' ? 'btn-danger' : 'btn-outline-danger' }}"
                                    data-mode="existing"><i class="bi bi-list-check me-1"></i>Existente</button>
                            <button type="button" class="btn {{ $vehMode === 'new' ? 'btn-danger' : 'btn-outline-danger' }}"
                                    data-mode="new"><i class="bi bi-plus-circle me-1"></i>Nuevo</button>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        {{-- Modo: vehículo existente --}}
                        <div id="vehExistingPane" class="{{ $vehMode === 'new' ? 'd-none' : '' }}">
                            <label class="form-label fw-semibold" for="vehicle_id">
                                Vehículo del cliente <span class="text-danger">*</span>
                            </label>
                            <select id="vehicle_id" name="vehicle_id"
                                    class="form-select @error('vehicle_id') is-invalid @enderror">
                                <option value="">— Seleccionar vehículo —</option>
                                @foreach($vehicles as $v)
                                <option value="{{ $v->id }}"
                                        data-client="{{ $v->client_id }}"
                                        {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                                    {{ $v->display_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('vehicle_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                ¿No está registrado? Usa <strong>«Nuevo»</strong> arriba para registrarlo aquí mismo.
                            </div>
                        </div>

                        {{-- Modo: nuevo vehículo (registro inline) --}}
                        <div id="vehNewPane" class="{{ $vehMode === 'new' ? '' : 'd-none' }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="veh_brand">Marca <span class="text-danger">*</span></label>
                                    <input type="text" id="veh_brand" name="vehicle[brand]"
                                           class="form-control @error('vehicle.brand') is-invalid @enderror"
                                           value="{{ old('vehicle.brand') }}" maxlength="100" placeholder="Ej: Honda, Yamaha…">
                                    @error('vehicle.brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="veh_model">Modelo</label>
                                    <input type="text" id="veh_model" name="vehicle[model]"
                                           class="form-control" value="{{ old('vehicle.model') }}" maxlength="100"
                                           placeholder="Ej: CB 125F, FZ-S…">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="veh_plate">Placa</label>
                                    <input type="text" id="veh_plate" name="vehicle[plate]"
                                           class="form-control" value="{{ old('vehicle.plate') }}" maxlength="20"
                                           placeholder="1234-ABC" style="text-transform:uppercase;"
                                           oninput="this.value=this.value.toUpperCase()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="veh_engine_cc">Cilindrada (cc)</label>
                                    <input type="text" id="veh_engine_cc" name="vehicle[engine_cc]"
                                           class="form-control" value="{{ old('vehicle.engine_cc') }}" maxlength="30" placeholder="125">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="veh_year">Año</label>
                                    <input type="number" id="veh_year" name="vehicle[year]"
                                           class="form-control" value="{{ old('vehicle.year') }}"
                                           min="1900" max="{{ now()->year + 2 }}" placeholder="{{ now()->year }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="veh_color">Color</label>
                                    <input type="text" id="veh_color" name="vehicle[color]"
                                           class="form-control" value="{{ old('vehicle.color') }}" maxlength="40" placeholder="Ej: Rojo, Negro…">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="veh_vin">VIN / N° de chasis</label>
                                    <input type="text" id="veh_vin" name="vehicle[vin]"
                                           class="form-control" value="{{ old('vehicle.vin') }}" maxlength="60" placeholder="N° de chasis">
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle me-1"></i>El vehículo se registrará a nombre del cliente seleccionado.
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Datos de recepción --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2 text-muted"></i>Datos de recepción</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="reception_date">
                                    Fecha de recepción <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="reception_date" name="reception_date"
                                       class="form-control @error('reception_date') is-invalid @enderror"
                                       value="{{ old('reception_date', now()->format('Y-m-d')) }}"
                                       required>
                                @error('reception_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="mileage">Kilometraje</label>
                                <div class="input-group">
                                    <input type="number" id="mileage" name="mileage" min="0"
                                           class="form-control @error('mileage') is-invalid @enderror"
                                           value="{{ old('mileage') }}"
                                           placeholder="0">
                                    <span class="input-group-text bg-light">km</span>
                                </div>
                                @error('mileage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="fuel_level">Nivel de combustible</label>
                                <select id="fuel_level" name="fuel_level"
                                        class="form-select @error('fuel_level') is-invalid @enderror">
                                    <option value="">— Seleccionar —</option>
                                    <option value="vacio" {{ old('fuel_level') === 'vacio' ? 'selected' : '' }}>Vacío</option>
                                    <option value="1/4" {{ old('fuel_level') === '1/4' ? 'selected' : '' }}>1/4</option>
                                    <option value="1/2" {{ old('fuel_level') === '1/2' ? 'selected' : '' }}>1/2</option>
                                    <option value="3/4" {{ old('fuel_level') === '3/4' ? 'selected' : '' }}>3/4</option>
                                    <option value="lleno" {{ old('fuel_level') === 'lleno' ? 'selected' : '' }}>Lleno</option>
                                </select>
                                @error('fuel_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="reported_issue">Falla reportada por el cliente</label>
                                <textarea id="reported_issue" name="reported_issue" rows="3"
                                          class="form-control @error('reported_issue') is-invalid @enderror"
                                          placeholder="Describe la falla o los síntomas reportados por el cliente...">{{ old('reported_issue') }}</textarea>
                                @error('reported_issue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="received_items">Objetos / accesorios recibidos</label>
                                <textarea id="received_items" name="received_items" rows="2"
                                          class="form-control @error('received_items') is-invalid @enderror"
                                          placeholder="Ej: Llaves de repuesto, documentos, herramienta de abordo...">{{ old('received_items') }}</textarea>
                                @error('received_items')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas adicionales</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones internas...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Fotos del vehículo (estado al recibir) --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-camera me-2 text-muted"></i>Fotos del vehículo</h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Documenta el estado del vehículo al recibirlo (rayones, golpes, accesorios). Puedes subir archivos o tomar fotos con la cámara.
                        </p>
                        <x-media-upload name="photos[]" :multiple="true" :bare="true"
                            label="Fotos" icon="bi-camera" :max-mb="5" :max-files="12"
                            accent="#e63946" drop-text="Arrastra las fotos o toca para elegir" />
                    </div>
                </div>

            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2 text-muted"></i>Mecánico asignado</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0" for="mechanic_id">Mecánico (opcional)</label>
                            @if($canCreateMechanic)
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openQuickMechanic()">
                                <i class="bi bi-person-plus me-1"></i>Nuevo
                            </button>
                            @endif
                        </div>
                        <select id="mechanic_id" name="mechanic_id"
                                class="form-select @error('mechanic_id') is-invalid @enderror">
                            <option value="">Sin asignar</option>
                            @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ old('mechanic_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }}{{ $m->specialty ? ' — ' . $m->specialty : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('mechanic_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="text-muted small mt-2 mb-0">Puedes asignar el mecánico más adelante desde la OT.</p>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Registrar recepción
                            </button>
                            <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    {{-- ── Modal: alta rápida de cliente ──────────────────────────────── --}}
    @if($canCreateClient)
    <div class="modal fade" id="quickClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2 text-primary"></i>Nuevo cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="qcAlert" class="alert alert-danger d-none py-2 small"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small" for="qc_full_name">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" id="qc_full_name" class="form-control" maxlength="255" placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qc_id_number">Documento</label>
                            <input type="text" id="qc_id_number" class="form-control" maxlength="50" placeholder="CI / NIT">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qc_phone">Teléfono</label>
                            <input type="text" id="qc_phone" class="form-control" maxlength="20" placeholder="Ej: 700-00000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" id="qc_save" onclick="saveQuickClient()">
                        <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Modal: alta rápida de mecánico ─────────────────────────────── --}}
    @if($canCreateMechanic)
    <div class="modal fade" id="quickMechanicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-person-gear me-2 text-primary"></i>Nuevo mecánico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="qmAlert" class="alert alert-danger d-none py-2 small"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small" for="qm_name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="qm_name" class="form-control" maxlength="255" placeholder="Ej: Carlos Rojas">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qm_specialty">Especialidad</label>
                            <input type="text" id="qm_specialty" class="form-control" maxlength="150" placeholder="Ej: Motor / eléctrico">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qm_phone">Teléfono</label>
                            <input type="text" id="qm_phone" class="form-control" maxlength="30" placeholder="Ej: 700-00000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" id="qm_save" onclick="saveQuickMechanic()">
                        <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
function filterVehiclesByClient() {
    const clientId = document.getElementById('client_id').value;
    const vehicleSelect = document.getElementById('vehicle_id');
    const options = vehicleSelect.querySelectorAll('option');
    let firstVisible = null;

    options.forEach(opt => {
        if (!opt.value) return; // skip placeholder
        if (!clientId || opt.dataset.client === clientId) {
            opt.style.display = '';
            if (!firstVisible) firstVisible = opt;
        } else {
            opt.style.display = 'none';
        }
    });

    // Reset selection if current vehicle doesn't match
    const current = vehicleSelect.options[vehicleSelect.selectedIndex];
    if (current && current.value && clientId && current.dataset.client !== clientId) {
        vehicleSelect.value = '';
    }
}

// ── Toggle vehículo: existente / nuevo ──────────────────────────────
function setVehicleMode(mode) {
    const isNew = mode === 'new';
    document.getElementById('vehicle_mode').value = mode;
    document.getElementById('vehExistingPane').classList.toggle('d-none', isNew);
    document.getElementById('vehNewPane').classList.toggle('d-none', !isNew);

    // required según el modo (evita bloquear el envío por campos ocultos)
    const vsel  = document.getElementById('vehicle_id');
    const brand = document.getElementById('veh_brand');
    if (vsel)  vsel.required  = !isNew;
    if (brand) brand.required = isNew;

    // estilos de los botones del toggle
    document.querySelectorAll('#vehicleModeToggle button[data-mode]').forEach(function (b) {
        const active = b.dataset.mode === mode;
        b.classList.toggle('btn-danger', active);
        b.classList.toggle('btn-outline-danger', !active);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Apply filter on load if client is pre-selected (old input)
    filterVehiclesByClient();

    document.querySelectorAll('#vehicleModeToggle button[data-mode]').forEach(function (btn) {
        btn.addEventListener('click', function () { setVehicleMode(this.dataset.mode); });
    });
    // Estado inicial (respeta old input tras un error de validación)
    setVehicleMode(document.getElementById('vehicle_mode').value || 'existing');
});

// ── Alta rápida de cliente / mecánico ───────────────────────────────
const QUICK_CSRF = '{{ csrf_token() }}';

function quickToast(msg) {
    let cont = document.getElementById('quickToastCont');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'quickToastCont';
        cont.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(cont);
    }
    const el = document.createElement('div');
    el.style.cssText = 'background:#fff;border-left:4px solid #16a34a;box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
    el.innerHTML = '<i class="bi bi-check-circle" style="color:#16a34a;"></i><span>' + msg + '</span>';
    cont.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
}

// Agrega una opción a un <select> (compatible con select2) y la selecciona.
function addAndSelect(selectId, id, text) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    if (!sel.querySelector('option[value="' + id + '"]')) {
        sel.appendChild(new Option(text, id, true, true));
    }
    if (window.jQuery && jQuery(sel).data('select2')) {
        jQuery(sel).val(String(id)).trigger('change');
    } else {
        sel.value = String(id);
        sel.dispatchEvent(new Event('change'));
    }
}

function quickPost(url, body, alertId, btn, onOk) {
    const alertBox = document.getElementById(alertId);
    alertBox.classList.add('d-none');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': QUICK_CSRF },
        body: JSON.stringify(body),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.ok) {
            const msg = d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar.');
            alertBox.textContent = msg;
            alertBox.classList.remove('d-none');
            return;
        }
        onOk(d);
    })
    .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = original; });
}

// ── Cliente ──
function openQuickClient() {
    ['qc_full_name', 'qc_id_number', 'qc_phone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qcAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickClientModal')).show();
    setTimeout(() => document.getElementById('qc_full_name').focus(), 300);
}

function saveQuickClient() {
    const name = document.getElementById('qc_full_name').value.trim();
    const alertBox = document.getElementById('qcAlert');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    quickPost('{{ route('clients.quick-store') }}', {
        full_name: name,
        id_number: document.getElementById('qc_id_number').value.trim(),
        phone:     document.getElementById('qc_phone').value.trim(),
    }, 'qcAlert', document.getElementById('qc_save'), (d) => {
        const label = d.client.full_name + (d.client.id_number ? ' (' + d.client.id_number + ')' : '');
        addAndSelect('client_id', d.client.id, label);
        bootstrap.Modal.getInstance(document.getElementById('quickClientModal'))?.hide();
        quickToast('Cliente «' + d.client.full_name + '» creado y seleccionado.');
    });
}

// ── Mecánico ──
function openQuickMechanic() {
    ['qm_name', 'qm_specialty', 'qm_phone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qmAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickMechanicModal')).show();
    setTimeout(() => document.getElementById('qm_name').focus(), 300);
}

function saveQuickMechanic() {
    const name = document.getElementById('qm_name').value.trim();
    const alertBox = document.getElementById('qmAlert');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    const specialty = document.getElementById('qm_specialty').value.trim();
    quickPost('{{ route('mechanics.quick-store') }}', {
        name: name,
        specialty: specialty,
        phone: document.getElementById('qm_phone').value.trim(),
    }, 'qmAlert', document.getElementById('qm_save'), (d) => {
        const label = d.mechanic.name + (d.mechanic.specialty ? ' — ' + d.mechanic.specialty : '');
        addAndSelect('mechanic_id', d.mechanic.id, label);
        bootstrap.Modal.getInstance(document.getElementById('quickMechanicModal'))?.hide();
        quickToast('Mecánico «' + d.mechanic.name + '» creado y seleccionado.');
    });
}
</script>
@endpush

@endsection
