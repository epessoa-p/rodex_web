@php
    $isEdit = isset($model);
    $action = $isEdit ? route('moto-models.update', $model) : route('moto-models.store');
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

<form action="{{ $action }}" method="POST">
    @csrf
    @method($method)

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bicycle me-2 text-muted"></i>Datos del modelo</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="moto_brand_id">
                                Marca <span class="text-danger">*</span>
                            </label>
                            <select id="moto_brand_id" name="moto_brand_id"
                                    class="form-select @error('moto_brand_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar marca —</option>
                                @foreach($brands as $brand)
                                <option value="{{ $brand->id }}"
                                        {{ old('moto_brand_id', $isEdit ? $model->moto_brand_id : '') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('moto_brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="name">
                                Nombre del modelo <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $model->name : '') }}"
                                   required maxlength="150"
                                   placeholder="Ej: CBF150, YBR125">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="engine_cc">Cilindrada (cc)</label>
                            <div class="input-group">
                                <input type="number" id="engine_cc" name="engine_cc"
                                       class="form-control @error('engine_cc') is-invalid @enderror"
                                       value="{{ old('engine_cc', $isEdit ? $model->engine_cc : '') }}"
                                       min="1" max="9999"
                                       placeholder="150">
                                <span class="input-group-text bg-light text-muted">cc</span>
                            </div>
                            @error('engine_cc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="year">Año</label>
                            <input type="number" id="year" name="year"
                                   class="form-control @error('year') is-invalid @enderror"
                                   value="{{ old('year', $isEdit ? $model->year : '') }}"
                                   min="1990" max="{{ date('Y') + 2 }}"
                                   placeholder="{{ date('Y') }}">
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="suggested_price">
                                Precio sugerido
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="suggested_price" name="suggested_price"
                                       class="form-control @error('suggested_price') is-invalid @enderror"
                                       value="{{ old('suggested_price', $isEdit ? $model->suggested_price : '') }}"
                                       step="0.01" min="0"
                                       placeholder="0.00">
                            </div>
                            @error('suggested_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="description">Descripción</label>
                            <textarea id="description" name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Características generales, observaciones...">{{ old('description', $isEdit ? $model->description : '') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $model->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Modelo activo</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Los modelos inactivos no aparecen al registrar nuevas unidades.</p>
                </div>
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear modelo' }}
                        </button>
                        <a href="{{ route('moto-models.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>
