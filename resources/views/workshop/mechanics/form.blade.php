@php
    $isEdit = isset($mechanic);
    $action = $isEdit ? route('mechanics.update', $mechanic) : route('mechanics.store');
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
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2 text-muted"></i>Datos del mecánico</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $mechanic->name : '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Juan Pérez">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="specialty">Especialidad</label>
                            <input type="text" id="specialty" name="specialty"
                                   class="form-control @error('specialty') is-invalid @enderror"
                                   value="{{ old('specialty', $isEdit ? $mechanic->specialty : '') }}"
                                   maxlength="255"
                                   placeholder="Ej: Motor, Frenos, Electricidad...">
                            @error('specialty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="phone">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $isEdit ? $mechanic->phone : '') }}"
                                       maxlength="50"
                                       placeholder="+591 700 00000">
                            </div>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="commission_rate">Comisión (%)</label>
                            <div class="input-group">
                                <input type="number" id="commission_rate" name="commission_rate"
                                       step="0.01" min="0" max="100"
                                       class="form-control @error('commission_rate') is-invalid @enderror"
                                       value="{{ old('commission_rate', $isEdit ? $mechanic->commission_rate : '') }}"
                                       placeholder="0.00">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            @error('commission_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                               {{ old('active', $isEdit ? $mechanic->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Mecánico activo</label>
                    </div>
                    <p class="text-muted small mt-2 mb-4">Los mecánicos inactivos no aparecen en las órdenes de trabajo.</p>

                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear mecánico' }}
                        </button>
                        <a href="{{ route('mechanics.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
