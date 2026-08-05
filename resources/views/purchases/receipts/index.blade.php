@extends('layouts.app')
@section('title', 'Recepciones de mercadería')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-in-down me-2 text-danger"></i>Recepciones de mercadería</h1>
            <p class="text-muted mb-0 small">Historial de ingresos de mercadería al inventario.</p>
        </div>
    </div>

    {{-- Tabs de almacenes (multi-selección) --}}
    @if($warehouses->count())
    <form method="GET" id="whFilterForm" class="mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Tab: Todos --}}
            <button type="button"
                    class="wh-pill btn btn-sm rounded-pill px-3 {{ empty($selected) ? 'active' : '' }}"
                    data-all="1">
                <i class="bi bi-grid-3x3-gap me-1"></i>Todos
                <span class="badge rounded-pill ms-1 wh-count">{{ $totalCount }}</span>
            </button>

            {{-- Tab por almacén --}}
            @foreach($warehouses as $wh)
            <label class="wh-pill btn btn-sm rounded-pill px-3 mb-0 {{ in_array($wh->id, $selected) ? 'active' : '' }}">
                <input type="checkbox" name="warehouses[]" value="{{ $wh->id }}"
                       class="d-none wh-checkbox" {{ in_array($wh->id, $selected) ? 'checked' : '' }}>
                <i class="bi bi-building me-1"></i>{{ $wh->name }}
                <span class="badge rounded-pill ms-1 wh-count">{{ $counts[$wh->id] ?? 0 }}</span>
            </label>
            @endforeach

            @if(!empty($selected))
            <a href="{{ route('goods-receipts.index') }}" class="btn btn-sm btn-link text-muted text-decoration-none">
                <i class="bi bi-x-lg"></i> Limpiar
            </a>
            @endif
        </div>
    </form>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Código</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Orden de compra</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Almacén</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Fecha</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Recibido por</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receipts as $receipt)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <a href="{{ route('goods-receipts.show', $receipt) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $receipt->code }}
                                </a>
                            </td>
                            <td class="py-3 small">
                                <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}"
                                   class="text-decoration-none text-muted">
                                    {{ $receipt->purchaseOrder->code }}
                                </a>
                            </td>
                            <td class="py-3 small">{{ $receipt->purchaseOrder->supplier->name }}</td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border fw-normal">
                                    {{ $receipt->warehouse->name }}
                                </span>
                            </td>
                            <td class="py-3 small text-muted">{{ $receipt->receipt_date->format('d/m/Y') }}</td>
                            <td class="py-3 small text-muted">{{ $receipt->receivedBy->name }}</td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('goods-receipts.show', $receipt) }}" class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay recepciones registradas.</p>
                                <p class="small">Las recepciones se crean desde una orden de compra.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $receipts->links() }}</div>

</div>

@push('styles')
<style>
    .wh-pill {
        background: #fff;
        border: 1px solid #e3e3e3;
        color: #555;
        font-weight: 500;
        transition: all .15s ease;
        cursor: pointer;
    }
    .wh-pill:hover {
        border-color: var(--brand-black, #22242e);
        color: var(--brand-black, #22242e);
    }
    .wh-pill.active {
        background: var(--brand-black, #22242e);
        border-color: var(--brand-black, #22242e);
        color: #fff;
    }
    .wh-pill .wh-count {
        background: #f0f0f0;
        color: #777;
        font-size: .68rem;
        font-weight: 600;
    }
    .wh-pill.active .wh-count {
        background: rgba(255,255,255,.22);
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('whFilterForm');
    if (!form) return;

    // Pills de almacén (toggle individual)
    form.querySelectorAll('.wh-checkbox').forEach(cb => {
        cb.closest('.wh-pill').addEventListener('click', function (e) {
            e.preventDefault();
            cb.checked = !cb.checked;
            form.submit();
        });
    });

    // Tab "Todos" → limpia selección
    const allBtn = form.querySelector('[data-all="1"]');
    if (allBtn) {
        allBtn.addEventListener('click', function () {
            form.querySelectorAll('.wh-checkbox').forEach(cb => cb.checked = false);
            form.submit();
        });
    }
})();
</script>
@endpush
@endsection
