{{-- Paginador del catálogo público: solo ‹ 1 2 3 › centrado (el contador va aparte). --}}
@if ($paginator->hasPages())
<nav aria-label="Paginación">
    <ul class="pagination mb-0">
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&lsaquo;</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&lsaquo;</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">&rsaquo;</a></li>
        @else
            <li class="page-item disabled"><span class="page-link" aria-hidden="true">&rsaquo;</span></li>
        @endif
    </ul>
</nav>
@endif
