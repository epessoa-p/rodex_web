@php
    $isEdit = isset($service);
    $action = $isEdit ? route('services.update', $service) : route('services.store');
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
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2 text-muted"></i>Datos del servicio</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre del servicio <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $service->name : '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Cambio de aceite y filtro">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="description">Descripción</label>
                            <textarea id="description" name="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Detalle del servicio...">{{ old('description', $isEdit ? $service->description : '') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="price">
                                Precio <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="price" name="price"
                                       step="0.01" min="0"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $isEdit ? $service->price : '') }}"
                                       required placeholder="0.00">
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="estimated_time">Tiempo estimado</label>
                            <input type="text" id="estimated_time" name="estimated_time"
                                   class="form-control @error('estimated_time') is-invalid @enderror"
                                   value="{{ old('estimated_time', $isEdit ? $service->estimated_time : '') }}"
                                   placeholder="Ej: 2 horas">
                            @error('estimated_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $service->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Servicio activo</label>
                    </div>
                    <p class="text-muted small mt-2 mb-4">Los servicios inactivos no aparecen en las órdenes de trabajo.</p>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear servicio' }}
                        </button>
                        <a href="{{ route('services.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
