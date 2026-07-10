@extends('layouts.app')
@php $isEdit = $service->exists; @endphp
@section('title', $isEdit ? 'Editar servicio' : 'Nuevo servicio')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-receipt-cutoff me-2 text-danger"></i>{{ $isEdit ? 'Editar servicio' : 'Nuevo servicio' }}</h1>
            <p class="text-muted mb-0 small">Concepto de gasto reutilizable.</p>
        </div>
        <a href="{{ route('expense-services.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card border-0 shadow-sm" style="max-width:620px;">
        <div class="card-body p-4">
            <form action="{{ $isEdit ? route('expense-services.update', $service) : route('expense-services.store') }}" method="POST">
                @csrf
                @if($isEdit)@method('PUT')@endif
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold" for="name">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $service->name) }}" required placeholder="Ej. Internet Tigo, Luz CRE, Hosting…">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="type">Tipo <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select">
                            @foreach(\App\Models\ExpenseService::TYPES as $k => $lbl)
                            <option value="{{ $k }}" {{ old('type', $service->type) === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="default_amount">Monto sugerido</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Bs.</span>
                            <input type="number" id="default_amount" name="default_amount" class="form-control" step="0.01" min="0" value="{{ old('default_amount', $service->default_amount) }}" placeholder="Opcional">
                        </div>
                        <div class="form-text">Se usa para prellenar el monto en el modal de gasto.</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $service->active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Activo</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="notes">Notas</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Opcional">{{ old('notes', $service->notes) }}</textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Guardar cambios' : 'Crear servicio' }}</button>
                    <a href="{{ route('expense-services.index') }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
