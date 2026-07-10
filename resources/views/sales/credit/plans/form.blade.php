@php
    $isEdit = isset($plan);
    $action = $isEdit ? route('payment-plans.update', $plan) : route('payment-plans.store');
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
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

        {{-- Main column --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check me-2 text-muted"></i>Datos del plan</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="name">
                                Nombre del plan <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                                   value="{{ old('name', $isEdit ? $plan->name : '') }}"
                                   required maxlength="255"
                                   placeholder="Ej: Plan 12 meses sin interés">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="number_of_installments">
                                N° de cuotas <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="number_of_installments" name="number_of_installments"
                                   class="form-control @error('number_of_installments') is-invalid @enderror"
                                   value="{{ old('number_of_installments', $isEdit ? $plan->number_of_installments : '') }}"
                                   required min="1" max="360"
                                   placeholder="Ej: 12">
                            @error('number_of_installments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="frequency_days">
                                Frecuencia (días) <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="frequency_days" name="frequency_days"
                                   class="form-control @error('frequency_days') is-invalid @enderror"
                                   value="{{ old('frequency_days', $isEdit ? $plan->frequency_days : '') }}"
                                   required min="1"
                                   placeholder="Ej: 30">
                            <div class="form-text text-muted small">30 = mensual &nbsp;·&nbsp; 15 = quincenal &nbsp;·&nbsp; 7 = semanal</div>
                            @error('frequency_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="interest_rate">
                                Interés (%) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" id="interest_rate" name="interest_rate"
                                       class="form-control @error('interest_rate') is-invalid @enderror"
                                       value="{{ old('interest_rate', $isEdit ? $plan->interest_rate : '0') }}"
                                       required min="0" step="0.01" max="100"
                                       placeholder="0.00">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            <div class="form-text text-muted small">0 = sin interés</div>
                            @error('interest_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Right sidebar --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $plan->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Plan activo</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Los planes inactivos no aparecen al crear ventas a crédito.</p>
                </div>
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear plan' }}
                        </button>
                        <a href="{{ route('payment-plans.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
