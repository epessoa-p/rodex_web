<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $branch ? 'Editar sucursal' : 'Nueva sucursal' }}</h1>
            <p class="text-muted mb-0">Configura la información operativa de la sucursal.</p>
        </div>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ $action }}" method="POST" class="row g-3">
                @csrf
                @if($method !== 'POST') @method($method) @endif
                @if($isSuperAdmin ?? false)
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" id="company_id" class="form-select">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id', $selectedCompanyId ?? $branch?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control" value="{{ old('name', $branch?->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Código</label><input type="text" name="code" class="form-control" value="{{ old('code', $branch?->code) }}"></div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-1">Almacén principal <span class="text-danger">*</span></label>
                        @if($canCreateWarehouse ?? false)
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="openQuickWarehouse()">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo almacén
                        </button>
                        @endif
                    </div>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required
                            data-selected="{{ old('warehouse_id', $branch?->warehouse_id) }}">
                        <option value="">Seleccionar almacén</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id', $branch?->warehouse_id) === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                        @endforeach
                    </select>
                    <small id="warehouse_empty_hint" class="text-danger {{ $warehouses->isEmpty() ? '' : 'd-none' }}">No hay almacenes disponibles para esta empresa. Crea primero un almacén.</small>
                </div>
                <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $branch?->phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input type="email" name="email" class="form-control" value="{{ old('email', $branch?->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Encargado</label><input type="text" name="manager_name" class="form-control" value="{{ old('manager_name', $branch?->manager_name) }}"></div>
                <div class="col-md-6">
                    <label class="form-label">Color de la sucursal</label>
                    @php $branchColor = old('color', $branch?->color ?: '#e63946'); @endphp
                    <div class="input-group">
                        <input type="color" id="branch_color_picker" class="form-control form-control-color" value="{{ $branchColor }}"
                               style="max-width:52px;" title="Elegir color">
                        <input type="text" name="color" id="branch_color_hex" class="form-control" value="{{ $branchColor }}"
                               maxlength="7" placeholder="#e63946" data-no-search>
                    </div>
                    <small class="text-muted">Se usará como color de referencia de la sucursal.</small>
                </div>
                <div class="col-12"><label class="form-label">Dirección</label><input type="text" name="address" class="form-control" value="{{ old('address', $branch?->address) }}"></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $branch?->active ?? true) ? 'checked' : '' }}><label class="form-check-label">Activo</label></div></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Guardar</button><a href="{{ route('branches.index') }}" class="btn btn-light border">Cancelar</a></div>
            </form>
        </div>
    </div>

    {{-- ── Modal: alta rápida de almacén ──────────────────────────────── --}}
    @if($canCreateWarehouse ?? false)
    <div class="modal fade" id="quickWarehouseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-house-add me-2 text-primary"></i>Nuevo almacén</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="qwAlert" class="alert alert-danger d-none py-2 small"></div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small" for="qw_name">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="qw_name" class="form-control" maxlength="255" placeholder="Ej: Almacén central">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qw_code">Código</label>
                            <input type="text" id="qw_code" class="form-control" maxlength="50" placeholder="Se autogenera" data-no-search>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="qw_location">Ubicación</label>
                            <input type="text" id="qw_location" class="form-control" maxlength="255" placeholder="Ej: Av. Principal 123">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" id="qw_save" onclick="saveQuickWarehouse()">
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
(function () {
    const picker = document.getElementById('branch_color_picker');
    const hex    = document.getElementById('branch_color_hex');
    if (picker && hex) {
        picker.addEventListener('input', () => { hex.value = picker.value; });
        hex.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
        });
    }
})();

// ── Almacenes según la empresa elegida (solo super_admin) ──────────────
@if($isSuperAdmin ?? false)
(function () {
    if (!window.jQuery) return;
    const $        = window.jQuery;
    const URL      = '{{ route('branches.warehouses') }}';
    const $company = $('#company_id');
    const $wh      = $('#warehouse_id');
    const $hint    = $('#warehouse_empty_hint');
    if (!$company.length || !$wh.length) return;

    function loadWarehouses(companyId, keepSelection) {
        const prev = keepSelection ? ($wh.val() || $wh.data('selected') || '') : '';
        $wh.prop('disabled', true);
        $.getJSON(URL, { company_id: companyId })
            .done(function (rows) {
                let html = '<option value="">Seleccionar almacén</option>';
                (rows || []).forEach(function (w) {
                    const label = w.name + (w.code ? ' (' + w.code + ')' : '');
                    html += '<option value="' + w.id + '">' + $('<div>').text(label).html() + '</option>';
                });
                $wh.html(html);
                // Reponer selección previa si sigue existiendo para esa empresa.
                if (prev && $wh.find('option[value="' + prev + '"]').length) {
                    $wh.val(String(prev));
                }
                $hint.toggleClass('d-none', (rows || []).length > 0);
                $wh.prop('disabled', false).trigger('change.select2');
            })
            .fail(function () {
                $wh.html('<option value="">Seleccionar almacén</option>')
                   .prop('disabled', false).trigger('change.select2');
            });
    }

    // Al cambiar de empresa se recargan los almacenes (se descarta la selección anterior).
    $company.on('change', function () { loadWarehouses($(this).val(), false); });
})();
@endif

// ── Alta rápida de almacén (modal + AJAX) ──────────────────────────────
@if($canCreateWarehouse ?? false)
const QW_CSRF = '{{ csrf_token() }}';
const QW_URL  = '{{ route('warehouses.quick-store') }}';

// Agrega una opción a un <select> (compatible con select2) y la selecciona.
function qwAddAndSelect(selectId, id, text) {
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

function qwToast(msg) {
    let cont = document.getElementById('qwToastCont');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'qwToastCont';
        cont.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(cont);
    }
    const el = document.createElement('div');
    el.style.cssText = 'background:#fff;border-left:4px solid #16a34a;box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
    el.innerHTML = '<i class="bi bi-check-circle" style="color:#16a34a;"></i><span>' + msg + '</span>';
    cont.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
}

function openQuickWarehouse() {
    ['qw_name', 'qw_code', 'qw_location'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('qwAlert').classList.add('d-none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickWarehouseModal')).show();
    setTimeout(() => document.getElementById('qw_name').focus(), 300);
}

function saveQuickWarehouse() {
    const name     = document.getElementById('qw_name').value.trim();
    const alertBox = document.getElementById('qwAlert');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    const body = {
        name:     name,
        code:     document.getElementById('qw_code').value.trim(),
        location: document.getElementById('qw_location').value.trim(),
    };
    // En modo global, el almacén se crea en la empresa seleccionada en el formulario.
    const companySel = document.getElementById('company_id');
    if (companySel) body.company_id = companySel.value;

    const btn = document.getElementById('qw_save');
    const original = btn.innerHTML;
    alertBox.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch(QW_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': QW_CSRF },
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
        const label = d.warehouse.name + (d.warehouse.code ? ' (' + d.warehouse.code + ')' : '');
        qwAddAndSelect('warehouse_id', d.warehouse.id, label);
        document.getElementById('warehouse_empty_hint')?.classList.add('d-none');
        bootstrap.Modal.getInstance(document.getElementById('quickWarehouseModal'))?.hide();
        qwToast('Almacén «' + d.warehouse.name + '» creado y seleccionado.');
    })
    .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = original; });
}
@endif
</script>
@endpush