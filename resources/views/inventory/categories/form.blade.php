@php
    $isEdit = isset($category);
    $action = $isEdit ? route('product-categories.update', $category) : route('product-categories.store');
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
            <h6 class="mb-0 fw-semibold"><i class="bi bi-tag me-2 text-muted"></i>Datos de la categoría</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">

                @if(isset($companies) && $companies->count() > 1)
                <div class="col-12">
                    <label class="form-label fw-semibold" for="company_id">Empresa</label>
                    <select name="company_id" id="company_id" class="form-select">
                        @foreach($companies as $c)
                        <option value="{{ $c->id }}"
                            {{ (string) old('company_id', $isEdit ? $category->company_id : '') === (string) $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-12">
                    <label class="form-label fw-semibold" for="name">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name" name="name"
                           class="form-control form-control-lg @error('name') is-invalid @enderror"
                           value="{{ old('name', $isEdit ? $category->name : '') }}"
                           required maxlength="255"
                           placeholder="Ej: Frenos, Motor, Eléctrico, Transmisión...">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="description">Descripción</label>
                    <textarea id="description" name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="3" maxlength="500"
                              placeholder="Descripción breve de la categoría (opcional)...">{{ old('description', $isEdit ? $category->description : '') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text text-muted">Máximo 500 caracteres.</div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $category->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Categoría activa</label>
                    </div>
                    <div class="form-text text-muted">Las categorías inactivas no aparecen al crear productos.</div>
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('product-categories.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Guardar cambios' : 'Crear categoría' }}
        </button>
    </div>
</form>
