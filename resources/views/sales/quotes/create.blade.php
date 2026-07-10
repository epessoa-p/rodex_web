@extends('layouts.app')
@section('title', 'Nueva cotización')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-plus me-2 text-dark"></i>Nueva cotización</h1>
            <p class="text-muted mb-0 small">Crea un presupuesto para un cliente.</p>
        </div>
        <a href="{{ route('quotes.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('sales.quotes.form')

</div>
@endsection
