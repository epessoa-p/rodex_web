{{-- Las dos formas de cargar productos. $active: 'table' | 'excel'. --}}
@php $active = $active ?? 'table'; @endphp
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <a href="{{ route('inventory.stock.import.table') }}"
           class="imp-mode {{ $active === 'table' ? 'is-active' : '' }}">
            <span class="imp-mode-icon" style="--c:#0d6efd;"><i class="bi bi-table"></i></span>
            <span class="flex-grow-1">
                <span class="d-flex align-items-center gap-2 fw-semibold">
                    Escribir en tabla
                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">Recomendado</span>
                </span>
                <span class="d-block small text-muted">Como una hoja de cálculo, aquí mismo. Sin Excel, sin descargar ni subir archivos.</span>
            </span>
            @if($active === 'table')<i class="bi bi-check-circle-fill text-primary fs-5"></i>@endif
        </a>
    </div>
    <div class="col-md-6">
        <a href="{{ route('inventory.stock.import') }}"
           class="imp-mode {{ $active === 'excel' ? 'is-active' : '' }}">
            <span class="imp-mode-icon" style="--c:#198754;"><i class="bi bi-file-earmark-excel"></i></span>
            <span class="flex-grow-1">
                <span class="d-block fw-semibold">Subir plantilla Excel</span>
                <span class="d-block small text-muted">Si ya tienes tus productos en un archivo .xlsx.</span>
            </span>
            @if($active === 'excel')<i class="bi bi-check-circle-fill text-success fs-5"></i>@endif
        </a>
    </div>
</div>

@once
@push('styles')
<style>
.imp-mode {
    display:flex; align-items:center; gap:.85rem; height:100%;
    padding:.9rem 1rem; border-radius:.9rem; background:#fff;
    border:1.5px solid #e9ecef; color:inherit; text-decoration:none;
    transition:border-color .15s, box-shadow .15s, transform .15s;
}
.imp-mode:hover { border-color:#c7cdd4; box-shadow:0 4px 16px rgba(0,0,0,.06); transform:translateY(-1px); color:inherit; }
.imp-mode.is-active { border-color:var(--bs-primary,#0d6efd); box-shadow:0 0 0 .2rem rgba(13,110,253,.08); }
.imp-mode-icon {
    width:44px; height:44px; border-radius:.75rem; flex-shrink:0;
    display:inline-flex; align-items:center; justify-content:center; font-size:1.3rem;
    color:var(--c); background:color-mix(in srgb, var(--c) 12%, white);
}
</style>
@endpush
@endonce
