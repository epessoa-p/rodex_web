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
                            <label class="form-label fw-semibold" for="moto_brand_filter">
                                Marca <span class="text-danger">*</span>
                            </label>
                            <select id="moto_brand_filter" name="moto_brand_filter" class="form-select" data-no-search required>
                                <option value="">— Seleccionar marca —</option>
                                @foreach($brandList as $b)
                                <option value="{{ $b->id }}" {{ $currentBrandId === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="moto_model_id">
                                Modelo <span class="text-danger">*</span>
                            </label>
                            <select id="moto_model_id" name="moto_model_id"
                                    class="form-select @error('moto_model_id') is-invalid @enderror" required>
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
                                        {{ old('branch_id', $isEdit ? $unit->branch_id : '') == $b->id ? 'selected' : '' }}>
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

@push('scripts')
<script>
(function () {
    const brandSel = document.getElementById('moto_brand_filter');
    const modelSel = document.getElementById('moto_model_id');
    if (!brandSel || !modelSel) return;

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
    brandSel.addEventListener('change', () => highlightModels(modelSel.value));

    // Estado inicial (edición / old input): resaltar conservando el modelo actual.
    document.addEventListener('DOMContentLoaded', () => highlightModels(modelSel.value));

    // ── Auto-rellenar el Precio de la unidad con el precio sugerido del modelo ──
    // Se sugiere al elegir un modelo; si el usuario ya escribió un precio, no se pisa.
    const priceInput = document.getElementById('price');
    let priceTouched = !!(priceInput && priceInput.value.trim() !== '');
    if (priceInput) priceInput.addEventListener('input', () => { priceTouched = true; });

    modelSel.addEventListener('change', function () {
        if (!priceInput || priceTouched) return;
        const opt = modelSel.options[modelSel.selectedIndex];
        const sp  = opt ? parseFloat(opt.getAttribute('data-price') || '') : NaN;
        if (!isNaN(sp) && sp > 0) priceInput.value = sp.toFixed(2);
    });
})();
</script>
@endpush
