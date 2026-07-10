@extends('layouts.app')

@section('title', 'Nuevo cliente')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-person-plus"></i> Nuevo cliente</h1>
            <p class="text-muted mb-0">Registra un nuevo cliente en el sistema.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @include('admin.clients.form')
</div>
@endsection
