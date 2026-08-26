@extends('layouts.app')
@section('title', 'Productos')
@section('page')
@php
    $canEditProducts = auth()->user()->is_super_admin
        || auth()->user()->hasPermissionInCompany('products.edit', auth()->user()->getCurrentCompany());
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-seam me-2 text-danger"></i>Productos</h1>
            <p class="text-muted mb-0 small">Catálogo completo de repuestos y accesorios.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
        <div class="d-flex gap-2">
            <a href="{{ route('products.import') }}" class="btn btn-light border">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Importar Excel
            </a>
            <x-plan-limit-button :status="$limitStatus" :href="route('products.create')"
                label="Nuevo producto" resource="productos" />
        </div>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;width:40px;font-size:.72rem;"></th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Producto</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Categoría</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Marca</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Origen</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Stock</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Precio</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php $mainPhoto = $product->mainPhoto(); @endphp
                        <tr class="border-bottom border-light">
                            <td class="ps-3 py-2">
                                @if($mainPhoto)
                                <img src="{{ $mainPhoto->url }}" alt="{{ $product->name }}"
                                     class="rounded-2 border object-fit-cover"
                                     style="width:30px;height:30px;object-fit:cover;">
                                @else
                                <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center fw-bold text-muted"
                                     style="width:30px;height:30px;font-size:.75rem;">
                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                </div>
                                @endif
                            </td>
                            <td class="py-2">
                                <a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark fw-semibold d-block lh-sm">
                                    {{ $product->name }}
                                </a>
                                <span class="text-muted" style="font-size:.75rem;">{{ $product->sku }}</span>
                            </td>
                            <td class="py-2">
                                @if($product->category)
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $product->category->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if($product->brand)
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $product->brand->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if($canEditProducts)
                                <div class="d-flex align-items-center gap-1">
                                    <select class="form-select form-select-sm origin-inline" data-no-search
                                            data-id="{{ $product->id }}" style="min-width:118px;max-width:150px;font-size:.76rem;padding:.15rem 1.4rem .15rem .5rem;">
                                        <option value="">— Sin origen —</option>
                                        @foreach($origins as $o)
                                        <option value="{{ $o->id }}" {{ (string) $product->origin_id === (string) $o->id ? 'selected' : '' }}>{{ $o->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi origin-state" data-id="{{ $product->id }}" style="font-size:.85rem;"></i>
                                </div>
                                @else
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $product->origin?->name ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-center">
                                @php
                                    $stock    = (float) $product->current_stock;
                                    $minStock = (float) ($product->min_stock ?? 0);
                                    $low      = $minStock > 0 && $stock <= $minStock;
                                @endphp
                                <span class="badge {{ $low ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}" style="font-size:.75rem;">
                                    {{ number_format($stock, 0) }}
                                </span>
                                @if($low)
                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:.7rem;" title="Stock bajo (mín: {{ $product->min_stock }})"></i>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold">
                                {{ money($product->price, null, 2) }}
                            </td>
                            <td class="py-2">
                                @if($product->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-3">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ addslashes($product->name) }}»?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay productos registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer producto
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
</div>

@if($canEditProducts)
@push('scripts')
<script>
// Guardado inline del ORIGEN del producto (AJAX).
(function () {
    const CSRF = '{{ csrf_token() }}';
    const urlFor = (id) => '{{ url('inventory/stock') }}/' + id + '/field';

    function setIcon(id, cls, color) {
        const el = document.querySelector('.origin-state[data-id="' + id + '"]');
        if (!el) return;
        el.className = 'bi origin-state ' + cls;
        el.style.color = color;
        if (cls === 'bi-check-circle-fill') {
            setTimeout(() => { el.className = 'bi origin-state'; el.style.color = ''; }, 1500);
        }
    }

    document.querySelectorAll('.origin-inline').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const id = this.dataset.id;
            setIcon(id, 'bi-arrow-repeat', '#fd7e14');
            fetch(urlFor(id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ field: 'origin_id', value: this.value || null }),
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => setIcon(id, (ok && d.ok) ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill', (ok && d.ok) ? '#198754' : '#dc3545'))
            .catch(() => setIcon(id, 'bi-exclamation-circle-fill', '#dc3545'));
        });
    });
})();
</script>
@endpush
@endif
@endsection
