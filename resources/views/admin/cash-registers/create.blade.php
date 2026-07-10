@extends('layouts.app')

@section('title', 'Nueva caja')

@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-plus-circle"></i> Nueva caja</h1>
            <p class="text-muted mb-0">Registra una nueva caja registradora en una sucursal.</p>
        </div>
        <a href="{{ route('cash-registers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @include('admin.cash-registers.form')

</div>
@endsection
