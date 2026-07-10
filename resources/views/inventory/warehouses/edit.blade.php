@extends('layouts.app')
@section('title', 'Editar: ' . $warehouse->name)
@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-pencil me-2 text-danger"></i>Editar almacén</h1>
            <p class="text-muted mb-0 small">{{ $warehouse->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-light border">
                <i class="bi bi-eye me-1"></i> Ver
            </a>
            <a href="{{ route('warehouses.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>
    @include('inventory.warehouses.form')
</div>
@endsection
