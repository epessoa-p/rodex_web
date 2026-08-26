@extends('layouts.app')
@section('title', 'Nuevo vehículo')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bicycle me-2 text-danger"></i>Nuevo vehículo</h1>
            <p class="text-muted mb-0 small">Registra un vehículo de cliente.</p>
        </div>
        <a href="{{ route('vehicles.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('sales.vehicles.form')

</div>
@endsection
