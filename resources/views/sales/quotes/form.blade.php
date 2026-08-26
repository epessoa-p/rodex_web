@php
    $isEdit = isset($quote);
    $formAction = $isEdit ? route('quotes.update', $quote) : route('quotes.store');
    $productsJson = $products->map(fn($p) => [
        'id'    => $p->id,
        'name'  => $p->name,
        'sku'   => $p->sku,
        'price' => (float) $p->price,
    ])->values();
    $prefillRows = $isEdit ? $quote->items->map(fn($i) => [
        'product_id' => $i->product_id,
        'quantity'   => $i->quantity,
        'unit_price' => $i->unit_price,
        'discount'   => $i->discount,
    ])->values() : collect();
@endphp

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ $formAction }}" method="POST" id="quoteForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

        {{-- ── MAIN (left) ────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Datos de la cotización --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la cotización</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="branch_id">Sucursal</label>
                            <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">— Sucursal —</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}"
                                    {{ old('branch_id', $isEdit ? $quote->branch_id : '') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="client_id">Cliente</label>
                            <select id="client_id" name="client_id"
                                    class="form-select @error('client_id') is-invalid @enderror">
                                <option value="">Cliente general</option>
                                @foreach($clients as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('client_id', $isEdit ? $quote->client_id : '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="quote_date">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="quote_date" name="quote_date"
                                   class="form-control @error('quote_date') is-invalid @enderror"
                                   value="{{ old('quote_date', $isEdit ? $quote->quote_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                            @error('quote_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="valid_until">Válida hasta</label>
                            <input type="date" id="valid_until" name="valid_until"
                                   class="form-control @error('valid_until') is-invalid @enderror"
                                   value="{{ old('valid_until', $isEdit && $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '') }}">
                            @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Observaciones opcionales...">{{ old('notes', $isEdit ? $quote->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Productos --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos</h6>
                    <button type="button" class="btn btn-sm btn-light border" onclick="addItemRow()">
                        <i class="bi bi-plus-lg me-1"></i>Agregar producto
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted" style="min-width:200px;font-size:.72rem;">Producto</th>
                                    <th class="py-3 fw-semibold text-muted" style="width:100px;font-size:.72rem;">Cantidad</th>
                                    <th class="py-3 fw-semibold text-muted" style="width:120px;font-size:.72rem;">Precio unit.</th>
                                    <th class="py-3 fw-semibold text-muted" style="width:110px;font-size:.72rem;">Descuento</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="width:110px;font-size:.72rem;">Subtotal</th>
                                    <th class="py-3 pe-3" style="width:44px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                    <div id="emptyItemsMsg" class="text-center py-4 text-muted small">
                        <i class="bi bi-box-seam d-block fs-3 mb-1 opacity-25"></i>
                        Sin productos. Use "Agregar producto".
                    </div>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3">
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Subtotal productos</span>
                                <span id="displaySubtotal">{{ currency_symbol() }} 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <label class="text-muted mb-0" for="global_discount">Descuento global</label>
                                <div class="input-group input-group-sm" style="width:120px">
                                    <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                    <input type="number" id="global_discount" name="discount"
                                           step="0.01" min="0"
                                           class="form-control text-end"
                                           value="{{ old('discount', $isEdit ? $quote->discount : '0.00') }}"
                                           placeholder="0.00"
                                           oninput="recalcTotals()">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <label class="text-muted mb-0" for="tax">Impuesto</label>
                                <div class="input-group input-group-sm" style="width:120px">
                                    <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                    <input type="number" id="tax" name="tax"
                                           step="0.01" min="0"
                                           class="form-control text-end"
                                           value="{{ old('tax', $isEdit ? $quote->tax : '0.00') }}"
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

        {{-- ── RIGHT SIDEBAR ─────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
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
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Descuento</span>
                        <span class="fw-semibold" id="summaryDiscount">{{ currency_symbol() }} 0.00</span>
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Actualizar cotización' : 'Guardar cotización' }}
                        </button>
                        <a href="{{ $isEdit ? route('quotes.show', $quote) : route('quotes.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@push('styles')
<style>
.item-row td { padding-top:.55rem; padding-bottom:.55rem; }
/* El producto ocupa un ancho fijo y trunca nombres largos; las demás columnas quedan visibles */
#itemsTable { table-layout: fixed; width: 100%; }
#itemsTable td, #itemsTable th { overflow: hidden; }
#itemsTable th:first-child, #itemsTable td:first-child { width: 36%; }
#itemsTable .select2-selection__rendered { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 18px; }
</style>
@endpush

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const PREFILL_ROWS = @json($prefillRows);
let rowIndex = 0;

function buildProductOptions(selectedId) {
    let opts = '<option value="">— Seleccionar —</option>';
    PRODUCTS.forEach(p => {
        const sel = selectedId && String(p.id) === String(selectedId) ? 'selected' : '';
        opts += `<option value="${p.id}" data-price="${p.price}" ${sel}>${p.name} (${p.sku})</option>`;
    });
    return opts;
}

function addItemRow(productId, qty, unitPrice, discount) {
    const tbody = document.getElementById('itemsBody');
    const idx   = rowIndex++;
    const tr    = document.createElement('tr');
    tr.className = 'item-row border-bottom border-light';
    tr.dataset.index = idx;
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
                   value="${qty || ''}" placeholder="1"
                   oninput="recalcRow(${idx})">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">{{ currency_symbol() }}</span>
                <input type="number" name="items[${idx}][unit_price]" step="0.01" min="0"
                       class="form-control price-input text-end" required
                       value="${unitPrice || ''}" placeholder="0.00"
                       oninput="recalcRow(${idx})">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">{{ currency_symbol() }}</span>
                <input type="number" name="items[${idx}][discount]" step="0.01" min="0"
                       class="form-control line-disc-input text-end"
                       value="${discount !== undefined ? discount : 0}" placeholder="0.00"
                       oninput="recalcRow(${idx})">
            </div>
        </td>
        <td class="text-end fw-semibold small subtotal-cell pe-2" data-index="${idx}">{{ currency_symbol() }} 0.00</td>
        <td class="pe-3">
            <button type="button" class="btn btn-sm btn-light border text-danger" onclick="removeRow(this)" title="Quitar">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    if (window.initSelect2) window.initSelect2(tr);
    document.getElementById('emptyItemsMsg').style.display = 'none';
    if (unitPrice) recalcRow(idx);
    recalcTotals();
}

function onProductChange(select, idx) {
    const opt   = select.options[select.selectedIndex];
    const price = opt.dataset.price || '';
    const row   = document.querySelector(`tr[data-index="${idx}"]`);
    const pi    = row.querySelector('.price-input');
    if (price && !pi.value) pi.value = parseFloat(price).toFixed(2);
    recalcRow(idx);
}

function recalcRow(idx) {
    const row  = document.querySelector(`tr[data-index="${idx}"]`);
    if (!row) return;
    const qty  = parseInt(row.querySelector('.qty-input')?.value, 10)        || 0;
    const pr   = parseFloat(row.querySelector('.price-input')?.value)      || 0;
    const disc = parseFloat(row.querySelector('.line-disc-input')?.value)  || 0;
    const sub  = Math.max(0, qty * pr - disc);
    const cell = row.querySelector('.subtotal-cell');
    if (cell) cell.textContent = money(sub, 2);
    recalcTotals();
}

function recalcTotals() {
    let sub = 0;
    document.querySelectorAll('.subtotal-cell').forEach(c => {
        sub += parseMoney(c.textContent) || 0;
    });
    const disc  = parseFloat(document.getElementById('global_discount').value) || 0;
    const tax   = parseFloat(document.getElementById('tax').value) || 0;
    const total = Math.max(0, sub - disc + tax);
    const rows  = document.querySelectorAll('.item-row').length;
    const fmt   = v => money(v, 2);
    document.getElementById('displaySubtotal').textContent  = fmt(sub);
    document.getElementById('displayTotal').textContent     = fmt(total);
    document.getElementById('summaryItems').textContent     = rows;
    document.getElementById('summarySubtotal').textContent  = fmt(sub);
    document.getElementById('summaryDiscount').textContent  = fmt(disc);
    document.getElementById('summaryTax').textContent       = fmt(tax);
    document.getElementById('summaryTotal').textContent     = fmt(total);
    const emptyMsg = document.getElementById('emptyItemsMsg');
    emptyMsg.style.display = rows > 0 ? 'none' : 'block';
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalcTotals();
}

document.addEventListener('DOMContentLoaded', function () {
    if (PREFILL_ROWS.length) {
        PREFILL_ROWS.forEach(it => {
            addItemRow(it.product_id, it.quantity, it.unit_price, it.discount);
        });
    } else {
        addItemRow();
    }
    recalcTotals();
});
</script>
@endpush
