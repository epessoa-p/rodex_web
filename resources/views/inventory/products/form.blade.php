@php
    $isEdit = isset($product);
    $action = $isEdit ? route('products.update', $product) : route('products.store');
    $method = $isEdit ? 'PUT' : 'POST';
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

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- ─── LEFT COLUMN ──────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Información básica --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información básica</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        @if(isset($companies) && $companies->count() > 1)
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="company_id">Empresa</label>
                            <select name="company_id" id="company_id" class="form-select">
                                @foreach($companies as $c)
                                <option value="{{ $c->id }}"
                                    {{ (string) old('company_id', $isEdit ? $product->company_id : '') === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre del producto <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $product->name : '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Pastilla de freno trasera Honda CG 150">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="category_id">
                                Categoría
                                <a href="{{ route('product-categories.create') }}" class="ms-1 small text-muted" title="Crear nueva categoría" target="_blank">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                            </label>
                            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">— Sin categoría —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ (string) old('category_id', $isEdit ? $product->category_id : '') === (string) $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="brand_id">
                                Marca
                                <a href="{{ route('product-brands.create') }}" class="ms-1 small text-muted" title="Crear nueva marca" target="_blank">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                            </label>
                            <select name="brand_id" id="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                                <option value="">— Sin marca —</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}"
                                    {{ (string) old('brand_id', $isEdit ? $product->brand_id : '') === (string) $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="sku">SKU</label>
                            <input type="text" id="sku" name="sku"
                                   class="form-control @error('sku') is-invalid @enderror"
                                   value="{{ old('sku', $isEdit ? $product->sku : '') }}"
                                   maxlength="100"
                                   placeholder="Se autogenera si lo dejas vacío">
                            <div class="form-text">Opcional. Si lo dejas vacío se genera automáticamente ({{ config('inventory.code_prefix') }}-00001).</div>
                            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="code">Código</label>
                            <input type="text" id="code" name="code"
                                   class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $isEdit ? $product->code : '') }}"
                                   maxlength="100"
                                   placeholder="Código interno / referencia">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="barcode">Código de barras</label>
                            <input type="text" id="barcode" name="barcode"
                                   class="form-control @error('barcode') is-invalid @enderror"
                                   value="{{ old('barcode', $isEdit ? $product->barcode : '') }}"
                                   maxlength="100"
                                   placeholder="7501234567890">
                            @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="unit">
                                Unidad de medida <span class="text-danger">*</span>
                                <a href="{{ route('product-units.create') }}" class="ms-1 small text-muted" title="Crear nueva unidad" target="_blank">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                            </label>
                            @php
                                $currentUnit = old('unit', $isEdit ? $product->unit : config('inventory.default_unit', 'Unidad'));
                                $unitNames   = ($units ?? collect())->pluck('name');
                            @endphp
                            <select id="unit" name="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                @foreach($unitNames as $u)
                                <option value="{{ $u }}" {{ (string) $currentUnit === (string) $u ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                                {{-- La unidad actual del producto puede no estar en el catálogo (legado): la conservamos. --}}
                                @if(filled($currentUnit) && !$unitNames->contains($currentUnit))
                                <option value="{{ $currentUnit }}" selected>{{ $currentUnit }}</option>
                                @endif
                            </select>
                            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="moto_models">
                                Modelos compatibles
                                <a href="{{ route('moto-models.create') }}" class="ms-1 small text-muted" title="Crear nuevo modelo" target="_blank">
                                    <i class="bi bi-plus-circle"></i>
                                </a>
                            </label>
                            @php
                                $selectedModels = old('moto_models', $isEdit ? $product->motoModels->pluck('id')->all() : []);
                                $selectedModels = array_map('strval', (array) $selectedModels);
                            @endphp
                            <select name="moto_models[]" id="moto_models" multiple
                                    class="form-select @error('moto_models') is-invalid @enderror"
                                    data-placeholder="Selecciona los modelos de moto compatibles…">
                                @foreach($motoModels as $m)
                                <option value="{{ $m->id }}" {{ in_array((string) $m->id, $selectedModels, true) ? 'selected' : '' }}>
                                    {{ $m->display_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('moto_models')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('moto_models.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="description">Descripción</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Descripción técnica del producto...">{{ old('description', $isEdit ? $product->description : '') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                       {{ old('active', $isEdit ? $product->active : true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="active">Producto activo</label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Precios y stock --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-currency-dollar me-2 text-muted"></i>Precios y stock</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="cost">
                                Precio de compra <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">{{ currency_symbol() }}</span>
                                <input type="number" id="cost" name="cost" step="0.01" min="0"
                                       class="form-control border-start-0 @error('cost') is-invalid @enderror"
                                       value="{{ old('cost', $isEdit ? $product->cost : '') }}"
                                       required placeholder="0.00"
                                       oninput="calcMargin()">
                                @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="price">
                                Precio de venta <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">{{ currency_symbol() }}</span>
                                <input type="number" id="price" name="price" step="0.01" min="0"
                                       class="form-control border-start-0 @error('price') is-invalid @enderror"
                                       value="{{ old('price', $isEdit ? $product->price : '') }}"
                                       required placeholder="0.00"
                                       oninput="calcMargin()">
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Margen estimado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">%</span>
                                <div id="marginDisplay" class="form-control border-start-0 bg-light fw-bold text-success">—</div>
                            </div>
                            <div class="form-text text-muted">Se calcula automáticamente.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="min_stock">Stock mínimo</label>
                            <input type="number" id="min_stock" name="min_stock" step="0.01" min="0"
                                   class="form-control @error('min_stock') is-invalid @enderror"
                                   value="{{ old('min_stock', $isEdit ? $product->min_stock : '') }}"
                                   placeholder="0">
                            @error('min_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-muted">Alerta cuando el stock baje de este valor.</div>
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- end col-lg-8 --}}

        {{-- ─── RIGHT COLUMN ─────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-images me-2 text-muted"></i>Fotos del producto</h6>
                </div>
                <div class="card-body p-4">

                    {{-- Existing photos (edit mode) --}}
                    @if($isEdit && $product->photos->count() > 0)
                    <p class="text-muted small fw-semibold mb-2 text-uppercase" style="letter-spacing:.04em">Fotos actuales</p>
                    <div class="row g-2 mb-4">
                        @foreach($product->photos as $photo)
                        <div class="col-4">
                            <div class="photo-thumb-wrap position-relative rounded-2 overflow-hidden border {{ $photo->is_main ? 'border-danger border-2' : '' }}"
                                 style="aspect-ratio:1;background:#f8f9fa">
                                <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                     class="w-100 h-100 object-fit-cover">
                                @if($photo->is_main)
                                <span class="position-absolute bottom-0 start-0 end-0 text-center bg-danger text-white"
                                      style="font-size:.65rem;padding:2px 0">Principal</span>
                                @endif
                            </div>
                            <div class="mt-1">
                                <label class="d-flex align-items-center gap-1 small text-muted">
                                    <input type="checkbox" name="delete_photos[]" value="{{ $photo->id }}" class="form-check-input m-0">
                                    Eliminar
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Upload new photos --}}
                    <x-media-upload name="photos[]" :multiple="true" :bare="true"
                        accent="#e63946" :max-mb="3" :max-files="8"
                        drop-text="Arrastra fotos aquí"
                        hint="JPG, PNG, WebP · máx. 3MB · hasta 8 fotos" />

                    <div class="form-text text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        La primera foto seleccionada será la principal.
                    </div>

                </div>
            </div>
        </div>{{-- end col-lg-4 --}}

    </div>{{-- end row --}}

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('products.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5">
            <i class="bi bi-check-lg me-1"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Crear producto' }}
        </button>
    </div>

</form>

@push('styles')
<style>
.upload-area:hover { border-color: var(--brand-red) !important; background: rgba(230,57,70,.02); }
.object-fit-cover { object-fit: cover; }
.border-dashed { border-style: dashed !important; }
</style>
@endpush

@push('scripts')
<script>
function calcMargin() {
    const cost  = parseFloat(document.getElementById('cost').value)  || 0;
    const price = parseFloat(document.getElementById('price').value) || 0;
    const el    = document.getElementById('marginDisplay');
    if (price > 0 && cost >= 0) {
        const margin = ((price - cost) / price * 100);
        el.textContent = margin.toFixed(1) + '%';
        el.className = 'form-control border-start-0 bg-light fw-bold ' + (margin >= 0 ? 'text-success' : 'text-danger');
    } else {
        el.textContent = '—';
        el.className = 'form-control border-start-0 bg-light fw-bold text-muted';
    }
}
calcMargin();

// Las fotos del producto ahora se gestionan con el componente media-upload.
</script>
@endpush
