@extends('layouts.app')
@section('title', 'Editar vehículo: ' . $vehicle->brand . ' ' . $vehicle->model)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-pencil me-2 text-danger"></i>Editar vehículo</h1>
            <p class="text-muted mb-0 small">
                {{ $vehicle->brand }} {{ $vehicle->model }}
                @if($vehicle->plate)
                <span class="badge bg-light text-dark border ms-1" style="font-size:.75rem;">{{ $vehicle->plate }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('sales.vehicles.form')

</div>
@endsection
