@php
    $isEdit = isset($supplier);
    $action = $isEdit ? route('suppliers.update', $supplier) : route('suppliers.store');
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

        {{-- Left column --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-building me-2 text-muted"></i>Datos del proveedor</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre / Razón social <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $supplier->name : '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Distribuidora Honda Bolivia S.A.">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="nit">NIT</label>
                            <input type="text" id="nit" name="nit"
                                   class="form-control @error('nit') is-invalid @enderror"
                                   value="{{ old('nit', $isEdit ? $supplier->nit : '') }}"
                                   maxlength="50"
                                   placeholder="1234567890">
                            @error('nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="contact_name">Persona de contacto</label>
                            <input type="text" id="contact_name" name="contact_name"
                                   class="form-control @error('contact_name') is-invalid @enderror"
                                   value="{{ old('contact_name', $isEdit ? $supplier->contact_name : '') }}"
                                   maxlength="255"
                                   placeholder="Nombre del contacto">
                            @error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="phone">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $isEdit ? $supplier->phone : '') }}"
                                       maxlength="50"
                                       placeholder="+591 700 00000">
                            </div>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">Correo electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $isEdit ? $supplier->email : '') }}"
                                       maxlength="255"
                                       placeholder="contacto@proveedor.com">
                            </div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="address">Dirección</label>
                            <input type="text" id="address" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $isEdit ? $supplier->address : '') }}"
                                   maxlength="500"
                                   placeholder="Dirección completa del proveedor">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Condiciones de pago, observaciones, etc.">{{ old('notes', $isEdit ? $supplier->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- Right column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $supplier->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Proveedor activo</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Los proveedores inactivos no aparecen en los formularios de compra.</p>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('suppliers.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5">
            <i class="bi bi-check-lg me-1"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Crear proveedor' }}
        </button>
    </div>

</form>
