@extends('layouts.app')

@section('title', 'Editar Plantilla')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-pencil-square"></i> Editar Plantilla</h1>
            <p class="text-muted mb-0">{{ $documentTemplate->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('document-templates.show', $documentTemplate) }}" class="btn btn-light border">
                <i class="bi bi-eye"></i> Vista previa
            </a>
            <a href="{{ route('document-templates.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    @include('document-templates.form')
</div>
@endsection
