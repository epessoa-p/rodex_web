@php
    $isEdit = isset($warehouse) && $warehouse !== null;
    $action = $isEdit ? route('warehouses.update', $warehouse) : route('warehouses.store');
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

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-muted"></i>Datos del almacén</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">

                @if($companies->count() > 1)
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="company_id">Empresa</label>
                    <select name="company_id" id="company_id" class="form-select">
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}"
                            {{ (string) old('company_id', $isEdit ? $warehouse->company_id : '') === (string) $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="{{ $companies->count() > 1 ? 'col-md-6' : 'col-md-8' }}">
                    <label class="form-label fw-semibold" for="name">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $isEdit ? $warehouse->name : '') }}"
                           required maxlength="255"
                           placeholder="Ej: Almacén Central, Depósito Norte...">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="code">
                        Código <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="code" name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $isEdit ? $warehouse->code : '') }}"
                           required maxlength="50"
                           placeholder="ALM-01">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="location">Ubicación</label>
                    <input type="text" id="location" name="location"
                           class="form-control @error('location') is-invalid @enderror"
                           value="{{ old('location', $isEdit ? $warehouse->location : '') }}"
                           maxlength="255"
                           placeholder="Dirección o descripción de la ubicación física...">
                    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="description">Descripción</label>
                    <textarea id="description" name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="3"
                              placeholder="Información adicional del almacén (opcional)...">{{ old('description', $isEdit ? $warehouse->description : '') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $warehouse->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Almacén activo</label>
                    </div>
                    <div class="form-text text-muted">La vinculación con sucursal se define desde la sucursal (almacén principal).</div>
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('warehouses.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Guardar cambios' : 'Crear almacén' }}
        </button>
    </div>
</form>
