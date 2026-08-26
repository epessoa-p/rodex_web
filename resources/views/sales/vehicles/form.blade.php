@php
    $isEdit = isset($vehicle);
    $action = $isEdit ? route('vehicles.update', $vehicle) : route('vehicles.store');
    $method = $isEdit ? 'PUT' : 'POST';

    $authUser        = auth()->user();
    $canCreateClient = $authUser->is_super_admin
        || $authUser->hasPermissionInCompany('clients.create', $authUser->getCurrentCompany());
@endphp

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- ── LEFT ─────────────────────────────────────────────────── --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bicycle me-2 text-muted"></i>Datos del vehículo</h6>
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
                                    class="form-select @error('client_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar cliente —</option>
                                @foreach($clients as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('client_id', $isEdit ? $vehicle->client_id : '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="brand">
                                Marca <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="brand" name="brand"
                                   class="form-control @error('brand') is-invalid @enderror"
                                   value="{{ old('brand', $isEdit ? $vehicle->brand : '') }}"
                                   required maxlength="100"
                                   placeholder="Ej: Honda, Yamaha, Suzuki...">
                            @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="model">Modelo</label>
                            <input type="text" id="model" name="model"
                                   class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model', $isEdit ? $vehicle->model : '') }}"
                                   maxlength="100"
                                   placeholder="Ej: CB 125F, FZ-S, XR 150L...">
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="plate">Placa</label>
                            <input type="text" id="plate" name="plate"
                                   class="form-control @error('plate') is-invalid @enderror"
                                   value="{{ old('plate', $isEdit ? $vehicle->plate : '') }}"
                                   maxlength="20"
                                   placeholder="Ej: 1234-ABC">
                            @error('plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="engine_cc">Cilindrada (cc)</label>
                            <input type="number" id="engine_cc" name="engine_cc"
                                   class="form-control @error('engine_cc') is-invalid @enderror"
                                   value="{{ old('engine_cc', $isEdit ? $vehicle->engine_cc : '') }}"
                                   min="0" placeholder="125">
                            @error('engine_cc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="year">Año</label>
                            <input type="number" id="year" name="year"
                                   class="form-control @error('year') is-invalid @enderror"
                                   value="{{ old('year', $isEdit ? $vehicle->year : '') }}"
                                   min="1900" max="{{ now()->year + 2 }}"
                                   placeholder="{{ now()->year }}">
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="color">Color</label>
                            <input type="text" id="color" name="color"
                                   class="form-control @error('color') is-invalid @enderror"
                                   value="{{ old('color', $isEdit ? $vehicle->color : '') }}"
                                   maxlength="60"
                                   placeholder="Ej: Rojo, Negro...">
                            @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="vin">VIN / N° de chasis</label>
                            <input type="text" id="vin" name="vin"
                                   class="form-control @error('vin') is-invalid @enderror"
                                   value="{{ old('vin', $isEdit ? $vehicle->vin : '') }}"
                                   maxlength="100"
                                   placeholder="Número de identificación del vehículo">
                            @error('vin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Observaciones, historial de mantenimiento, etc.">{{ old('notes', $isEdit ? $vehicle->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- ── RIGHT ────────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $vehicle->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Vehículo activo</label>
                    </div>
                    <p class="text-muted small mb-0">Los vehículos inactivos no aparecen en las búsquedas principales.</p>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5">
            <i class="bi bi-check-lg me-1"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Registrar vehículo' }}
        </button>
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

@push('scripts')
<script>
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

function openQuickClient() {
    ['qc_full_name', 'qc_id_number', 'qc_phone'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qcAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickClientModal')).show();
    setTimeout(() => document.getElementById('qc_full_name').focus(), 300);
}

function saveQuickClient() {
    const name = document.getElementById('qc_full_name').value.trim();
    const alertBox = document.getElementById('qcAlert');
    alertBox.classList.add('d-none');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    const btn = document.getElementById('qc_save');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch('{{ route('clients.quick-store') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': QUICK_CSRF },
        body: JSON.stringify({
            full_name: name,
            id_number: document.getElementById('qc_id_number').value.trim(),
            phone:     document.getElementById('qc_phone').value.trim(),
        }),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.ok) {
            const msg = d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar.');
            alertBox.textContent = msg;
            alertBox.classList.remove('d-none');
            return;
        }
        const label = d.client.full_name + (d.client.id_number ? ' (' + d.client.id_number + ')' : '');
        addAndSelect('client_id', d.client.id, label);
        bootstrap.Modal.getInstance(document.getElementById('quickClientModal'))?.hide();
        quickToast('Cliente «' + d.client.full_name + '» creado y seleccionado.');
    })
    .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = original; });
}
</script>
@endpush
@endif
