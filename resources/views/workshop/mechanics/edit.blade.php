@extends('layouts.app')
@section('title', 'Editar mecánico: ' . $mechanic->name)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-person-gear me-2 text-danger"></i>Editar mecánico</h1>
            <p class="text-muted mb-0 small">{{ $mechanic->name }}</p>
        </div>
        <a href="{{ route('mechanics.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @include('workshop.mechanics.form')

</div>
@endsection
