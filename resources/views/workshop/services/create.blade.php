@extends('layouts.app')
@section('title', 'Nuevo servicio')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gear me-2 text-danger"></i>Nuevo servicio</h1>
            <p class="text-muted mb-0 small">Registra un nuevo servicio en el catálogo del taller.</p>
        </div>
        <a href="{{ route('services.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('workshop.services.form')

</div>
@endsection
