@extends('layouts.app')
@section('title', 'Editar Unidad: ' . $unit->chassis_number)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-seam me-2 text-danger"></i>Editar Unidad</h1>
            <p class="text-muted mb-0 small">Modifica los datos de <strong>{{ $unit->display_name }}</strong>.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('moto-units.show', $unit) }}" class="btn btn-light border">
                <i class="bi bi-eye me-1"></i>Ver detalle
            </a>
            <a href="{{ route('moto-units.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @include('motos.units.form')

</div>
@endsection
