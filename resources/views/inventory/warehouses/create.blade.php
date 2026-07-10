@extends('layouts.app')
@section('title', 'Nuevo almacén')
@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-plus-circle me-2 text-danger"></i>Nuevo almacén</h1>
            <p class="text-muted mb-0 small">Registra un nuevo espacio de almacenamiento.</p>
        </div>
        <a href="{{ route('warehouses.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
    @include('inventory.warehouses.form')
</div>
@endsection
