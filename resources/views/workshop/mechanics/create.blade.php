@extends('layouts.app')
@section('title', 'Nuevo mecánico')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-person-gear me-2 text-danger"></i>Nuevo mecánico</h1>
            <p class="text-muted mb-0 small">Registra un nuevo técnico en el taller.</p>
        </div>
        <a href="{{ route('mechanics.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('workshop.mechanics.form')

</div>
@endsection
