@extends('layouts.app')
@section('title', 'Nueva factura de compra')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-receipt me-2 text-danger"></i>Nueva factura de compra</h1>
            <p class="text-muted mb-0 small">
                @if($fromOrder)
                    OC <strong>{{ $fromOrder->code }}</strong> &mdash; {{ $fromOrder->supplier->name }}
                    @if($uninvoicedReceipts->isNotEmpty())
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">
                            <i class="bi bi-box-arrow-in-down me-1"></i>{{ $uninvoicedReceipts->count() }} recepción(es) pendiente(s) de facturar
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-2">
                            <i class="bi bi-check-all me-1"></i>Todas las recepciones ya están facturadas
                        </span>
                    @endif
                @else
                    Registra una factura de proveedor.
                @endif
            </p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('purchases.store') }}" method="POST" id="invoiceForm">
        @csrf

        {{-- Hidden OC id if coming from order --}}
        @if($fromOrder)
        <input type="hidden" name="purchase_order_id" value="{{ $fromOrder->id }}">
        @endif

        <div class="row g-4">

            {{-- Main form --}}
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la factura</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="supplier_id">
                                    Proveedor <span class="text-danger">*</span>
                                </label>
                                <select id="supplier_id" name="supplier_id"
                                        class="form-select @error('supplier_id') is-invalid @enderror"
                                        required {{ $fromOrder ? 'disabled' : '' }}>
                                    <option value="">— Seleccionar proveedor —</option>
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}"
                                        {{ old('supplier_id', $fromOrder?->supplier_id) == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @if($fromOrder)
                                <input type="hidden" name="supplier_id" value="{{ $fromOrder->supplier_id }}">
                                @endif
                                @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if(!$fromOrder)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="purchase_order_id">
                                    Orden de compra
                                    <span class="text-muted fw-normal" style="font-size:.78rem;">
                                        (opcional — carga productos recibidos)
                                    </span>
                                </label>
                                <select id="purchase_order_id" name="purchase_order_id"
                                        class="form-select @error('purchase_order_id') is-invalid @enderror"
                                        onchange="if(this.value) window.location.href='{{ route('purchases.create') }}?order_id='+this.value">
                                    <option value="">— Sin orden de compra —</option>
                                    @foreach($orders as $ord)
                                    <option value="{{ $ord->id }}" {{ old('purchase_order_id') == $ord->id ? 'selected' : '' }}>
                                        {{ $ord->code }} — {{ $ord->supplier->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('purchase_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="invoice_number">N° de factura</label>
                                <input type="text" id="invoice_number" name="invoice_number"
                                       class="form-control @error('invoice_number') is-invalid @enderror"
                                       value="{{ old('invoice_number') }}"
                                       maxlength="100"
                                       placeholder="FAC-001">
                                @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="purchase_date">
                                    Fecha de factura <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="purchase_date" name="purchase_date"
                                       class="form-control @error('purchase_date') is-invalid @enderror"
                                       value="{{ old('purchase_date', now()->format('Y-m-d')) }}"
                                       required>
                                @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Selector de recepciones (solo cuando viene de una OC) --}}
                @if($fromOrder)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-box-arrow-in-down me-2 text-muted"></i>Seleccionar recepción(es) a facturar
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($uninvoicedReceipts->isEmpty())
                            <div class="alert alert-warning border-0 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Todas las recepciones de esta OC ya han sido facturadas. No se puede crear una nueva factura.
                            </div>
                        @else
                            <div class="row g-3" id="receiptCards">
                                @foreach($uninvoicedReceipts as $receipt)
                                @php
                                    $subtotal    = $receipt->items->sum(fn ($i) => $i->quantity * $i->unit_cost);
                                    $autoSelect  = $uninvoicedReceipts->count() === 1;
                                    $receiptJson = $receipt->items->map(fn ($i) => [
                                        'product_id' => $i->product_id,
                                        'name'       => $i->product?->name ?? '—',
                                        'quantity'   => (float) $i->quantity,
                                        'unit_cost'  => (float) $i->unit_cost,
                                    ])->values();
                                @endphp
                                <div class="col-md-6">
                                    <label class="receipt-card d-block rounded-3 border p-3 h-100"
                                           style="cursor:pointer;transition:border-color .15s,background .15s;"
                                           data-receipt-id="{{ $receipt->id }}">
                                        <div class="d-flex align-items-start gap-3">
                                            <input type="checkbox"
                                                   name="receipt_ids[]"
                                                   value="{{ $receipt->id }}"
                                                   class="form-check-input receipt-checkbox mt-1 flex-shrink-0"
                                                   {{ $autoSelect ? 'checked' : '' }}>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold mb-1">{{ $receipt->code }}</div>
                                                <div class="text-muted small mb-2">
                                                    <i class="bi bi-calendar3 me-1"></i>{{ $receipt->receipt_date->format('d/m/Y') }}
                                                    &nbsp;·&nbsp;
                                                    <i class="bi bi-building me-1"></i>{{ $receipt->warehouse?->name }}
                                                </div>
                                                <div class="small">
                                                    @foreach($receipt->items as $ri)
                                                    <div class="d-flex justify-content-between text-muted">
                                                        <span class="text-truncate me-2" style="max-width:160px;">{{ $ri->product?->name }}</span>
                                                        <span class="flex-shrink-0">{{ number_format($ri->quantity, 0) }} u.</span>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                <div class="mt-2 d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-light text-dark border">{{ $receipt->items->count() }} producto(s)</span>
                                                    <span class="fw-semibold">{{ money($subtotal, null, 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- Datos embebidos para JS --}}
                                        <script type="application/json" class="receipt-data">
                                        @json($receiptJson)
                                        </script>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Items --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos a facturar</h6>
                            @if($fromOrder && $uninvoicedReceipts->isNotEmpty())
                            <small class="text-muted">Se cargan al seleccionar recepciones arriba</small>
                            @endif
                        </div>
                        @if(!$fromOrder)
                        <button type="button" class="btn btn-sm btn-light border" onclick="addItemRow()">
                            <i class="bi bi-plus-lg me-1"></i>Agregar producto
                        </button>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 small fw-semibold text-muted" style="min-width:220px">Producto</th>
                                        <th class="py-3 small fw-semibold text-muted" style="width:110px">Cantidad</th>
                                        <th class="py-3 small fw-semibold text-muted" style="width:130px">Costo unit.</th>
                                        <th class="py-3 small fw-semibold text-muted text-end" style="width:120px">Subtotal</th>
                                        @if(!$fromOrder)
                                        <th class="py-3 pe-3" style="width:44px"></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    {{-- Rows injected by JS --}}
                                </tbody>
                            </table>
                        </div>
                        @if(!$fromOrder)
                        <div id="emptyItemsMsg" class="text-center py-4 text-muted small">
                            <i class="bi bi-box-seam d-block fs-3 mb-1 opacity-25"></i>
                            Sin productos. Use "Agregar producto".
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top px-4 py-3">
                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between mb-2 small text-muted">
                                    <span>Subtotal</span>
                                    <span id="displaySubtotal">{{ currency_symbol() }} 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 small">
                                    <label class="text-muted mb-0" for="tax">Impuesto</label>
                                    <div class="input-group input-group-sm" style="width:130px">
                                        <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                        <input type="number" id="tax" name="tax" step="0.01" min="0"
                                               class="form-control text-end"
                                               value="{{ old('tax', '0.00') }}"
                                               placeholder="0.00"
                                               oninput="recalcTotals()">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total</span>
                                    <span id="displayTotal">{{ currency_symbol() }} 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Resumen</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Productos</span>
                            <span class="fw-semibold" id="summaryItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold" id="summarySubtotal">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small">
                            <span class="text-muted">Impuesto</span>
                            <span class="fw-semibold" id="summaryTax">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3">
                            <span>Total</span>
                            <span id="summaryTotal" class="fs-5">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="submit" class="btn btn-primary w-100" id="btnSubmit"
                                    {{ ($fromOrder && $uninvoicedReceipts->isEmpty()) ? 'disabled' : '' }}>
                                <i class="bi bi-check-lg me-1"></i>Registrar compra
                            </button>
                            <a href="{{ route('purchases.index') }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@push('styles')
<style>
.item-row td { padding-top:.6rem; padding-bottom:.6rem; }
/* El producto ocupa un ancho fijo y trunca nombres largos; las demás columnas quedan visibles */
#itemsTable { table-layout: fixed; width: 100%; }
#itemsTable td, #itemsTable th { overflow: hidden; }
#itemsTable th:first-child, #itemsTable td:first-child { width: 38%; }
#itemsTable .select2-selection__rendered { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 18px; }
</style>
@endpush

@php
    $productsJson = $products->map(fn ($p) => [
        'id'   => $p->id,
        'name' => $p->name,
        'sku'  => $p->sku,
        'cost' => $p->cost,
    ])->values();
@endphp
@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const FROM_ORDER = @json((bool) $fromOrder);
let rowIndex = 0;

function buildProductOptions(selectedId) {
    let opts = '<option value="">— Seleccionar —</option>';
    PRODUCTS.forEach(p => {
        const sel = selectedId && String(p.id) === String(selectedId) ? 'selected' : '';
        opts += `<option value="${p.id}" data-cost="${p.cost}" ${sel}>${p.name} (${p.sku})</option>`;
    });
    return opts;
}

function addItemRow(productId, quantity, unitCost, locked) {
    const tbody = document.getElementById('itemsBody');
    const idx   = rowIndex++;
    const tr    = document.createElement('tr');
    tr.className = 'item-row border-bottom border-light';
    tr.dataset.index = idx;

    if (locked) {
        // Pre-filled from order — read-only display
        const productName = PRODUCTS.find(p => String(p.id) === String(productId))?.name || productId;
        tr.innerHTML = `
            <input type="hidden" name="items[${idx}][product_id]" value="${productId}">
            <input type="hidden" name="items[${idx}][quantity]"   value="${quantity}">
            <input type="hidden" name="items[${idx}][unit_cost]"  value="${unitCost}">
            <td class="ps-4 py-3 small fw-semibold">${productName}</td>
            <td class="py-3 small text-end">${quantity}</td>
            <td class="py-3 small text-end">${money(parseFloat(unitCost), 2)}</td>
            <td class="py-3 text-end fw-semibold small pe-4 subtotal-cell" data-index="${idx}">
                ${money((parseFloat(quantity) * parseFloat(unitCost)), 2)}
            </td>`;
    } else {
        tr.innerHTML = `
            <td class="ps-4">
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select" required
                        onchange="onProductChange(this, ${idx})">
                    ${buildProductOptions(productId)}
                </select>
            </td>
            <td>
                <input type="number" name="items[${idx}][quantity]" step="1" min="1" inputmode="numeric"
                       class="form-control form-control-sm qty-input text-end" required
                       value="${quantity || ''}" placeholder="1"
                       oninput="recalcRow(${idx})">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light px-2">{{ currency_symbol() }}</span>
                    <input type="number" name="items[${idx}][unit_cost]" step="0.01" min="0"
                           class="form-control cost-input text-end" required
                           value="${unitCost || ''}" placeholder="0.00"
                           oninput="recalcRow(${idx})">
                </div>
            </td>
            <td class="text-end fw-semibold small subtotal-cell pe-2" data-index="${idx}">{{ currency_symbol() }} 0.00</td>
            <td class="pe-3">
                <button type="button" class="btn btn-sm btn-light border text-danger" onclick="removeRow(this)" title="Quitar">
                    <i class="bi bi-trash"></i>
                </button>
            </td>`;
    }
    tbody.appendChild(tr);
    if (window.initSelect2) window.initSelect2(tr);
    const emptyMsg = document.getElementById('emptyItemsMsg');
    if (emptyMsg) emptyMsg.style.display = 'none';
    if (unitCost && !locked) recalcRow(idx);
    recalcTotals();
}

function onProductChange(select, idx) {
    const opt  = select.options[select.selectedIndex];
    const cost = opt.dataset.cost || '';
    const row  = document.querySelector(`tr[data-index="${idx}"]`);
    const ci   = row.querySelector('.cost-input');
    if (cost && !ci.value) ci.value = parseFloat(cost).toFixed(2);
    recalcRow(idx);
}

function recalcRow(idx) {
    const row  = document.querySelector(`tr[data-index="${idx}"]`);
    if (!row) return;
    const qty  = parseInt(row.querySelector('.qty-input')?.value, 10)  || 0;
    const cost = parseFloat(row.querySelector('.cost-input')?.value) || 0;
    const cell = row.querySelector('.subtotal-cell');
    if (cell) cell.textContent = money(qty * cost, 2);
    recalcTotals();
}

function recalcTotals() {
    let sub = 0;
    document.querySelectorAll('.subtotal-cell').forEach(c => {
        sub += parseMoney(c.textContent) || 0;
    });
    const tax   = parseFloat(document.getElementById('tax').value) || 0;
    const total = sub + tax;
    const rows  = document.querySelectorAll('.item-row').length;
    const fmt   = v => money(v, 2);
    document.getElementById('displaySubtotal').textContent = fmt(sub);
    document.getElementById('displayTotal').textContent    = fmt(total);
    document.getElementById('summaryItems').textContent    = rows;
    document.getElementById('summarySubtotal').textContent = fmt(sub);
    document.getElementById('summaryTax').textContent      = fmt(tax);
    document.getElementById('summaryTotal').textContent    = fmt(total);
    const emptyMsg = document.getElementById('emptyItemsMsg');
    if (emptyMsg) emptyMsg.style.display = rows > 0 ? 'none' : 'block';
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalcTotals();
}

// ── Lógica de selección de recepciones ───────────────────────
@if($fromOrder && $uninvoicedReceipts->isNotEmpty())

function loadReceiptItems() {
    const tbody   = document.getElementById('itemsBody');
    const btnSubmit = document.getElementById('btnSubmit');
    tbody.innerHTML = '';
    rowIndex = 0;

    // Recolectar items de todas las tarjetas marcadas
    const checked = document.querySelectorAll('.receipt-checkbox:checked');
    if (checked.length === 0) {
        if (btnSubmit) btnSubmit.disabled = true;
        recalcTotals();
        return;
    }
    if (btnSubmit) btnSubmit.disabled = false;

    const seen = {}; // product_id → {qty, cost}
    checked.forEach(cb => {
        const card  = cb.closest('.receipt-card');
        const data  = JSON.parse(card.querySelector('.receipt-data').textContent);
        data.forEach(item => {
            if (seen[item.product_id]) {
                seen[item.product_id].qty += item.quantity;
            } else {
                seen[item.product_id] = { qty: item.quantity, cost: item.unit_cost, name: item.name };
            }
        });
    });

    Object.entries(seen).forEach(([pid, info]) => {
        addItemRow(pid, info.qty, info.cost, true);
    });
    recalcTotals();
}

document.addEventListener('DOMContentLoaded', function () {
    // Estilos de tarjetas seleccionadas
    function updateCardStyles() {
        document.querySelectorAll('.receipt-card').forEach(card => {
            const cb = card.querySelector('.receipt-checkbox');
            if (cb.checked) {
                card.style.borderColor = 'var(--brand-black, #22242e)';
                card.style.background  = '#f8f8f8';
            } else {
                card.style.borderColor = '';
                card.style.background  = '';
            }
        });
    }

    document.querySelectorAll('.receipt-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            updateCardStyles();
            loadReceiptItems();
        });
        // Click en la tarjeta activa el checkbox
        const card = cb.closest('.receipt-card');
        card.addEventListener('click', function (e) {
            if (e.target.tagName !== 'INPUT') {
                cb.checked = !cb.checked;
                cb.dispatchEvent(new Event('change'));
            }
        });
    });

    updateCardStyles();
    loadReceiptItems();
});

@elseif($fromOrder)
    {{-- Todas facturadas: no hacer nada --}}
    document.addEventListener('DOMContentLoaded', recalcTotals);
@else
    document.addEventListener('DOMContentLoaded', function () {
        addItemRow();
        recalcTotals();
    });
@endif
</script>
@endpush
@endsection
