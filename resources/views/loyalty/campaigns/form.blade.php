@extends('layouts.app')
@section('title', 'Fidelización · ' . ($campaign->exists ? 'Editar campaña' : 'Nueva campaña'))
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-megaphone me-2 text-danger"></i>{{ $campaign->exists ? 'Editar campaña' : 'Nueva campaña' }}</h1>
        </div>
        <a href="{{ route('loyalty.campaigns.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ $campaign->exists ? route('loyalty.campaigns.update', $campaign) : route('loyalty.campaigns.store') }}" method="POST">
        @csrf
        @if($campaign->exists) @method('PUT') @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" placeholder="Ej. Doble puntos de fin de semana" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Multiplicador <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">x</span>
                            <input type="number" name="multiplier" class="form-control" min="1" max="99" step="0.25"
                                   value="{{ old('multiplier', $campaign->multiplier ?? 2) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Desde <span class="text-danger">*</span></label>
                        <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', optional($campaign->starts_at)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Hasta <span class="text-danger">*</span></label>
                        <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', optional($campaign->ends_at)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1"
                                   {{ old('active', $campaign->active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">Activa</label>
                        </div>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 py-2" style="font-size:.8rem;">
                    <i class="bi bi-info-circle me-1 text-muted"></i>
                    Durante la vigencia, los puntos acumulados en cada venta se multiplican por este factor. Si hay varias campañas activas, se aplica el multiplicador más alto.
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('loyalty.campaigns.index') }}" class="btn btn-light border">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Guardar</button>
        </div>
    </form>
</div>
@endsection
