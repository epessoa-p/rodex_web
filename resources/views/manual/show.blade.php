@extends('layouts.app')

@section('title', $chapter['title'] . ' - Manual - ' . config('brand.name'))

@section('page')
<div class="container-fluid manual-page">
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('manual.index') }}" class="text-decoration-none">Manual</a></li>
            <li class="breadcrumb-item active">{{ $chapter['title'] }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="mb-0"><i class="bi {{ $chapter['icon'] }}"></i> {{ $chapter['title'] }}</h1>
        <a href="{{ route('manual.pdf', $chapter['slug']) }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
        </a>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-3 order-lg-2">
            <div class="manual-toc card border-0 shadow-sm">
                <div class="card-body">
                    @if(count($content['toc']))
                        <div class="text-uppercase text-muted small fw-semibold mb-2">En este capítulo</div>
                        <ul class="list-unstyled mb-3">
                            @foreach($content['toc'] as $item)
                                <li><a href="#{{ $item['id'] }}" class="manual-toc-link">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="text-uppercase text-muted small fw-semibold mb-2">Capítulos</div>
                    <ul class="list-unstyled mb-0">
                        @foreach($chapters as $c)
                            <li>
                                <a href="{{ route('manual.show', $c['slug']) }}"
                                   class="manual-toc-link {{ $c['slug'] === $chapter['slug'] ? 'active' : '' }}">
                                    <i class="bi {{ $c['icon'] }} me-1"></i>{{ $c['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9 order-lg-1">
            <div class="card border-0 shadow-sm">
                <div class="card-body manual-content">
                    {!! $content['html'] !!}
                </div>
            </div>

            <div class="d-flex justify-content-between gap-2 mt-3">
                @if($prev)
                    <a href="{{ route('manual.show', $prev['slug']) }}" class="btn btn-light border text-truncate">
                        <i class="bi bi-arrow-left"></i> {{ $prev['title'] }}
                    </a>
                @else
                    <span></span>
                @endif
                @if($next)
                    <a href="{{ route('manual.show', $next['slug']) }}" class="btn btn-light border text-truncate">
                        {{ $next['title'] }} <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .manual-toc { position: sticky; top: 1rem; }
    .manual-toc-link { display: block; padding: .3rem .5rem; border-radius: 6px; color: var(--bs-body-color);
                       text-decoration: none; font-size: .9rem; }
    .manual-toc-link:hover { background: var(--bs-light); }
    .manual-toc-link.active { background: rgba(var(--bs-primary-rgb), .1); color: var(--bs-primary); font-weight: 600; }

    .manual-content { font-size: 1rem; line-height: 1.65; max-width: 860px; overflow-wrap: anywhere; }
    .manual-content h2 { font-size: 1.3rem; margin-top: 2rem; padding-top: .5rem; border-top: 1px solid var(--bs-border-color); scroll-margin-top: 1rem; }
    .manual-content h2:first-child { margin-top: 0; border-top: 0; padding-top: 0; }
    .manual-content h3 { font-size: 1.08rem; margin-top: 1.4rem; }
    .manual-content ol, .manual-content ul { padding-left: 1.4rem; }
    .manual-content li { margin-bottom: .3rem; }
    .manual-content blockquote { border-left: 4px solid var(--bs-primary); background: rgba(var(--bs-primary-rgb), .05);
                                 padding: .6rem 1rem; margin: 1rem 0; border-radius: 0 8px 8px 0; }
    .manual-content blockquote p:last-child { margin-bottom: 0; }
    .manual-content code { background: var(--bs-light); color: inherit; padding: .1rem .35rem; border-radius: 4px; }
    .manual-content table { width: 100%; margin: 1rem 0; border-collapse: collapse; display: block; overflow-x: auto; }
    .manual-content th, .manual-content td { border: 1px solid var(--bs-border-color); padding: .45rem .6rem; vertical-align: top; }
    .manual-content th { background: var(--bs-light); }
    .manual-content img { max-width: 100%; height: auto; border: 1px solid var(--bs-border-color); border-radius: 8px; }
</style>
@endsection
