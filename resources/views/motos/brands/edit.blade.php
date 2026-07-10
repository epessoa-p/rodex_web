@extends('layouts.app')
@section('title', 'Editar Marca: ' . $brand->name)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-tag me-2 text-danger"></i>Editar Marca</h1>
            <p class="text-muted mb-0 small">Modifica los datos de <strong>{{ $brand->name }}</strong>.</p>
        </div>
        <a href="{{ route('moto-brands.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('motos.brands.form')

</div>
@endsection
