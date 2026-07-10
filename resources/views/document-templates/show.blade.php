@extends('layouts.app')

@section('title', 'Plantilla: ' . $documentTemplate->name)

@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="mb-0"><i class="bi bi-file-earmark-text"></i> {{ $documentTemplate->name }}</h1>
                <span class="badge bg-{{ $documentTemplate->type_badge }}">{{ $documentTemplate->type_label }}</span>
                @if($documentTemplate->active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                @endif
                @if(!$documentTemplate->company_id)
                    <span class="badge bg-dark">Global</span>
                @endif
            </div>
            @if($documentTemplate->description)
                <p class="text-muted mb-0">{{ $documentTemplate->description }}</p>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('document-templates.download.word', $documentTemplate) }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-word"></i> Word
            </a>
            <a href="{{ route('document-templates.export.pdf', $documentTemplate) }}" class="btn btn-outline-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
            <a href="{{ route('document-templates.edit', $documentTemplate) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <form action="{{ route('document-templates.destroy', $documentTemplate) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar la plantilla «{{ addslashes($documentTemplate->name) }}»?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
            </form>
            <a href="{{ route('document-templates.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- Left: Template content --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-file-ruled"></i> Contenido de la plantilla</h6>
                    <small class="text-muted">Las variables aparecen resaltadas</small>
                </div>
                <div class="card-body">
                    <div class="border rounded p-4 bg-light template-raw" style="min-height:200px; white-space: pre-wrap; font-family: inherit;">
                        @if($documentTemplate->content)
                            {!! preg_replace(
                                '/\{\{([a-z_]+)\}\}/',
                                '<mark class="bg-warning bg-opacity-50 rounded px-1"><code style="font-size:.85em;">{{$1}}</code></mark>',
                                e($documentTemplate->content)
                            ) !!}
                        @else
                            <span class="text-muted">Sin contenido.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Variables reference --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-braces"></i> Variables disponibles</h6>
                </div>
                <div class="card-body p-2">
                    @php
                        $placeholders = \App\Models\DocumentTemplate::PLACEHOLDERS;
                    @endphp
                    @foreach($placeholders as $key => $label)
                        @php($token = sprintf('{{%s}}', $key))
                        <div class="d-flex align-items-start gap-2 px-1 py-1 rounded copy-row"
                             style="cursor: pointer;"
                             onclick="copyVar(this, '{{ $token }}')"
                             title="Clic para copiar">
                            <code class="text-primary flex-shrink-0" style="font-size: .73rem;">{{ $token }}</code>
                            <span class="text-muted" style="font-size: .72rem;">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyVar(el, text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                el.classList.add('bg-success-subtle');
                setTimeout(() => el.classList.remove('bg-success-subtle'), 800);
            });
        }
    }
</script>
@endpush
@endsection
