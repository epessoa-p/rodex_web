@php
    $isEdit = isset($brand);
    $action = $isEdit ? route('moto-brands.update', $brand) : route('moto-brands.store');
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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-tag me-2 text-muted"></i>Datos de la marca</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $brand->name : '') }}"
                                   required maxlength="100"
                                   placeholder="Ej: Honda, Yamaha, Suzuki">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="country">País de origen</label>
                            <input type="text" id="country" name="country"
                                   class="form-control @error('country') is-invalid @enderror"
                                   value="{{ old('country', $isEdit ? $brand->country : '') }}"
                                   maxlength="100"
                                   placeholder="Ej: Japón">
                            @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                               {{ old('active', $isEdit ? $brand->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Marca activa</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Las marcas inactivas no aparecen al registrar nuevos modelos.</p>
                </div>
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear marca' }}
                        </button>
                        <a href="{{ route('moto-brands.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>
