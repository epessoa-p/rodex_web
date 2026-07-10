@extends('layouts.app')
@section('title', 'Nueva Solicitud de Crédito')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Nueva solicitud de crédito</h1>
            <p class="text-muted mb-0 small">Registra una solicitud de crédito para evaluación.</p>
        </div>
        <a href="{{ route('credit-applications.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('sales.credit.applications.form')

</div>
@endsection
