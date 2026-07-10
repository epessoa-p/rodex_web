@extends('layouts.app')

@section('title', 'Editar caja: ' . $cashRegister->name)

@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-pencil"></i> Editar caja</h1>
            <p class="text-muted mb-0">{{ $cashRegister->name }} — {{ $cashRegister->branch?->name }}</p>
        </div>
        <a href="{{ route('cash-registers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @include('admin.cash-registers.form')

</div>
@endsection
