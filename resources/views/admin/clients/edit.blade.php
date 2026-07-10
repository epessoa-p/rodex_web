@extends('layouts.app')

@section('title', 'Editar cliente: ' . $client->full_name)

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-pencil"></i> Editar cliente</h1>
            <p class="text-muted mb-0">{{ $client->full_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('clients.show', $client) }}" class="btn btn-light border">
                <i class="bi bi-eye"></i> Ver perfil
            </a>
            <a href="{{ route('clients.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    @include('admin.clients.form')
</div>
@endsection
