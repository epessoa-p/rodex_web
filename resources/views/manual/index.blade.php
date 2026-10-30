@extends('layouts.app')

@section('title', 'Manual de usuario - ' . config('brand.name'))

@section('page')
<div class="container-fluid manual-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-1"><i class="bi bi-book"></i> Manual de usuario</h1>
            <p class="text-muted mb-0">Guías paso a paso de los módulos que tienes disponibles.</p>
        </div>
        @if(count($chapters))
            <a href="{{ route('manual.pdf') }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf"></i> Descargar manual completo
            </a>
        @endif
    </div>

    <div class="mb-3" style="max-width:420px;">
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" id="manualSearch" class="form-control" placeholder="Buscar en el manual (ej. cerrar caja)">
        </div>
    </div>

    <div class="row g-3" id="manualCards">
        @forelse($chapters as $chapter)
            @php
                // Texto plano del capítulo para el buscador (títulos + contenido).
                $haystack = Str::lower($chapter['title'] . ' ' . $chapter['summary'] . ' '
                    . strip_tags(\App\Support\UserManual::render($chapter, auth()->user()->getCurrentCompany())['html']));
            @endphp
            <div class="col-12 col-md-6 col-xl-4 manual-card" data-search="{{ $haystack }}">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="manual-icon"><i class="bi {{ $chapter['icon'] }}"></i></span>
                            <h5 class="mb-0">{{ $chapter['title'] }}</h5>
                        </div>
                        <p class="text-muted small flex-grow-1 mb-3">{{ $chapter['summary'] }}</p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manual.show', $chapter['slug']) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-book-half"></i> Leer
                            </a>
                            <a href="{{ route('manual.pdf', $chapter['slug']) }}" class="btn btn-sm btn-light border" title="Descargar este capítulo en PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border">No hay capítulos disponibles para tus módulos.</div>
            </div>
        @endforelse
    </div>
    <div id="manualNoResults" class="alert alert-light border mt-3 d-none">Ningún capítulo coincide con esa búsqueda.</div>
</div>

<style>
    .manual-icon { width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
                   background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); font-size: 1.15rem; flex-shrink: 0; }
</style>

<script>
    (function () {
        const input = document.getElementById('manualSearch');
        const cards = document.querySelectorAll('.manual-card');
        const empty = document.getElementById('manualNoResults');
        input?.addEventListener('input', function () {
            const terms = this.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
            let shown = 0;
            cards.forEach(card => {
                const ok = terms.every(t => card.dataset.search.includes(t));
                card.classList.toggle('d-none', !ok);
                if (ok) shown++;
            });
            empty.classList.toggle('d-none', shown > 0);
        });
    })();
</script>
@endsection
