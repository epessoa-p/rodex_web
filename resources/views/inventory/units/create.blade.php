@extends('layouts.app')
@section('title', 'Nueva unidad')
@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-plus-circle me-2 text-danger"></i>Nueva unidad</h1>
            <p class="text-muted mb-0 small">Agrega una unidad de medida para los productos.</p>
        </div>
        <a href="{{ route('product-units.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
    @include('inventory.units.form')
</div>
@endsection
