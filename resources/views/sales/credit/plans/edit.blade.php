@extends('layouts.app')
@section('title', 'Editar Plan de Pago')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-list-check me-2 text-danger"></i>Editar plan de pago</h1>
            <p class="text-muted mb-0 small">Modifica las condiciones del plan <strong>{{ $plan->name }}</strong>.</p>
        </div>
        <a href="{{ route('payment-plans.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('sales.credit.plans.form')

</div>
@endsection
