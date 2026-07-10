@extends('layouts.app')

@section('title', 'Nueva Plantilla')

@section('page')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-file-earmark-plus"></i> Nueva Plantilla</h1>
            <p class="text-muted mb-0">Crea una plantilla para contratos, boletas, recibos, etc.</p>
        </div>
        <a href="{{ route('document-templates.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @include('document-templates.form')
</div>
@endsection
