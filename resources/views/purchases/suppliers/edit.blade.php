@extends('layouts.app')
@section('title', 'Editar proveedor: ' . $supplier->name)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-truck me-2 text-danger"></i>Editar proveedor</h1>
            <p class="text-muted mb-0 small">{{ $supplier->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-light border">
                <i class="bi bi-eye me-1"></i>Ver detalle
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @include('purchases.suppliers.form')

</div>
@endsection
