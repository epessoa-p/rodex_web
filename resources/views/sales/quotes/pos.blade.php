@extends('layouts.app')
@section('title', 'Punto de Cotización')
@section('page')

@php
    $productsData = $products->map(function($p) {
        $photos = [];
        foreach ($p->photos as $ph) {
            $photos[] = $ph->url;
        }
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'price'            => (float) $p->price,
            'stock'            => (float) $p->current_stock,
            'category'         => $p->category?->name ?? '',
            'category_id'      => $p->category_id,
            'brand'            => $p->brand?->name ?? '',
            'description'      => $p->description ?? '',
            'compatible_models'=> $p->motoModels->pluck('display_name')->implode(', '),
            'photo'            => count($photos) > 0 ? $photos[0] : null,
            'photos'           => $photos,
        ];
    })->values();

    $clientsJson = $clients->map(function($c) {
        return [
            'id'        => $c->id,
            'name'      => $c->full_name,
            'id_number' => $c->id_number ?? '',
        ];
    })->values();
@endphp

<div class="pos-wrapper">

    {{-- ── TOP BAR ────────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-shrink-0 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h1 class="mb-0 fw-bold fs-5"><i class="bi bi-calculator me-2 text-danger"></i>Punto de Cotización</h1>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('quotes.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-list-ul me-1"></i>Cotizaciones
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show flex-shrink-0" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-3">

        {{-- ── LEFT: PRODUCT GRID ──────────────────────────────────────── --}}
        <div class="col-lg-8">
            {{-- Search --}}
            <div class="mb-2">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="productSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar producto por nombre, SKU o categoría..."
                           autocomplete="off">
                    <button type="button" class="btn btn-light border" id="clearSearch" style="display:none;" onclick="clearSearch()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            {{-- Category filter bar --}}
            <div class="cat-filter-bar mb-3">
                <button type="button" class="cat-pill active" data-cat="">Todas</button>
                @foreach($categories as $cat)
                <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                @endforeach
            </div>
            {{-- Grid --}}
            <div id="productGrid" class="row g-2 align-content-start pb-2">
                {{-- Filled by JS --}}
            </div>
            <div id="noProductsMsg" class="text-center py-5 text-muted" style="display:none;">
                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                Sin resultados para esa búsqueda.
            </div>
        </div>

        {{-- ── RIGHT: CART ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <form action="{{ route('quotes.store') }}" method="POST" id="quoteForm"
                  style="position:sticky;top:1rem;">
                @csrf
                <input type="hidden" name="quote_date" value="{{ now()->format('Y-m-d') }}">
                <input type="hidden" name="client_id" id="client_id" value="">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2 text-muted"></i>Cotización</h6>
                            <span class="badge bg-light border text-muted" id="cartCount">0 items</span>
                        </div>
                    </div>

                    {{-- Cart rows --}}
                    <div class="overflow-auto" style="max-height:38vh;">
                        <div id="cartEmpty" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2 d-block fs-2 mb-2 opacity-25"></i>
                            <small>Haz clic en un producto para agregarlo</small>
                        </div>
                        <table class="table table-sm align-middle mb-0" id="cartTable" style="display:none;font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2" style="font-size:.7rem;">Producto</th>
                                    <th class="py-2 text-center" style="width:80px;font-size:.7rem;">Cant.</th>
                                    <th class="py-2 text-end" style="width:70px;font-size:.7rem;">P.Unit</th>
                                    <th class="py-2 text-end" style="width:75px;font-size:.7rem;">Sub.</th>
                                    <th class="py-2 pe-2" style="width:32px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                        </table>
                    </div>

                    {{-- Footer --}}
                    <div class="border-top">
                        {{-- Client selector --}}
                        <div class="p-3 border-bottom">
                            <label class="form-label small fw-semibold mb-1">Cliente (opcional)</label>
                            <button type="button" class="btn btn-light border w-100 d-flex align-items-center justify-content-between"
                                    data-bs-toggle="modal" data-bs-target="#clientModal">
                                <span class="d-flex align-items-center gap-2 text-truncate">
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span id="selectedClientLabel" class="text-truncate">Sin cliente</span>
                                </span>
                                <i class="bi bi-search text-muted small"></i>
                            </button>
                        </div>

                        {{-- Branch + valid_until --}}
                        <div class="px-3 pt-2 pb-1 border-bottom">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold mb-1" for="branch_id">Sucursal</label>
                                <select name="branch_id" id="branch_id" class="form-select form-select-sm">
                                    <option value="">— Sucursal —</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-semibold mb-1" for="valid_until">Válida hasta</label>
                                <input type="date" name="valid_until" id="valid_until" class="form-control form-control-sm"
                                       value="{{ now()->addDays(15)->format('Y-m-d') }}">
                            </div>
                        </div>

                        {{-- Totals --}}
                        <div class="px-3 pt-2 pb-1">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">Subtotal</span>
                                <span id="cartSubtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <label class="text-muted small mb-0" for="discount">Descuento</label>
                                <div class="input-group input-group-sm" style="width:110px;">
                                    <span class="input-group-text bg-light px-2">$</span>
                                    <input type="number" id="discount" name="discount"
                                           class="form-control text-end" min="0" step="0.01"
                                           value="0" placeholder="0.00"
                                           oninput="recalcCart()">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1">
                                <span>TOTAL</span>
                                <span id="cartTotal" class="fs-5 text-dark">$0.00</span>
                            </div>
                        </div>

                        {{-- Action --}}
                        <div class="p-3">
                            <button type="button" class="btn btn-primary w-100 py-2" id="btnGenQuote"
                                    onclick="submitQuote()">
                                <i class="bi bi-calculator me-2"></i>Generar cotización
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hidden cart inputs --}}
                <div id="cartInputs"></div>

            </form>
        </div>

    </div>
</div>

{{-- ─── CLIENT SEARCH MODAL ───────────────────────────────────────────── --}}
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="clientModalLabel">
                    <i class="bi bi-person-circle me-2 text-muted"></i>Seleccionar cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="clientSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre o documento..." autocomplete="off">
                </div>
                <div id="clientList" class="list-group" style="max-height:50vh;overflow-y:auto;">
                    {{-- Filled by JS --}}
                </div>
                <div id="noClientsMsg" class="text-center py-4 text-muted" style="display:none;">
                    <i class="bi bi-person-x d-block fs-2 mb-2 opacity-25"></i>
                    Sin coincidencias.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── PRODUCT DETAIL MODAL ──────────────────────────────────────────── --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-box-seam me-2 text-muted"></i>Detalle del producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div id="pmGallery"></div>
                    </div>
                    <div class="col-md-7">
                        <h5 class="fw-bold mb-1" id="pmName"></h5>
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <span class="text-muted small" id="pmSku"></span>
                            <span id="pmStock" class="badge"></span>
                        </div>
                        <div class="fs-4 fw-bold mb-3" id="pmPrice"></div>
                        <dl class="row g-1 small mb-3">
                            <dt class="col-5 text-muted fw-normal">Categoría</dt>
                            <dd class="col-7 mb-0" id="pmCategory"></dd>
                            <dt class="col-5 text-muted fw-normal">Marca</dt>
                            <dd class="col-7 mb-0" id="pmBrand"></dd>
                        </dl>
                        <div class="mb-2" id="pmModelsRow">
                            <div class="text-muted small fw-semibold mb-1">Modelos compatibles</div>
                            <div class="small" id="pmModels"></div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small fw-semibold mb-1">Descripción</div>
                            <div class="small text-muted" id="pmDesc"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="pmFichaBtn" target="_blank" class="btn btn-primary"
                   data-base-url="{{ route('products.show', '__ID__') }}">
                    <i class="bi bi-eye me-1"></i>Ver ficha completa
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.product-card {
    cursor: pointer;
    border: 1.5px solid #e8e8e8;
    border-radius: 10px;
    padding: 10px;
    background: #fff;
    transition: all .15s ease;
    user-select: none;
    position: relative;
    height: 100%;
}
.product-card:hover { border-color: var(--brand-black,#0a0a0a); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.product-card.in-cart { border-color: #0a0a0a; background: #fafafa; }
.product-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 6px; background:#f5f5f5; }
.product-thumb-placeholder {
    width: 100%; aspect-ratio: 1; border-radius: 6px; background:#f0f0f0;
    display: flex; align-items: center; justify-content: center; color:#ccc; font-size:1.6rem;
}
.cart-qty-input { width:56px; text-align:center; }
.cat-filter-bar {
    display: flex;
    gap: .4rem;
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: .25rem;
    scrollbar-width: thin;
}
.cat-filter-bar::-webkit-scrollbar { height: 4px; }
.cat-filter-bar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
.cat-pill {
    display: inline-flex;
    align-items: center;
    padding: .3rem .85rem;
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
.cat-pill:hover { border-color: #0a0a0a; color: #0a0a0a; }
.cat-pill.active { background: #0a0a0a; border-color: #0a0a0a; color: #fff; }
.product-info-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,.9);
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    color: #666;
    cursor: pointer;
    z-index: 2;
    transition: all .15s;
    padding: 0;
    line-height: 1;
}
.product-info-btn:hover { background: #fff; border-color: #0a0a0a; color: #0a0a0a; }
.pm-gallery-main { width:100%; aspect-ratio:1; object-fit:cover; border-radius:10px; background:#f5f5f5; }
.pm-gallery-placeholder { width:100%; aspect-ratio:1; border-radius:10px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-size:3rem; color:#ccc; }
.pm-thumb { width:56px; height:56px; object-fit:cover; border-radius:6px; border:2px solid transparent; cursor:pointer; transition:border-color .15s; }
.pm-thumb.active, .pm-thumb:hover { border-color:#0a0a0a; }
</style>
@endpush

@push('scripts')
<script>
const PRODUCTS = @json($productsData);
const CLIENTS  = @json($clientsJson);
let cart = {};
let currentCategory = null;

// ── CLIENT SEARCH MODAL ─────────────────────────────────────────────
function renderClientList(filter) {
    const listEl = document.getElementById('clientList');
    const noMsg  = document.getElementById('noClientsMsg');
    const q = (filter || '').toLowerCase().trim();
    const matches = q
        ? CLIENTS.filter(c =>
            c.name.toLowerCase().includes(q) ||
            (c.id_number && c.id_number.toLowerCase().includes(q)))
        : CLIENTS;

    let html = `
        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                onclick="selectClient('', 'Sin cliente')">
            <i class="bi bi-people text-muted"></i>
            <span class="fw-semibold">Sin cliente</span>
        </button>`;

    if (matches.length === 0 && q) {
        noMsg.style.display = '';
    } else {
        noMsg.style.display = 'none';
        html += matches.map(c => `
            <button type="button" class="list-group-item list-group-item-action"
                    onclick="selectClient(${c.id}, '${(c.name + (c.id_number ? ' (' + c.id_number + ')' : '')).replace(/'/g, "\\'")}')">
                <div class="fw-semibold">${c.name}</div>
                ${c.id_number ? `<small class="text-muted"><i class="bi bi-card-text me-1"></i>${c.id_number}</small>` : ''}
            </button>`).join('');
    }
    listEl.innerHTML = html;
}

function selectClient(id, label) {
    document.getElementById('client_id').value = id;
    document.getElementById('selectedClientLabel').textContent = label;
    bootstrap.Modal.getInstance(document.getElementById('clientModal'))?.hide();
}

// ── PRODUCT DETAIL MODAL ────────────────────────────────────────────
function openProductModal(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p) return;
    const modal = document.getElementById('productModal');

    let galleryHtml = '';
    if (p.photos && p.photos.length > 0) {
        galleryHtml = `
            <img id="pmMainImg" src="${p.photos[0]}" class="pm-gallery-main mb-2" alt="${p.name}">
            ${p.photos.length > 1 ? `<div class="d-flex gap-2 flex-wrap">
                ${p.photos.map((ph, i) => `<img src="${ph}" class="pm-thumb ${i===0?'active':''}" onclick="pmSwap(this, '${ph}')" alt="">`).join('')}
            </div>` : ''}`;
    } else {
        galleryHtml = `<div class="pm-gallery-placeholder"><i class="bi bi-box-seam"></i></div>`;
    }

    modal.querySelector('#pmGallery').innerHTML = galleryHtml;
    modal.querySelector('#pmName').textContent  = p.name;
    modal.querySelector('#pmSku').textContent   = p.sku;
    modal.querySelector('#pmPrice').textContent = '$' + p.price.toFixed(2);
    modal.querySelector('#pmStock').textContent = p.stock > 0 ? p.stock + ' en stock' : 'Sin stock';
    modal.querySelector('#pmStock').className   = 'badge ' + (p.stock > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle');
    modal.querySelector('#pmCategory').textContent = p.category || '—';
    modal.querySelector('#pmBrand').textContent    = p.brand || '—';
    modal.querySelector('#pmDesc').textContent     = p.description || '—';

    const modelsRow = modal.querySelector('#pmModelsRow');
    if (p.compatible_models) {
        modelsRow.style.display = '';
        modal.querySelector('#pmModels').textContent = p.compatible_models;
    } else {
        modelsRow.style.display = 'none';
    }

    const fichaBtn = modal.querySelector('#pmFichaBtn');
    fichaBtn.href = fichaBtn.dataset.baseUrl.replace('__ID__', p.id);

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function pmSwap(thumb, src) {
    document.getElementById('pmMainImg').src = src;
    document.querySelectorAll('.pm-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// ── RENDER PRODUCT GRID ─────────────────────────────────────────────
function renderGrid(filter) {
    const grid = document.getElementById('productGrid');
    const noMsg = document.getElementById('noProductsMsg');
    const q = (filter || '').toLowerCase().trim();

    let list = q
        ? PRODUCTS.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            p.category.toLowerCase().includes(q))
        : PRODUCTS.slice();

    if (currentCategory) {
        list = list.filter(p => p.category_id == currentCategory);
    }

    if (list.length === 0) {
        grid.innerHTML = '';
        noMsg.style.display = '';
        return;
    }
    noMsg.style.display = 'none';

    grid.innerHTML = list.map(p => {
        const inCart = cart[p.id] ? 'in-cart' : '';
        const stockBadge = p.stock > 0
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">${p.stock} en stock</span>`
            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem;">Sin stock</span>`;
        const img = p.photo
            ? `<img src="${p.photo}" class="product-thumb mb-2" alt="${p.name}">`
            : `<div class="product-thumb-placeholder mb-2"><i class="bi bi-box-seam"></i></div>`;
        return `
        <div class="col-6 col-md-4 col-xl-3">
            <div class="product-card ${inCart}" onclick="addToCart(${p.id})" data-pid="${p.id}">
                <button type="button" class="product-info-btn" onclick="event.stopPropagation();openProductModal(${p.id})" title="Ver detalle">
                    <i class="bi bi-eye"></i>
                </button>
                ${img}
                <div class="fw-semibold lh-sm mb-1" style="font-size:.8rem;">${p.name}</div>
                <div class="text-muted mb-1" style="font-size:.7rem;">${p.sku}</div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:.85rem;">$${p.price.toFixed(2)}</span>
                    ${stockBadge}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── CART LOGIC ──────────────────────────────────────────────────────
function addToCart(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p) return;
    if (cart[pid]) {
        cart[pid].qty++;
    } else {
        cart[pid] = { product: p, qty: 1 };
    }
    renderCart();
    highlightProductCard(pid);
}

function removeFromCart(pid) {
    delete cart[pid];
    renderCart();
    highlightProductCard(pid);
}

function highlightProductCard(pid) {
    document.querySelectorAll(`[data-pid="${pid}"]`).forEach(el => {
        el.classList.toggle('in-cart', !!cart[pid]);
    });
}

function renderCart() {
    const body   = document.getElementById('cartBody');
    const empty  = document.getElementById('cartEmpty');
    const table  = document.getElementById('cartTable');
    const count  = document.getElementById('cartCount');
    const inputs = document.getElementById('cartInputs');

    const items = Object.values(cart);
    count.textContent = items.length + (items.length === 1 ? ' item' : ' items');

    if (items.length === 0) {
        empty.style.display = '';
        table.style.display = 'none';
        inputs.innerHTML = '';
        recalcCart();
        return;
    }
    empty.style.display = 'none';
    table.style.display = '';

    body.innerHTML = items.map((it) => {
        const sub = (it.qty * it.product.price).toFixed(2);
        return `
        <tr>
            <td class="ps-3 py-2">
                <div class="fw-semibold lh-sm" style="font-size:.78rem;">${it.product.name}</div>
                <div class="text-muted" style="font-size:.68rem;">${it.product.sku}</div>
            </td>
            <td class="text-center py-2">
                <input type="number" class="form-control form-control-sm cart-qty-input"
                       min="1" step="1" inputmode="numeric"
                       value="${it.qty}"
                       oninput="updateQty(${it.product.id}, this.value)">
            </td>
            <td class="text-end py-2">$${it.product.price.toFixed(2)}</td>
            <td class="text-end py-2 fw-semibold">$${sub}</td>
            <td class="pe-2 py-2">
                <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                        onclick="removeFromCart(${it.product.id})" title="Quitar">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        </tr>`;
    }).join('');

    inputs.innerHTML = items.map((it, i) => `
        <input type="hidden" name="items[${i}][product_id]" value="${it.product.id}">
        <input type="hidden" name="items[${i}][quantity]"   value="${it.qty}">
        <input type="hidden" name="items[${i}][unit_price]" value="${it.product.price}">
    `).join('');

    recalcCart();
}

function updateQty(pid, val) {
    if (!cart[pid]) return;
    const qty = Math.max(1, Math.floor(parseInt(val, 10) || 1));
    cart[pid].qty = qty;
    renderCart();
}

function recalcCart() {
    const items = Object.values(cart);
    const sub   = items.reduce((s, it) => s + it.qty * it.product.price, 0);
    const disc  = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, sub - disc);
    document.getElementById('cartSubtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('cartTotal').textContent    = '$' + total.toFixed(2);
    return { sub, disc, total };
}

// ── SUBMIT ──────────────────────────────────────────────────────────
function submitQuote() {
    if (Object.keys(cart).length === 0) { alert('Agrega al menos un producto.'); return; }
    const btn = document.getElementById('btnGenQuote');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando…';
    document.getElementById('quoteForm').requestSubmit();
}

// ── SEARCH ──────────────────────────────────────────────────────────
function clearSearch() {
    document.getElementById('productSearch').value = '';
    document.getElementById('clearSearch').style.display = 'none';
    renderGrid('');
}

document.addEventListener('DOMContentLoaded', function () {
    renderGrid('');

    document.querySelectorAll('.cat-pill').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.cat || null;
            renderGrid(document.getElementById('productSearch').value);
        });
    });

    const searchInput = document.getElementById('productSearch');
    searchInput.addEventListener('input', function () {
        document.getElementById('clearSearch').style.display = this.value ? '' : 'none';
        renderGrid(this.value);
    });

    renderClientList('');
    const clientSearch = document.getElementById('clientSearch');
    clientSearch.addEventListener('input', function () {
        renderClientList(this.value);
    });
    document.getElementById('clientModal').addEventListener('shown.bs.modal', function () {
        clientSearch.value = '';
        renderClientList('');
        clientSearch.focus();
    });
});
</script>
@endpush

@endsection
