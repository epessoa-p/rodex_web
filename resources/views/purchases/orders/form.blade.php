@php
    $isEdit = isset($order);
    $action = $isEdit ? route('purchase-orders.update', $order) : route('purchase-orders.store');
    $method = $isEdit ? 'PUT' : 'POST';
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

<form action="{{ $action }}" method="POST" id="orderForm">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- Left: order details --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la orden</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="supplier_id">
                                Proveedor <span class="text-danger">*</span>
                            </label>
                            <select id="supplier_id" name="supplier_id"
                                    class="form-select @error('supplier_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar proveedor —</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    {{ old('supplier_id', $isEdit ? $order->supplier_id : '') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="branch_id">Sucursal destino</label>
                            <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror">
                                <option value="">— Sin sucursal específica —</option>
                                @foreach($branches as $b)
                                <option value="{{ $b->id }}"
                                    {{ old('branch_id', $isEdit ? $order->branch_id : '') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="order_date">
                                Fecha de orden <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="order_date" name="order_date"
                                   class="form-control @error('order_date') is-invalid @enderror"
                                   value="{{ old('order_date', $isEdit ? $order->order_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                   required>
                            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="expected_date">Fecha esperada de entrega</label>
                            <input type="date" id="expected_date" name="expected_date"
                                   class="form-control @error('expected_date') is-invalid @enderror"
                                   value="{{ old('expected_date', $isEdit ? optional($order->expected_date)->format('Y-m-d') : '') }}">
                            @error('expected_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes" rows="2"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Indicaciones especiales, condiciones de entrega...">{{ old('notes', $isEdit ? $order->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos</h6>
                    <button type="button" class="btn btn-sm btn-light border" onclick="addItemRow()">
                        <i class="bi bi-plus-lg me-1"></i>Agregar producto
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted">Producto</th>
                                    <th class="py-3 small fw-semibold text-muted form-control-sm" style="width:90px">Cant.</th>
                                    <th class="py-3 small fw-semibold text-muted" style="width:120px">Costo <br> unit.</th>
                                    <th class="py-3 small fw-semibold text-muted text-end" style="width:110px">Subtotal</th>
                                    <th class="py-3 pe-3" style="width:44px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                {{-- Rows injected by JS --}}
                            </tbody>
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
                                <span>Subtotal</span>
                                <span id="displaySubtotal">{{ currency_symbol() }} 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <label class="text-muted mb-0" for="tax">Impuesto</label>
                                <div class="input-group input-group-sm" style="width:130px">
                                    <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                    <input type="number" id="tax" name="tax" step="0.01" min="0"
                                           class="form-control text-end"
                                           value="{{ old('tax', $isEdit ? $order->tax : '0.00') }}"
                                           placeholder="0.00"
                                           oninput="recalcTotals()">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total</span>
                                <span id="displayTotal" class="text-dark">{{ currency_symbol() }} 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right: summary / actions --}}
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
                        <span id="summaryTotal" class="text-dark fs-5">{{ currency_symbol() }} 0.00</span>
                    </div>
                    <div class="d-flex flex-column gap-2 mt-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear orden' }}
                        </button>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-light border w-100">
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
.item-row td { padding-top: .6rem; padding-bottom: .6rem; }

/* Layout fijo: el producto ocupa el espacio restante y no empuja al costo/subtotal */
#itemsTable { table-layout: fixed; width: 100%; }
#itemsTable td, #itemsTable th { overflow: hidden; }
/* El select de producto se adapta a la celda y trunca el nombre largo con "…" */
#itemsTable .product-select + .select2-container { max-width: 100%; }
#itemsTable .select2-selection__rendered {
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    padding-right: 18px;
}
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
let rowIndex = 0;

function buildProductOptions(selectedId) {
    let opts = '<option value="">— Seleccionar —</option>';
    PRODUCTS.forEach(p => {
        const sel = selectedId && String(p.id) === String(selectedId) ? 'selected' : '';
        opts += `<option value="${p.id}" data-cost="${p.cost}" ${sel}>${p.name} (${p.sku})</option>`;
    });
    return opts;
}

function addItemRow(productId, quantity, unitCost) {
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
                   value="${quantity || ''}"
                   placeholder="1"
                   oninput="recalcRow(${idx})">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">{{ currency_symbol() }}</span>
                <input type="number" name="items[${idx}][unit_cost]" step="0.01" min="0"
                       class="form-control cost-input text-end" required
                       value="${unitCost || ''}"
                       placeholder="0.00"
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

    if (unitCost) recalcRow(idx);
    recalcTotals();
}

function onProductChange(select, idx) {
    const option = select.options[select.selectedIndex];
    const cost   = option.dataset.cost || '';
    const row    = document.querySelector(`tr[data-index="${idx}"]`);
    const costInput = row.querySelector('.cost-input');
    if (cost && !costInput.value) costInput.value = parseFloat(cost).toFixed(2);
    recalcRow(idx);
}

function recalcRow(idx) {
    const row  = document.querySelector(`tr[data-index="${idx}"]`);
    if (!row) return;
    const qty  = parseInt(row.querySelector('.qty-input').value, 10)  || 0;
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    const sub  = qty * cost;
    row.querySelector(`.subtotal-cell`).textContent = money(sub, 2);
    recalcTotals();
}

function recalcTotals() {
    let subtotal = 0;
    document.querySelectorAll('.subtotal-cell').forEach(cell => {
        subtotal += parseMoney(cell.textContent) || 0;
    });
    const tax   = parseFloat(document.getElementById('tax').value) || 0;
    const total = subtotal + tax;
    const rows  = document.querySelectorAll('.item-row').length;

    const fmt = v => money(v, 2);
    document.getElementById('displaySubtotal').textContent = fmt(subtotal);
    document.getElementById('displayTotal').textContent    = fmt(total);
    document.getElementById('summaryItems').textContent    = rows;
    document.getElementById('summarySubtotal').textContent = fmt(subtotal);
    document.getElementById('summaryTax').textContent      = fmt(tax);
    document.getElementById('summaryTotal').textContent    = fmt(total);
    document.getElementById('emptyItemsMsg').style.display = rows > 0 ? 'none' : 'block';
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalcTotals();
}

// Pre-fill on edit
document.addEventListener('DOMContentLoaded', function () {
    @if($isEdit && $order->items->count())
    @foreach($order->items as $item)
    addItemRow({{ $item->product_id }}, {{ $item->quantity }}, {{ $item->unit_cost }});
    @endforeach
    @else
    addItemRow();
    @endif
    recalcTotals();
});
</script>
@endpush
