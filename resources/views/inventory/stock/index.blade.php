@extends('layouts.app')
@section('title', 'Inventario')

@section('page')
<div class="container-fluid">

    {{-- ── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5">
                <i class="bi bi-clipboard-data me-2 text-danger"></i>Inventario
            </h1>
            <p class="text-muted mb-0 small">
                Consulta y ajusta precios, costos y cantidades por almacén.
            </p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
        <div class="d-flex gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Exportar inventario">
                    <i class="bi bi-download me-1"></i>Exportar
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('inventory.stock.export.pdf', ['warehouse' => request('warehouse', 'all')]) }}"
                           target="_blank" rel="noopener">
                            <i class="bi bi-file-earmark-pdf text-danger"></i>Descargar PDF
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2"
                           href="{{ route('inventory.stock.export.excel', ['warehouse' => request('warehouse', 'all')]) }}">
                            <i class="bi bi-file-earmark-excel text-success"></i>Descargar Excel
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('inventory.stock.template') }}"
               class="btn btn-sm btn-light border"
               title="Descargar plantilla Excel">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>Descargar plantilla
            </a>
            <a href="{{ route('inventory.stock.import') }}"
               class="btn btn-sm btn-primary">
                <i class="bi bi-upload me-1"></i>Migrar inventario
            </a>
            <a href="{{ route('products.index') }}"
               class="btn btn-sm btn-light border">
                <i class="bi bi-box-seam me-1"></i>Productos
            </a>
        </div>
        @endif
    </div>

    {{-- ── WAREHOUSE TABS ──────────────────────────────────────────────── --}}
    @if($warehouses->count())
    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
        <a href="{{ route('inventory.stock', ['warehouse' => 'all']) }}"
           class="wh-pill btn btn-sm rounded-pill px-3 {{ $isAll ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap me-1"></i>Todos
        </a>
        @foreach($warehouses as $wh)
        <a href="{{ route('inventory.stock', ['warehouse' => $wh->id]) }}"
           class="wh-pill btn btn-sm rounded-pill px-3 {{ !$isAll && $whId == $wh->id ? 'active' : '' }}">
            <i class="bi bi-building me-1"></i>{{ $wh->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── FILTERS: categorías (scroll) + búsqueda ───────────────────── --}}
    <div class="d-flex align-items-center gap-2 mb-2 flex-nowrap">
        <button type="button" class="btn btn-light border btn-sm flex-shrink-0 px-2"
                id="sortToggle" title="Ordenar por nombre" aria-label="Ordenar">
            <i class="bi bi-sort-alpha-down" id="sortIcon"></i>
        </button>

        <div class="cat-filter-bar flex-grow-1 min-w-0">
            <button type="button" class="cat-pill active" data-cat="">Ver todos</button>
            <button type="button" class="cat-pill" data-cat="__nostock__">
                <i class="bi bi-exclamation-circle me-1 text-danger" style="font-size:.72rem;"></i>Sin stock
            </button>
            @foreach($categories as $cat)
            <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">
                {{ $cat->name }}@if($cat->code) ({{ $cat->code }})@endif
            </button>
            @endforeach
        </div>

        <div class="input-group input-group-sm flex-shrink-0" style="width:200px;">
            <span class="input-group-text bg-white border-end-0 pe-1">
                <i class="bi bi-search text-muted" style="font-size:.78rem;"></i>
            </span>
            <input type="text" id="stockSearch" class="form-control border-start-0 ps-1"
                   placeholder="Nombre, SKU, código…" autocomplete="off">
            <button type="button" class="btn btn-light border" id="clearStockSearch"
                    style="display:none;" title="Limpiar búsqueda">
                <i class="bi bi-x-lg" style="font-size:.72rem;"></i>
            </button>
        </div>
    </div>

    {{-- ── FILTERS: marcas (scroll) ───────────────────────────────────── --}}
    @if($brands->count())
    <div class="d-flex align-items-center gap-2 mb-3 flex-nowrap">
        <span class="text-muted flex-shrink-0" style="font-size:.72rem;"><i class="bi bi-award me-1"></i>Marca</span>
        <div class="cat-filter-bar flex-grow-1 min-w-0">
            <button type="button" class="cat-pill brand-pill active" data-brand="">Todas</button>
            @foreach($brands as $brand)
            <button type="button" class="cat-pill brand-pill" data-brand="{{ $brand->id }}">{{ $brand->name }}</button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── KPIs: valor de stock ───────────────────────────────────────── --}}
    <div class="row g-2 mb-3" id="stockKpis">
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.66rem;letter-spacing:.04em;">
                        <i class="bi bi-box-seam"></i>PRODUCTOS
                    </div>
                    <div class="fw-bold" style="font-size:1.05rem;" id="kpiProducts">{{ number_format($productCount) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.66rem;letter-spacing:.04em;">
                        <i class="bi bi-stack"></i>UNIDADES
                    </div>
                    <div class="fw-bold" style="font-size:1.05rem;" id="kpiUnits">{{ number_format($totalUnits) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.66rem;letter-spacing:.04em;">
                        <i class="bi bi-cash-stack"></i>VALOR A COSTO
                    </div>
                    <div class="fw-bold text-dark" style="font-size:1.05rem;">Bs. <span id="kpiCost">{{ number_format($valueCost, 2) }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 text-muted" style="font-size:.66rem;letter-spacing:.04em;">
                        <i class="bi bi-tag"></i>VALOR A VENTA
                    </div>
                    <div class="fw-bold text-success" style="font-size:1.05rem;">Bs. <span id="kpiPrice">{{ number_format($valuePrice, 2) }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg">
            <div class="card border-0 shadow-sm h-100" style="background:linear-gradient(135deg,#0a0a0a,#2a2a2a);">
                <div class="card-body py-2 px-3 text-white">
                    <div class="d-flex align-items-center gap-2" style="font-size:.66rem;letter-spacing:.04em;opacity:.8;">
                        <i class="bi bi-graph-up-arrow"></i>GANANCIA POTENCIAL
                    </div>
                    <div class="fw-bold" style="font-size:1.05rem;">Bs. <span id="kpiProfit">{{ number_format($potentialProfit, 2) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Warehouse mode indicator --}}
    @if(!$isAll)
    <div class="alert alert-info border-0 py-2 px-3 mb-3 d-flex align-items-center gap-2"
         style="font-size:.83rem;background:#eff6ff;">
        <i class="bi bi-info-circle-fill text-primary"></i>
        Editando cantidades en el almacén
        <strong>{{ $warehouses->firstWhere('id', $whId)?->name }}</strong>.
        Los precios y costos se actualizan globalmente.
    </div>
    @else
    <div class="alert border-0 py-2 px-3 mb-3 d-flex align-items-center gap-2"
         style="font-size:.83rem;background:#f9f9f9;color:#666;">
        <i class="bi bi-eye text-muted"></i>
        Vista consolidada. Selecciona un almacén para editar cantidades.
    </div>
    @endif

    {{-- ── MAIN TABLE ──────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem;" id="stockTable">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-3 py-2 fw-semibold text-muted text-uppercase"
                                style="letter-spacing:.04em;width:38px;font-size:.72rem;"></th>
                            <th class="py-2 fw-semibold text-muted text-uppercase"
                                style="letter-spacing:.04em;font-size:.72rem;">Producto</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase"
                                style="letter-spacing:.04em;font-size:.72rem;">Precio</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase"
                                style="letter-spacing:.04em;font-size:.72rem;">Costo</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-center"
                                style="letter-spacing:.04em;font-size:.72rem;min-width:130px;">
                                Cantidad disponible
                            </th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-center"
                                style="letter-spacing:.04em;font-size:.72rem;">Ganancia</th>
                            <th class="py-2 pe-3" style="width:28px;"></th>
                        </tr>
                    </thead>
                    <tbody id="stockBody">
                        @forelse($products as $product)
                        @php
                            $mainPhoto = $product->mainPhoto();
                            $displayStock = $isAll
                                ? (float) $product->current_stock
                                : (float) ($stockMap[$product->id] ?? 0);
                            $minStock     = (float) ($product->min_stock ?? 0);
                            $isLow        = $minStock > 0 && $displayStock <= $minStock;
                            $margin       = ($product->cost > 0)
                                ? round(($product->price - $product->cost) / $product->cost * 100, 1)
                                : 0;
                        @endphp
                        <tr class="border-bottom border-light stock-row"
                            data-category="{{ $product->category_id }}"
                            data-brand="{{ $product->brand_id }}"
                            data-name="{{ strtolower($product->name) }}"
                            data-sku="{{ strtolower($product->sku ?? '') }}"
                            data-code="{{ strtolower($product->code ?? '') }}"
                            data-stock="{{ $displayStock }}">

                            {{-- Thumbnail --}}
                            <td class="ps-3 py-2">
                                @if($mainPhoto)
                                <img src="{{ $mainPhoto->url }}"
                                     alt="{{ $product->name }}"
                                     class="rounded-2 border object-fit-cover"
                                     style="width:32px;height:32px;object-fit:cover;">
                                @else
                                <div class="rounded-2 bg-light border d-flex align-items-center
                                            justify-content-center fw-bold text-muted"
                                     style="width:32px;height:32px;font-size:.75rem;">
                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                </div>
                                @endif
                            </td>

                            {{-- Producto --}}
                            <td class="py-2">
                                <a href="{{ route('products.show', $product) }}"
                                   class="text-decoration-none text-dark fw-semibold d-block lh-sm">
                                    {{ $product->name }}
                                </a>
                                <span class="text-muted" style="font-size:.73rem;">
                                    {{ $product->code ?: $product->sku }}
                                </span>
                                @if($isLow)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1"
                                      style="font-size:.68rem;vertical-align:middle;">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Unidades bajas
                                </span>
                                @endif
                            </td>

                            {{-- Precio --}}
                            <td class="py-2">
                                <div class="input-group input-group-sm" style="max-width:130px;">
                                    <span class="input-group-text bg-light px-2 text-muted"
                                          style="font-size:.78rem;">Bs.</span>
                                    <input type="number"
                                           class="form-control form-control-sm fld-input"
                                           data-field="price"
                                           data-id="{{ $product->id }}"
                                           value="{{ number_format($product->price, 2, '.', '') }}"
                                           min="0" step="0.01"
                                           style="max-width:80px;">
                                </div>
                            </td>

                            {{-- Costo --}}
                            <td class="py-2">
                                <div class="input-group input-group-sm" style="max-width:130px;">
                                    <span class="input-group-text bg-light px-2 text-muted"
                                          style="font-size:.78rem;">Bs.</span>
                                    <input type="number"
                                           class="form-control form-control-sm fld-input"
                                           data-field="cost"
                                           data-id="{{ $product->id }}"
                                           value="{{ number_format($product->cost, 2, '.', '') }}"
                                           min="0" step="0.01"
                                           style="max-width:80px;">
                                </div>
                            </td>

                            {{-- Cantidad disponible --}}
                            <td class="py-2 text-center">
                                <input type="number"
                                       class="form-control form-control-sm qty-input mx-auto"
                                       data-id="{{ $product->id }}"
                                       value="{{ $displayStock }}"
                                       min="0" step="1"
                                       @if($isAll) disabled title="Selecciona un almacén para editar cantidades" @endif
                                       style="max-width:90px;text-align:center;">
                            </td>

                            {{-- Ganancia --}}
                            <td class="py-2 text-center">
                                <span class="badge margin-badge bg-success-subtle text-success border border-success-subtle"
                                      data-id="{{ $product->id }}"
                                      style="font-size:.78rem;min-width:44px;">
                                    {{ $margin }}%
                                </span>
                            </td>

                            {{-- Save state --}}
                            <td class="py-2 pe-3 text-center" style="width:28px;">
                                <span class="save-state" data-id="{{ $product->id }}"></span>
                            </td>
                        </tr>
                        @empty
                        <tr id="stockEmptyRow">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard-data fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay productos en el inventario.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('inventory.stock.import') }}"
                                   class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-upload me-1"></i>Migrar inventario
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- No results message (shown by JS) --}}
            <div id="stockNoResults" class="text-center py-5 text-muted" style="display:none;">
                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                <p class="mb-0">Sin resultados para esa búsqueda.</p>
            </div>
        </div>
    </div>

    {{-- Pagination (client-side: opera sobre el conjunto filtrado) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"
         id="stockPaginationWrap" style="display:none;">
        <div class="text-muted small" id="stockPageInfo"></div>
        <nav aria-label="Paginación de inventario">
            <ul class="pagination pagination-sm mb-0" id="stockPagination"></ul>
        </nav>
    </div>

</div>

{{-- Hidden: active warehouse id for JS --}}
<span id="activeWarehouseId" data-wh="{{ $whId ?? '' }}" style="display:none;"></span>

@push('styles')
<style>
/* ── Warehouse pills ─────────────────────────────────────── */
.wh-pill {
    background: #fff;
    border: 1px solid #e3e3e3;
    color: #555;
    font-weight: 500;
    transition: all .15s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.wh-pill:hover {
    border-color: var(--brand-black, #0a0a0a);
    color: var(--brand-black, #0a0a0a);
    text-decoration: none;
}
.wh-pill.active {
    background: var(--brand-black, #0a0a0a);
    border-color: var(--brand-black, #0a0a0a);
    color: #fff;
}

/* ── Category filter bar ─────────────────────────────────── */
.cat-filter-bar {
    display: flex;
    gap: .4rem;
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: .25rem;
    scrollbar-width: thin;
    min-width: 0;
}
.cat-filter-bar::-webkit-scrollbar { height: 4px; }
.cat-filter-bar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }

.cat-pill {
    display: inline-flex;
    align-items: center;
    padding: .28rem .8rem;
    border-radius: 50rem;
    font-size: .78rem;
    font-weight: 500;
    border: 1.5px solid #dee2e6;
    background: #fff;
    color: #495057;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.cat-pill:hover  { border-color: #0a0a0a; color: #0a0a0a; }
.cat-pill.active { background: #0a0a0a; border-color: #0a0a0a; color: #fff; }
.brand-pill { padding: .2rem .7rem; font-size: .74rem; }

/* ── Tabla compacta (small) ─────────────────────────────── */
#stockTable td, #stockTable th { padding-top: .35rem; padding-bottom: .35rem; }
#stockTable .form-control-sm { font-size: .78rem; padding-top: .15rem; padding-bottom: .15rem; }

/* ── Field inputs ────────────────────────────────────────── */
.fld-input {
    max-width: 80px;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.fld-input:focus { border-color: #0a0a0a; box-shadow: 0 0 0 .18rem rgba(10,10,10,.1); }
.fld-input.saving { border-color: #fd7e14; }
.fld-input.saved  { border-color: #198754; }
.fld-input.error  { border-color: #dc3545; }

.qty-input {
    max-width: 90px;
    text-align: center;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.qty-input:not(:disabled):focus {
    border-color: #0a0a0a;
    box-shadow: 0 0 0 .18rem rgba(10,10,10,.1);
}
.qty-input:disabled { background: #f8f9fa; cursor: not-allowed; opacity: .75; }

/* ── Save state indicator ───────────────────────────────── */
.save-state {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    font-size: .8rem;
    border-radius: 50%;
    transition: opacity .3s ease;
}
.save-state.ok    { color: #198754; }
.save-state.error { color: #dc3545; }
.save-state.spin  { color: #fd7e14; }

/* ── Responsive tweaks ──────────────────────────────────── */
@media (max-width: 767.98px) {
    .fld-input  { max-width: 70px; }
    .qty-input  { max-width: 72px; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    // ── Blade-generated route templates ──────────────────────────────
    const FIELD_URL    = '{{ route('inventory.stock.field', '__ID__') }}';
    const QTY_URL      = '{{ route('inventory.stock.quantity', '__ID__') }}';
    const CSRF         = '{{ csrf_token() }}';
    const activeWhEl   = document.getElementById('activeWarehouseId');
    const activeWhId   = activeWhEl ? activeWhEl.dataset.wh : '';

    // ── Generic JSON POST helper ──────────────────────────────────────
    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': CSRF,
            },
            body: JSON.stringify(body),
        });
    }

    // ── Save-state helpers ────────────────────────────────────────────
    function setStateIcon(id, state) {
        const el = document.querySelector('.save-state[data-id="' + id + '"]');
        if (!el) return;
        el.className = 'save-state ' + state;
        if (state === 'ok') {
            el.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            setTimeout(function () { el.innerHTML = ''; el.className = 'save-state'; }, 2000);
        } else if (state === 'error') {
            el.innerHTML = '<i class="bi bi-x-circle-fill"></i>';
            setTimeout(function () { el.innerHTML = ''; el.className = 'save-state'; }, 3000);
        } else if (state === 'spin') {
            el.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:.8rem;height:.8rem;border-width:2px;"></span>';
        }
    }

    function flashInput(input, cls) {
        input.classList.add(cls);
        setTimeout(function () { input.classList.remove(cls); }, 1800);
    }

    // ── Margin badge update ───────────────────────────────────────────
    function updateMargin(id, price, cost) {
        const badge = document.querySelector('.margin-badge[data-id="' + id + '"]');
        if (!badge) return;
        const margin = cost > 0 ? Math.round((price - cost) / cost * 100 * 10) / 10 : 0;
        badge.textContent = margin + '%';
        // Colour: negative → danger, low (<20%) → warning, else success
        badge.className = 'badge margin-badge border';
        if (margin < 0) {
            badge.classList.add('bg-danger-subtle', 'text-danger', 'border-danger-subtle');
        } else if (margin < 20) {
            badge.classList.add('bg-warning-subtle', 'text-warning', 'border-warning-subtle');
        } else {
            badge.classList.add('bg-success-subtle', 'text-success', 'border-success-subtle');
        }
    }

    // ── Recalcular KPIs de valor de stock en vivo ────────────────────
    function fmtMoney(n) {
        return Number(n).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function recalcStockKpis() {
        let products = 0, units = 0, cost = 0, price = 0;
        document.querySelectorAll('#stockBody .stock-row').forEach(function (row) {
            products++;
            const qty = parseInt(row.querySelector('.qty-input')?.value, 10) || 0;
            const c   = parseFloat(row.querySelector('.fld-input[data-field="cost"]')?.value) || 0;
            const p   = parseFloat(row.querySelector('.fld-input[data-field="price"]')?.value) || 0;
            units += qty; cost += qty * c; price += qty * p;
        });
        const set = function (id, val) { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('kpiProducts', products.toLocaleString('es'));
        set('kpiUnits', units.toLocaleString('es'));
        set('kpiCost', fmtMoney(cost));
        set('kpiPrice', fmtMoney(price));
        set('kpiProfit', fmtMoney(price - cost));
    }

    // ── Get sibling field value (price or cost) from same row ─────────
    function getSiblingValue(id, field) {
        const sibling = document.querySelector('.fld-input[data-field="' + field + '"][data-id="' + id + '"]');
        return sibling ? parseFloat(sibling.value) || 0 : 0;
    }

    // ── Inline save: price / cost ─────────────────────────────────────
    document.querySelectorAll('.fld-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const id    = this.dataset.id;
            const field = this.dataset.field;
            const value = parseFloat(this.value);
            if (isNaN(value) || value < 0) return;

            setStateIcon(id, 'spin');
            flashInput(this, 'saving');

            postJson(FIELD_URL.replace('__ID__', id), { field: field, value: value })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) throw new Error(res.d.message || 'Error');
                    flashInput(input, 'saved');
                    setStateIcon(id, 'ok');

                    // Refresh margin badge
                    const price = field === 'price' ? value : getSiblingValue(id, 'price');
                    const cost  = field === 'cost'  ? value : getSiblingValue(id, 'cost');
                    if (res.d.margin !== undefined) {
                        const badge = document.querySelector('.margin-badge[data-id="' + id + '"]');
                        if (badge) {
                            updateMargin(id, price, cost);
                        }
                    } else {
                        updateMargin(id, price, cost);
                    }
                    recalcStockKpis();
                })
                .catch(function () {
                    flashInput(input, 'error');
                    setStateIcon(id, 'error');
                });
        });
    });

    // ── Inline save: quantity ─────────────────────────────────────────
    document.querySelectorAll('.qty-input:not(:disabled)').forEach(function (input) {
        const originalValue = input.value;

        input.addEventListener('change', function () {
            if (!activeWhId) return; // safety: should be disabled already on "Todos"
            const id  = this.dataset.id;
            const qty = parseInt(this.value, 10);
            if (isNaN(qty) || qty < 0) { this.value = originalValue; return; }

            setStateIcon(id, 'spin');

            const row = this.closest('tr');
            postJson(QTY_URL.replace('__ID__', id), {
                warehouse_id: activeWhId,
                quantity:     qty,
            })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                .then(function (res) {
                    if (!res.ok) throw new Error(res.d.message || 'Error');
                    const newStock = res.d.warehouse_stock !== undefined
                        ? parseFloat(res.d.warehouse_stock)
                        : qty;
                    input.value = newStock;
                    if (row) row.dataset.stock = newStock;
                    flashInput(input, 'saved');
                    setStateIcon(id, 'ok');
                    recalcStockKpis();
                })
                .catch(function () {
                    input.value = originalValue;
                    flashInput(input, 'error');
                    setStateIcon(id, 'error');
                });
        });
    });

    // ── Category + brand + search filters + paginación client-side ────
    let activeCat   = '';
    let activeBrand = '';
    let searchQ     = '';
    let sortAsc     = true;

    const PAGE_SIZE     = 25;          // productos por página
    let currentPage     = 1;
    let filteredRows    = [];          // filas que pasan los filtros (en orden del DOM)

    // Determina qué filas pasan los filtros (sin tocar la visibilidad todavía).
    function computeFiltered() {
        const rows = document.querySelectorAll('#stockBody .stock-row');
        const q    = searchQ.toLowerCase().trim();
        filteredRows = [];

        rows.forEach(function (row) {
            const cat   = row.dataset.category || '';
            const brand = row.dataset.brand || '';
            const stock = parseFloat(row.dataset.stock) || 0;
            const name  = row.dataset.name  || '';
            const sku   = row.dataset.sku   || '';
            const code  = row.dataset.code  || '';

            // Category filter
            let catMatch = true;
            if (activeCat === '__nostock__') {
                catMatch = stock <= 0;
            } else if (activeCat !== '') {
                catMatch = cat == activeCat;
            }

            // Brand filter
            let brandMatch = activeBrand === '' || brand == activeBrand;

            // Search filter
            let searchMatch = true;
            if (q) {
                searchMatch = name.includes(q) || sku.includes(q) || code.includes(q);
            }

            if (catMatch && brandMatch && searchMatch) filteredRows.push(row);
        });
    }

    // Muestra solo las filas de la página actual del conjunto ya filtrado.
    function renderPage() {
        const allRows = document.querySelectorAll('#stockBody .stock-row');
        const noRes   = document.getElementById('stockNoResults');
        const total   = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const start = (currentPage - 1) * PAGE_SIZE;
        const end   = start + PAGE_SIZE;

        // Ocultar todo, luego mostrar solo la porción de la página.
        allRows.forEach(function (row) { row.style.display = 'none'; });
        for (let i = start; i < end && i < total; i++) {
            filteredRows[i].style.display = '';
        }

        if (noRes) noRes.style.display = (total === 0 && allRows.length > 0) ? '' : 'none';

        renderPagination(total, totalPages, start, Math.min(end, total));
    }

    // Construye la barra de paginación (Bootstrap) + el texto informativo.
    function renderPagination(total, totalPages, start, shownEnd) {
        const wrap = document.getElementById('stockPaginationWrap');
        const ul   = document.getElementById('stockPagination');
        const info = document.getElementById('stockPageInfo');
        if (!wrap || !ul) return;

        // Si todo cabe en una sola página, ocultar la barra.
        if (total <= PAGE_SIZE) {
            wrap.style.display = 'none';
            ul.innerHTML = '';
            if (info) info.textContent = '';
            return;
        }
        wrap.style.display = '';

        if (info) {
            info.textContent = 'Mostrando ' + (start + 1) + '–' + shownEnd + ' de ' +
                total.toLocaleString('es') + ' productos';
        }

        // Ventana de páginas alrededor de la actual.
        const pages = [];
        const win = 2;
        let from = Math.max(1, currentPage - win);
        let to   = Math.min(totalPages, currentPage + win);
        if (from > 1)          { pages.push(1); if (from > 2) pages.push('…'); }
        for (let p = from; p <= to; p++) pages.push(p);
        if (to < totalPages)   { if (to < totalPages - 1) pages.push('…'); pages.push(totalPages); }

        let html = '';
        // Prev
        html += '<li class="page-item ' + (currentPage === 1 ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '" aria-label="Anterior">&laquo;</a></li>';
        // Numbers
        pages.forEach(function (p) {
            if (p === '…') {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            } else {
                html += '<li class="page-item ' + (p === currentPage ? 'active' : '') + '">' +
                        '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
            }
        });
        // Next
        html += '<li class="page-item ' + (currentPage === totalPages ? 'disabled' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '" aria-label="Siguiente">&raquo;</a></li>';

        ul.innerHTML = html;

        ul.querySelectorAll('a.page-link[data-page]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.dataset.page, 10);
                if (isNaN(p) || p < 1 || p > totalPages || p === currentPage) return;
                currentPage = p;
                renderPage();
                // Llevar la vista al inicio de la tabla.
                const tbl = document.getElementById('stockTable');
                if (tbl) tbl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    // Punto de entrada: recalcula filtros, vuelve a la página 1 y pinta.
    function applyFilters() {
        computeFiltered();
        currentPage = 1;
        renderPage();
    }

    // Pintado inicial.
    applyFilters();

    // Category pills (tienen data-cat)
    document.querySelectorAll('.cat-pill[data-cat]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cat-pill[data-cat]').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            activeCat = this.dataset.cat;
            applyFilters();
        });
    });

    // Brand pills (tienen data-brand)
    document.querySelectorAll('.cat-pill[data-brand]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cat-pill[data-brand]').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            activeBrand = this.dataset.brand;
            applyFilters();
        });
    });

    // Search
    const searchInput = document.getElementById('stockSearch');
    const clearBtn    = document.getElementById('clearStockSearch');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            searchQ = this.value;
            if (clearBtn) clearBtn.style.display = this.value ? '' : 'none';
            applyFilters();
        });
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchQ = '';
            this.style.display = 'none';
            applyFilters();
        });
    }

    // Sort toggle (by product name)
    const sortToggle = document.getElementById('sortToggle');
    const sortIcon   = document.getElementById('sortIcon');
    if (sortToggle) {
        sortToggle.addEventListener('click', function () {
            sortAsc = !sortAsc;
            sortIcon.className = sortAsc ? 'bi bi-sort-alpha-down' : 'bi bi-sort-alpha-up';

            const tbody = document.getElementById('stockBody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('.stock-row'));
            rows.sort(function (a, b) {
                const na = a.dataset.name || '';
                const nb = b.dataset.name || '';
                return sortAsc ? na.localeCompare(nb) : nb.localeCompare(na);
            });
            rows.forEach(function (row) { tbody.appendChild(row); });

            // Reordenado el DOM, recalcular el conjunto filtrado y volver a paginar.
            applyFilters();
        });
    }

})();
</script>
@endpush

@endsection
