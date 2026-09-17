@php
    $isEdit = isset($unit);
    $action = $isEdit ? route('moto-units.update', $unit) : route('moto-units.store');
    $method = $isEdit ? 'PUT' : 'POST';

    // La marca es solo una ayuda visual: resalta sus modelos, pero se puede
    // elegir cualquier modelo (la unidad no guarda marca: proviene del modelo).
    $currentModelId = (string) old('moto_model_id', $isEdit ? $unit->moto_model_id : '');
    $brandList      = ($brands ?? collect())->sortBy('name')->values();
    $currentBrandId = (string) (optional($models->firstWhere('id', (int) $currentModelId))->moto_brand_id
                        ?? old('moto_brand_filter', ''));
    $currentBranchId = (string) old('branch_id', $isEdit ? $unit->branch_id : ($defaultBranchId ?? ''));

    $authUser       = auth()->user();
    $currentCompany = $authUser->getCurrentCompany();
    $canCreateBrand = $authUser->is_super_admin || $authUser->hasPermissionInCompany('moto-brands.create', $currentCompany);
    $canCreateModel = $authUser->is_super_admin || $authUser->hasPermissionInCompany('moto-models.create', $currentCompany);
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

        <div class="col-lg-8">

            {{-- Identificación --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Identificación de la unidad</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0" for="moto_brand_filter">
                                    Marca <span class="text-danger">*</span>
                                </label>
                                @if($canCreateBrand)
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openQuickBrand()">
                                    <i class="bi bi-plus-lg me-1"></i>Nueva
                                </button>
                                @endif
                            </div>
                            <select id="moto_brand_filter" name="moto_brand_filter" class="form-select"
                                    data-placeholder="Buscar marca…" required>
                                <option value="">— Seleccionar marca —</option>
                                @foreach($brandList as $b)
                                <option value="{{ $b->id }}" {{ $currentBrandId === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0" for="moto_model_id">
                                    Modelo <span class="text-danger">*</span>
                                </label>
                                @if($canCreateModel)
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openQuickModel()">
                                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                                </button>
                                @endif
                            </div>
                            <select id="moto_model_id" name="moto_model_id"
                                    class="form-select @error('moto_model_id') is-invalid @enderror"
                                    data-placeholder="Buscar modelo…" required>
                                <option value="">— Seleccionar modelo —</option>
                                @foreach($models as $m)
                                <option value="{{ $m->id }}" data-brand="{{ $m->moto_brand_id }}"
                                        data-price="{{ $m->suggested_price }}"
                                        {{ $currentModelId === (string) $m->id ? 'selected' : '' }}>
                                    {{ $m->display_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('moto_model_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="branch_id">Sucursal</label>
                            <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">— Sin asignar —</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}"
                                        {{ $currentBranchId === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="chassis_number">
                                N° de chasis <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="chassis_number" name="chassis_number"
                                   class="form-control font-monospace @error('chassis_number') is-invalid @enderror"
                                   value="{{ old('chassis_number', $isEdit ? $unit->chassis_number : '') }}"
                                   required maxlength="100"
                                   placeholder="Ej: 9C2JC4110HR000001"
                                   style="text-transform:uppercase;"
                                   oninput="this.value=this.value.toUpperCase()">
                            @error('chassis_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="engine_number">N° de motor</label>
                            <input type="text" id="engine_number" name="engine_number"
                                   class="form-control font-monospace @error('engine_number') is-invalid @enderror"
                                   value="{{ old('engine_number', $isEdit ? $unit->engine_number : '') }}"
                                   maxlength="100"
                                   placeholder="Ej: JC41E1HR000001"
                                   style="text-transform:uppercase;"
                                   oninput="this.value=this.value.toUpperCase()">
                            @error('engine_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="color">Color</label>
                            <input type="text" id="color" name="color"
                                   class="form-control @error('color') is-invalid @enderror"
                                   value="{{ old('color', $isEdit ? $unit->color : '') }}"
                                   maxlength="80"
                                   placeholder="Ej: Rojo/Negro">
                            @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="placa">Placa</label>
                            <input type="text" id="placa" name="placa"
                                   class="form-control font-monospace @error('placa') is-invalid @enderror"
                                   value="{{ old('placa', $isEdit ? $unit->placa : '') }}"
                                   maxlength="20"
                                   placeholder="Ej: 1234-ABC"
                                   style="text-transform:uppercase;"
                                   oninput="this.value=this.value.toUpperCase()">
                            @error('placa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="year">Año</label>
                            <input type="number" id="year" name="year"
                                   class="form-control @error('year') is-invalid @enderror"
                                   value="{{ old('year', $isEdit ? $unit->year : date('Y')) }}"
                                   min="1990" max="{{ date('Y') + 2 }}"
                                   placeholder="{{ date('Y') }}">
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Precios --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-currency-dollar me-2 text-muted"></i>Precios</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="cost">
                                Costo <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="cost" name="cost"
                                       class="form-control @error('cost') is-invalid @enderror"
                                       value="{{ old('cost', $isEdit ? $unit->cost : '') }}"
                                       step="0.01" min="0" required
                                       placeholder="0.00">
                            </div>
                            @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="price">
                                Precio de venta <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="price" name="price"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $isEdit ? $unit->price : '') }}"
                                       step="0.01" min="0" required
                                       placeholder="0.00">
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Notas --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-muted"></i>Notas</h6>
                </div>
                <div class="card-body p-4">
                    <textarea id="notes" name="notes"
                              class="form-control @error('notes') is-invalid @enderror"
                              rows="3"
                              placeholder="Observaciones, condición de la unidad, accesorios incluidos...">{{ old('notes', $isEdit ? $unit->notes : '') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Confirmar</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-0">
                        Verifica que el número de chasis sea único y los precios correctos antes de guardar.
                    </p>
                </div>
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Registrar unidad' }}
                        </button>
                        <a href="{{ route('moto-units.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>

{{-- ── Modal: alta rápida de marca ──────────────────────────────────── --}}
@if($canCreateBrand)
<div class="modal fade" id="quickBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-tag me-2 text-primary"></i>Nueva marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="qbAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small" for="qb_name">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="qb_name" class="form-control" maxlength="255" placeholder="Ej: HONDA">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small" for="qb_country">País <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" id="qb_country" class="form-control" maxlength="100" placeholder="Ej: JAPÓN">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="qb_save" onclick="saveQuickBrand()">
                    <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Modal: alta rápida de modelo ─────────────────────────────────── --}}
@if($canCreateModel)
<div class="modal fade" id="quickModelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-bicycle me-2 text-primary"></i>Nuevo modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="qmoAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small" for="qmo_brand">Marca <span class="text-danger">*</span></label>
                        <select id="qmo_brand" class="form-select" data-placeholder="Buscar marca…">
                            <option value="">— Seleccionar marca —</option>
                            @foreach($brandList as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small" for="qmo_name">Nombre del modelo <span class="text-danger">*</span></label>
                        <input type="text" id="qmo_name" class="form-control" maxlength="255" placeholder="Ej: CB 190R">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold small" for="qmo_cc">Cilindrada</label>
                        <input type="text" id="qmo_cc" class="form-control" maxlength="30" placeholder="Ej: 190cc">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold small" for="qmo_year">Año</label>
                        <input type="number" id="qmo_year" class="form-control" min="1900" max="2100" placeholder="{{ date('Y') }}">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold small" for="qmo_price">Precio sugerido</label>
                        <input type="number" id="qmo_price" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="qmo_save" onclick="saveQuickModel()">
                    <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    const brandSel = document.getElementById('moto_brand_filter');
    const modelSel = document.getElementById('moto_model_id');
    if (!brandSel || !modelSel) return;

    // Select2 dispara el 'change' vía jQuery: los listeners nativos no lo ven.
    // Se escucha con jQuery cuando está disponible (cubre ambos casos).
    function onChange(el, fn) {
        if (window.jQuery) jQuery(el).on('change', fn); else el.addEventListener('change', fn);
    }

    function setSelectValue(el, value) {
        if (window.jQuery && jQuery(el).data('select2')) {
            jQuery(el).val(String(value)).trigger('change');
        } else {
            el.value = String(value);
            el.dispatchEvent(new Event('change'));
        }
    }

    // Copia de todas las opciones de modelo (con su marca y precio sugerido)
    const allOpts = Array.from(modelSel.options)
        .filter(o => o.value)
        .map(o => ({
            value: o.value,
            text:  o.textContent.trim(),
            brand: o.getAttribute('data-brand') || '',
            price: o.getAttribute('data-price') || '',
        }));

    function refreshSelect2(el) {
        if (window.jQuery && jQuery.fn.select2) {
            const $e = jQuery(el);
            if ($e.data('select2')) $e.select2('destroy');
            el.removeAttribute('data-select2-done');
            if (typeof window.initSelect2 === 'function') window.initSelect2(el.parentNode);
        }
    }

    function makeOpt(o, keepValue, related) {
        const opt = document.createElement('option');
        opt.value = o.value;
        opt.textContent = o.text;
        opt.setAttribute('data-brand', o.brand);
        opt.setAttribute('data-price', o.price || '');
        if (related) opt.setAttribute('data-related', '1');
        if (o.value === keepValue) opt.selected = true;
        return opt;
    }

    // Muestra TODOS los modelos; si hay una marca elegida, agrupa arriba los
    // relacionados (resaltados) y deja el resto en "Otros modelos". No filtra.
    function highlightModels(keepValue) {
        const brand     = brandSel.value;
        const brandName = (brandSel.options[brandSel.selectedIndex]?.textContent || '').trim();
        modelSel.innerHTML = '<option value="">— Seleccionar modelo —</option>';

        if (!brand) {
            allOpts.forEach(o => modelSel.appendChild(makeOpt(o, keepValue)));
        } else {
            const related = allOpts.filter(o => o.brand === brand);
            const others  = allOpts.filter(o => o.brand !== brand);

            if (related.length) {
                const g = document.createElement('optgroup');
                g.label = '★ ' + (brandName ? 'Modelos de ' + brandName : 'Relacionados') + ' (' + related.length + ')';
                related.forEach(o => g.appendChild(makeOpt(o, keepValue, true)));
                modelSel.appendChild(g);
            }
            if (others.length) {
                const g = document.createElement('optgroup');
                g.label = 'Otros modelos';
                others.forEach(o => g.appendChild(makeOpt(o, keepValue)));
                modelSel.appendChild(g);
            }
        }
        refreshSelect2(modelSel);
    }

    // Al cambiar la marca, re-resaltar SIN perder el modelo ya seleccionado.
    onChange(brandSel, () => highlightModels(modelSel.value));

    // Estado inicial (edición / old input): resaltar conservando el modelo actual.
    // Se espera a que el select2 global ya esté aplicado (jQuery ready) para reconstruirlo.
    if (window.jQuery) jQuery(() => highlightModels(modelSel.value));
    else document.addEventListener('DOMContentLoaded', () => highlightModels(modelSel.value));

    // ── Auto-rellenar el Precio de la unidad con el precio sugerido del modelo ──
    // Se sugiere al elegir un modelo; si el usuario ya escribió un precio, no se pisa.
    const priceInput = document.getElementById('price');
    let priceTouched = !!(priceInput && priceInput.value.trim() !== '');
    if (priceInput) priceInput.addEventListener('input', () => { priceTouched = true; });

    function suggestPrice() {
        if (!priceInput || priceTouched) return;
        const opt = modelSel.options[modelSel.selectedIndex];
        const sp  = opt ? parseFloat(opt.getAttribute('data-price') || '') : NaN;
        if (!isNaN(sp) && sp > 0) priceInput.value = sp.toFixed(2);
    }
    // Se vuelve a enganchar tras cada reconstrucción del select (select2 destroy/init
    // no afecta al <select> nativo, así que basta con un solo listener).
    onChange(modelSel, suggestPrice);

    // ── Alta rápida de marca / modelo ────────────────────────────────
    const QUICK_CSRF = '{{ csrf_token() }}';

    function quickToast(msg) {
        let cont = document.getElementById('quickToastCont');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'quickToastCont';
            cont.className = 'toast-container position-fixed top-0 end-0 p-3';
            cont.style.zIndex = '1090';
            document.body.appendChild(cont);
        }
        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-success border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle me-2"></i>' + msg + '</div>'
                     + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        cont.appendChild(el);
        const t = bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        t.show();
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

    // Agrega la marca a los selects (principal y del modal de modelo) si no existe.
    function addBrandOption(id, name) {
        [brandSel, document.getElementById('qmo_brand')].forEach(sel => {
            if (sel && !sel.querySelector('option[value="' + id + '"]')) {
                sel.appendChild(new Option(name, id));
            }
        });
    }

    // ── Marca ──
    window.openQuickBrand = function () {
        ['qb_name', 'qb_country'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('qbAlert').classList.add('d-none');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('quickBrandModal')).show();
        setTimeout(() => document.getElementById('qb_name').focus(), 300);
    };

    window.saveQuickBrand = function () {
        const name = document.getElementById('qb_name').value.trim();
        const alertBox = document.getElementById('qbAlert');
        if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

        quickPost('{{ route('moto-brands.quick-store') }}', {
            name: name,
            country: document.getElementById('qb_country').value.trim(),
        }, 'qbAlert', document.getElementById('qb_save'), (d) => {
            addBrandOption(d.brand.id, d.brand.name);
            setSelectValue(brandSel, d.brand.id);
            bootstrap.Modal.getInstance(document.getElementById('quickBrandModal'))?.hide();
            quickToast('Marca «' + d.brand.name + '» creada y seleccionada.');
        });
    };

    // ── Modelo ──
    window.openQuickModel = function () {
        ['qmo_name', 'qmo_cc', 'qmo_year', 'qmo_price'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('qmoAlert').classList.add('d-none');
        const qb = document.getElementById('qmo_brand');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('quickModelModal')).show();
        // La marca del modal arranca con la marca elegida en el formulario.
        setTimeout(() => {
            setSelectValue(qb, brandSel.value || '');
            document.getElementById(brandSel.value ? 'qmo_name' : 'qmo_brand').focus();
        }, 300);
    };

    window.saveQuickModel = function () {
        const brandId = document.getElementById('qmo_brand').value;
        const name    = document.getElementById('qmo_name').value.trim();
        const alertBox = document.getElementById('qmoAlert');
        if (!brandId) { alertBox.textContent = 'Selecciona la marca del modelo.'; alertBox.classList.remove('d-none'); return; }
        if (!name)    { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

        quickPost('{{ route('moto-models.quick-store') }}', {
            moto_brand_id:   brandId,
            name:            name,
            engine_cc:       document.getElementById('qmo_cc').value.trim(),
            year:            document.getElementById('qmo_year').value || null,
            suggested_price: document.getElementById('qmo_price').value || null,
        }, 'qmoAlert', document.getElementById('qmo_save'), (d) => {
            const m = d.model;
            if (!allOpts.some(o => o.value === String(m.id))) {
                allOpts.push({ value: String(m.id), text: m.display_name, brand: String(m.moto_brand_id), price: m.suggested_price || '' });
                allOpts.sort((a, b) => a.text.localeCompare(b.text));
            }
            // Seleccionar la marca del modelo (reconstruye la lista) y luego el modelo.
            if (brandSel.value !== String(m.moto_brand_id)) {
                setSelectValue(brandSel, m.moto_brand_id);
            } else {
                highlightModels(modelSel.value);
            }
            setSelectValue(modelSel, m.id);
            bootstrap.Modal.getInstance(document.getElementById('quickModelModal'))?.hide();
            quickToast('Modelo «' + m.display_name + '» creado y seleccionado.');
        });
    };
})();
</script>
@endpush
