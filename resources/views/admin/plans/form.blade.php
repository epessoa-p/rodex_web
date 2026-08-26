@extends('layouts.app')

@section('title', $plan->exists ? 'Editar plan' : 'Nuevo plan')

@section('page')
@php $isEdit = $plan->exists; @endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-box-seam"></i> {{ $isEdit ? 'Editar plan' : 'Nuevo plan' }}</h1>
            <p class="text-muted mb-0 small">Define el precio, los módulos incluidos y los límites de uso.</p>
        </div>
        <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('plans.update', $plan) : route('plans.store') }}" method="POST">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required maxlength="255">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug', $plan->slug) }}" placeholder="auto" data-no-search>
                                <div class="form-text">Vacío = se genera del nombre.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Descripción</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description', $plan->description) }}" maxlength="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Precio <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ config('inventory.currency') }}</span>
                                    <input type="number" name="price" class="form-control" value="{{ old('price', $plan->price ?? 0) }}" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Facturación <span class="text-danger">*</span></label>
                                <select name="billing_period" class="form-select" required>
                                    @foreach(\App\Models\Plan::BILLING_PERIODS as $value => $label)
                                        <option value="{{ $value }}" {{ old('billing_period', $plan->billing_period ?? 'monthly') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Días de prueba <span class="text-danger">*</span></label>
                                <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $plan->trial_days ?? 15) }}" min="0" max="365" required>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-sliders me-2 text-muted"></i>Límites de uso</h6>
                        <div class="row g-3">
                            @foreach([['max_users','Usuarios'],['max_branches','Sucursales'],['max_products','Productos']] as [$field, $label])
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" class="form-control" value="{{ old($field, $plan->$field) }}" min="1" placeholder="Ilimitado">
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text mt-2">Deja un límite vacío para que sea <strong>ilimitado</strong>.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-semibold mb-3"><i class="bi bi-grid-3x3-gap me-2 text-muted"></i>Módulos incluidos</h6>
                        @php $selected = old('features', $plan->features ?? []); @endphp
                        <div class="row g-2">
                            @foreach(\App\Models\Plan::MODULES as $key => $label)
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}"
                                               id="feat_{{ $key }}" {{ in_array($key, $selected, true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="feat_{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" value="1" id="active"
                                   {{ old('active', $plan->active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="active">Plan activo</label>
                        </div>
                        <div class="form-text">Solo los planes activos se pueden asignar a empresas.</div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> {{ $isEdit ? 'Guardar cambios' : 'Crear plan' }}</button>
                    <a href="{{ route('plans.index') }}" class="btn btn-light border">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
