@extends('layouts.app')
@section('title', 'Nuevo producto')
@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-plus-circle me-2 text-danger"></i>Nuevo producto</h1>
            <p class="text-muted mb-0 small">Agrega un nuevo repuesto o accesorio al catálogo.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
    @include('inventory.products.form')
</div>
@endsection
