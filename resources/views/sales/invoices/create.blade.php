@extends('layouts.app')
@section('title', 'Nueva venta')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-receipt me-2 text-danger"></i>Nueva venta</h1>
            <p class="text-muted mb-0 small">Registra una venta formal.</p>
        </div>
        <a href="{{ route('sales.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if(isset($fromQuote) && $fromQuote)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-file-earmark-text flex-shrink-0 fs-5"></i>
        <span>Generando venta desde cotización <strong>{{ $fromQuote->code }}</strong></span>
    </div>
    @endif

    @php $fromApplication = $fromApplication ?? null; @endphp
    @if($fromApplication)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-file-earmark-medical flex-shrink-0 fs-5"></i>
        <span>Generando venta desde solicitud <strong>{{ $fromApplication->code }}</strong>
            @if($fromApplication->client) — {{ $fromApplication->client->full_name }} @endif
        </span>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
        @csrf
        @if(isset($fromQuote) && $fromQuote)
        <input type="hidden" name="quote_id" value="{{ $fromQuote->id }}">
        @endif
        @if($fromApplication)
        <input type="hidden" name="application_id" value="{{ $fromApplication->id }}">
        @endif
        <input type="hidden" name="payment_plan_id" id="plan_id">
        <input type="hidden" name="interest" id="interest_input" value="0">
        <input type="hidden" name="sale_type" id="sale_type" value="cash">

        <div class="row g-4">

            {{-- ── MAIN (left) ──────────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Datos --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la venta</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            {{-- Caja / sucursal (desde la caja abierta del personal) --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Caja / Sucursal</label>
                                @if($session)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 px-3 py-2">
                                        <span class="rounded-circle bg-success" style="width:7px;height:7px;display:inline-block;"></span>
                                        {{ $session->cashRegister->branch->name ?? 'Sucursal' }} · {{ $session->cashRegister->name ?? 'Caja' }}
                                    </span>
                                    <small class="text-muted">La venta se registra en la sucursal de tu caja abierta.</small>
                                </div>
                                @else
                                <div class="alert alert-warning border-0 shadow-sm mb-0 py-2 px-3 small">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    No tienes una caja abierta. <a href="{{ route('cash-registers.index') }}">Abre tu caja</a> para registrar ventas.
                                </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="client_id">Cliente</label>
                                @php
                                    $preselectedClientId = old('client_id', $fromQuote?->client_id ?? ($fromApplication?->client_id ?? ''));
                                @endphp
                                <div class="input-group">
                                    <select id="client_id" name="client_id"
                                            class="form-select @error('client_id') is-invalid @enderror">
                                        <option value="">Cliente general</option>
                                        @foreach($clients as $c)
                                        <option value="{{ $c->id }}" {{ $preselectedClientId == $c->id ? 'selected' : '' }}>
                                            {{ $c->full_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-light border" data-no-search
                                            data-bs-toggle="modal" data-bs-target="#newClientModal" title="Registrar nuevo cliente">
                                        <i class="bi bi-person-plus"></i>
                                    </button>
                                </div>
                                @error('client_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="sale_date">
                                    Fecha <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="sale_date" name="sale_date"
                                       class="form-control @error('sale_date') is-invalid @enderror"
                                       value="{{ old('sale_date', now()->format('Y-m-d')) }}" required>
                                @error('sale_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones opcionales...">{{ old('notes') }}</textarea>
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
                                        <th class="ps-4 py-3 fw-semibold text-muted" style="min-width:200px;font-size:.72rem;" class="text-uppercase">Producto</th>
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
                                    <span id="displaySubtotal">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 small">
                                    <label class="text-muted mb-0" for="global_discount">Descuento global</label>
                                    <div class="input-group input-group-sm" style="width:120px">
                                        <span class="input-group-text bg-light">$</span>
                                        <input type="number" id="global_discount" name="discount"
                                               step="0.01" min="0"
                                               class="form-control text-end"
                                               value="{{ old('discount', '0.00') }}"
                                               placeholder="0.00"
                                               oninput="recalcTotals()">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 small">
                                    <label class="text-muted mb-0" for="tax">Impuesto</label>
                                    <div class="input-group input-group-sm" style="width:120px">
                                        <span class="input-group-text bg-light">$</span>
                                        <input type="number" id="tax" name="tax"
                                               step="0.01" min="0"
                                               class="form-control text-end"
                                               value="{{ old('tax', '0.00') }}"
                                               placeholder="0.00"
                                               oninput="recalcTotals()">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total</span>
                                    <span id="displayTotal">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT SIDEBAR ────────────────────────────────────── --}}
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
                            <span class="fw-semibold" id="summarySubtotal">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Descuento</span>
                            <span class="fw-semibold" id="summaryDiscount">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small">
                            <span class="text-muted">Impuesto</span>
                            <span class="fw-semibold" id="summaryTax">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3">
                            <span>Total</span>
                            <span id="summaryTotal" class="fs-5">$0.00</span>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="button" class="btn btn-primary w-100 py-2" id="btnContado" onclick="submitContado()">
                                <i class="bi bi-cash me-1"></i>Cobrar (Contado)
                            </button>
                            <button type="button" class="btn btn-light border w-100 py-2" id="btnCredito"
                                    data-bs-toggle="modal" data-bs-target="#saleCreditModal">
                                <i class="bi bi-calendar2-check me-1"></i>A Crédito
                            </button>
                            <a href="{{ route('sales.index') }}" class="btn btn-link text-muted w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

{{-- ─── NUEVO CLIENTE MODAL ──────────────────────────────────────── --}}
<div class="modal fade" id="newClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2 text-muted"></i>Nuevo cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="ncAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" id="nc_full_name" class="form-control" data-no-search>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Documento (CI/NIT)</label>
                        <input type="text" id="nc_id_number" class="form-control" data-no-search>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Teléfono</label>
                        <input type="text" id="nc_phone" class="form-control" data-no-search>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" id="nc_email" class="form-control" data-no-search>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dirección</label>
                        <input type="text" id="nc_address" class="form-control" data-no-search>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="nc_save" onclick="saveNewClient()">
                    <i class="bi bi-check-lg me-1"></i>Guardar cliente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── CREDIT SCHEDULE MODAL ────────────────────────────────────── --}}
<div class="modal fade" id="saleCreditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-calendar2-check me-2 text-muted"></i>Cronograma de cuotas — Venta a crédito
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                {{-- Plan de pago --}}
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Plan de pago</label>
                        <select id="sch_plan" class="form-select form-select-sm" onchange="onSchPlanChange(this)">
                            <option value="">— Plan personalizado —</option>
                            @foreach(($plans ?? collect()) as $p)
                            <option value="{{ $p->id }}"
                                    data-n="{{ $p->number_of_installments }}"
                                    data-freq="{{ $p->frequency_days }}"
                                    data-rate="{{ (float)$p->interest_rate }}">
                                {{ $p->name }}
                                @if($p->interest_rate > 0) ({{ number_format($p->interest_rate,2) }}% interés) @else (sin interés) @endif
                                — {{ $p->number_of_installments }} cuotas c/{{ $p->frequency_days }}d
                            </option>
                            @endforeach
                        </select>
                        @if(isset($fromApplication) && $fromApplication && $fromApplication->payment_plan_id)
                        <div class="form-text text-muted small">Plan preseleccionado desde la solicitud.</div>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">N° cuotas</label>
                        <input type="number" id="sch_cuotas" class="form-control" min="1" value="3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Frecuencia (días)</label>
                        <input type="number" id="sch_dias" class="form-control" min="1" value="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Primera fecha</label>
                        <input type="date" id="sch_fecha" class="form-control"
                               value="{{ now()->addDays(30)->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Pago inicial</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light px-2">$</span>
                            <input type="number" id="sch_down" name="down_payment"
                                   class="form-control" min="0" step="0.01"
                                   value="{{ old('down_payment', '0') }}"
                                   placeholder="0.00"
                                   oninput="updateSchBalance()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">% adicional (recargo)</label>
                        <div class="input-group">
                            <input type="number" id="sch_pct" class="form-control" min="0" step="0.01"
                                   value="0" placeholder="0.00"
                                   oninput="updateSchBalance()">
                            <span class="input-group-text bg-light px-2">%</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-light border btn-sm" onclick="generateSchedule()">
                        <i class="bi bi-lightning-charge me-1"></i>Generar
                    </button>
                    <button type="button" class="btn btn-light border btn-sm" onclick="addSchRow()">
                        <i class="bi bi-plus-lg me-1"></i>+ Cuota
                    </button>
                    <button type="button" class="btn btn-light border btn-sm" onclick="distributeEqual()">
                        <i class="bi bi-distribute-vertical me-1"></i>Dividir igual
                    </button>
                </div>

                <div id="schWrap">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" style="font-size:.83rem;" id="schTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2" style="width:40px;">#</th>
                                    <th class="py-2">Vencimiento</th>
                                    <th class="py-2">Monto</th>
                                    <th class="py-2" style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="schBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-2" id="schBalanceIndicator"></div>
                </div>

            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" onclick="confirmCreditSale()">
                    <i class="bi bi-check-lg me-1"></i>Confirmar venta a crédito
                </button>
            </div>
        </div>
    </div>
</div>

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

@php
    $productsJson = $products->map(fn($p) => [
        'id'    => $p->id,
        'name'  => $p->name,
        'sku'   => $p->sku,
        'price' => (float) $p->price,
    ])->values();
    $prefillJson = ($prefillItems ?? collect())->values();

    $plansCollection = $plans ?? collect();
    $plansData = [];
    foreach ($plansCollection as $p) {
        $plansData[] = [
            'id'   => $p->id,
            'name' => $p->name,
            'n'    => $p->number_of_installments,
            'freq' => $p->frequency_days,
            'rate' => (float) $p->interest_rate,
        ];
    }
    $fromApplicationPlanId = isset($fromApplication) && $fromApplication ? ($fromApplication->payment_plan_id ?? null) : null;
@endphp
@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const PREFILL = @json($prefillJson);
const PLANS = @json($plansData);
const FROM_APPLICATION_PLAN_ID = @json($fromApplicationPlanId);
let rowIndex = 0;
let schRowIndex = 0;
let currentRate = 0;

// ── PRODUCT ROWS ────────────────────────────────────────────────────
function buildProductOptions(selectedId) {
    let opts = '<option value="">— Seleccionar —</option>';
    PRODUCTS.forEach(p => {
        const sel = selectedId && String(p.id) === String(selectedId) ? 'selected' : '';
        opts += `<option value="${p.id}" data-price="${p.price}" ${sel}>${p.name} (${p.sku})</option>`;
    });
    return opts;
}

function addItemRow(productId, qty, unitPrice) {
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
                <span class="input-group-text bg-light px-2">$</span>
                <input type="number" name="items[${idx}][unit_price]" step="0.01" min="0"
                       class="form-control price-input text-end" required
                       value="${unitPrice || ''}" placeholder="0.00"
                       oninput="recalcRow(${idx})">
            </div>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">$</span>
                <input type="number" name="items[${idx}][discount]" step="0.01" min="0"
                       class="form-control line-disc-input text-end"
                       value="0" placeholder="0.00"
                       oninput="recalcRow(${idx})">
            </div>
        </td>
        <td class="text-end fw-semibold small subtotal-cell pe-2" data-index="${idx}">$0.00</td>
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
    if (cell) cell.textContent = '$' + sub.toFixed(2);
    recalcTotals();
}

function recalcTotals() {
    let sub = 0;
    document.querySelectorAll('.subtotal-cell').forEach(c => {
        sub += parseFloat(c.textContent.replace('$','')) || 0;
    });
    const disc  = parseFloat(document.getElementById('global_discount').value) || 0;
    const tax   = parseFloat(document.getElementById('tax').value) || 0;
    const total = Math.max(0, sub - disc + tax);
    const rows  = document.querySelectorAll('.item-row').length;
    const fmt   = v => '$' + v.toFixed(2);
    document.getElementById('displaySubtotal').textContent  = fmt(sub);
    document.getElementById('displayTotal').textContent     = fmt(total);
    document.getElementById('summaryItems').textContent     = rows;
    document.getElementById('summarySubtotal').textContent  = fmt(sub);
    document.getElementById('summaryDiscount').textContent  = fmt(disc);
    document.getElementById('summaryTax').textContent       = fmt(tax);
    document.getElementById('summaryTotal').textContent     = fmt(total);
    const emptyMsg = document.getElementById('emptyItemsMsg');
    emptyMsg.style.display = rows > 0 ? 'none' : 'block';
    updateSchBalance();
    return total;
}

function removeRow(btn) {
    btn.closest('tr').remove();
    recalcTotals();
}

// ── SALE TYPE TOGGLE (compat) ───────────────────────────────────────
function onSaleTypeChange() { /* tipo manejado por botones */ }

function lockSaleButtons(active) {
    ['btnContado', 'btnCredito'].forEach(function (id) {
        const b = document.getElementById(id);
        if (b) b.disabled = true;
    });
    if (active) active.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
}

// ── CONTADO: enviar directo ─────────────────────────────────────────
function submitContado() {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length === 0) { alert('Agrega al menos un producto.'); return; }
    document.getElementById('sale_type').value = 'cash';
    document.getElementById('interest_input').value = '0';
    lockSaleButtons(document.getElementById('btnContado'));
    document.getElementById('saleForm').requestSubmit();
}

// ── CONFIRM CREDIT SALE (desde el modal) ────────────────────────────
function confirmCreditSale() {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length === 0) { alert('Agrega al menos un producto.'); return; }
    const schRows = document.querySelectorAll('#schBody tr');
    if (schRows.length === 0) { alert('Genera al menos una cuota antes de confirmar.'); return; }

    document.getElementById('sale_type').value = 'credit';

    const total = recalcTotals();
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const pct   = parseFloat(document.getElementById('sch_pct')?.value) || 0;
    const rem   = Math.max(0, total - dp);
    const recargo = rem * (pct / 100);
    const interestAmt = currentRate > 0 ? rem * (currentRate / 100) : recargo;

    const interestInput = document.getElementById('interest_input');
    if (interestInput) interestInput.value = interestAmt.toFixed(2);

    bootstrap.Modal.getInstance(document.getElementById('saleCreditModal'))?.hide();
    lockSaleButtons(document.getElementById('btnCredito'));
    document.getElementById('saleForm').requestSubmit();
}

// ── PLAN SELECTOR ───────────────────────────────────────────────────
function onSchPlanChange(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const n    = opt.dataset.n;
    const freq = opt.dataset.freq;
    const rate = parseFloat(opt.dataset.rate) || 0;
    const planId = sel.value;

    currentRate = rate;
    document.getElementById('plan_id').value = planId || '';

    if (n && freq) {
        document.getElementById('sch_cuotas').value = n;
        document.getElementById('sch_dias').value   = freq;
    }

    // Sync sch_pct with plan rate (plan rate is the single source of recargo when plan selected)
    const pctInput = document.getElementById('sch_pct');
    if (pctInput) pctInput.value = rate > 0 ? rate.toFixed(2) : '0';

    updateSchBalance();
}

function applyPlanInterest() {
    const total = recalcTotals();
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const rem   = Math.max(0, total - dp);
    const interestAmt = rem * (currentRate / 100);
    const interestInput = document.getElementById('interest_input');
    if (interestInput) interestInput.value = interestAmt.toFixed(2);
}

// ── SCHEDULE ────────────────────────────────────────────────────────
function generateSchedule() {
    const n    = parseInt(document.getElementById('sch_cuotas').value) || 3;
    const days = parseInt(document.getElementById('sch_dias').value) || 30;
    const fd   = document.getElementById('sch_fecha').value;
    if (!fd) { alert('Ingresa la primera fecha de vencimiento.'); return; }

    const total = recalcTotals();
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const pct   = parseFloat(document.getElementById('sch_pct')?.value) || 0;
    const effectiveRate = currentRate > 0 ? currentRate : pct;
    const rem   = Math.max(0, total - dp);
    const remWithInterest = rem * (1 + effectiveRate / 100);
    const interestAmt = rem * (effectiveRate / 100);
    const interestInput = document.getElementById('interest_input');
    if (interestInput) interestInput.value = interestAmt.toFixed(2);

    const base = Math.floor((remWithInterest / n) * 100) / 100;
    const last = (remWithInterest - base * (n - 1)).toFixed(2);

    const body = document.getElementById('schBody');
    body.innerHTML = '';
    schRowIndex = 0;

    let d = new Date(fd + 'T00:00:00');
    for (let i = 0; i < n; i++) {
        const amt = (i === n - 1) ? last : base.toFixed(2);
        addSchRow(d.toISOString().slice(0,10), amt);
        d.setDate(d.getDate() + days);
    }
    updateSchBalance();
}

function addSchRow(dateVal, amtVal) {
    const i = schRowIndex++;
    const tr = document.createElement('tr');
    tr.dataset.schrow = i;
    tr.innerHTML = `
        <td class="py-1">${i+1}</td>
        <td class="py-1">
            <input type="date" name="installments[${i}][due_date]"
                   class="form-control form-control-sm sch-date"
                   value="${dateVal || ''}" oninput="updateSchBalance()">
        </td>
        <td class="py-1">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">$</span>
                <input type="number" name="installments[${i}][amount]"
                       class="form-control sch-amount text-end"
                       step="0.01" min="0" value="${amtVal || ''}"
                       oninput="updateSchBalance()">
            </div>
        </td>
        <td class="py-1">
            <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                    onclick="this.closest('tr').remove(); renumberSchRows(); updateSchBalance();">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    document.getElementById('schBody').appendChild(tr);
    updateSchBalance();
}

function renumberSchRows() {
    document.querySelectorAll('#schBody tr').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

function distributeEqual() {
    const rows = document.querySelectorAll('#schBody tr');
    if (rows.length === 0) return;
    const total = recalcTotals();
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const pct   = parseFloat(document.getElementById('sch_pct')?.value) || 0;
    const effectiveRate = currentRate > 0 ? currentRate : pct;
    const rem   = Math.max(0, total - dp);
    const remWithInterest = rem * (1 + effectiveRate / 100);
    const base  = Math.floor((remWithInterest / rows.length) * 100) / 100;
    const last  = (remWithInterest - base * (rows.length - 1)).toFixed(2);
    rows.forEach((tr, i) => {
        const el = tr.querySelector('.sch-amount');
        if (el) el.value = (i === rows.length - 1) ? last : base.toFixed(2);
    });
    updateSchBalance();
}

function computeTotal() {
    let sub = 0;
    document.querySelectorAll('.subtotal-cell').forEach(c => {
        sub += parseFloat(c.textContent.replace('$','')) || 0;
    });
    const disc = parseFloat(document.getElementById('global_discount').value) || 0;
    const tax  = parseFloat(document.getElementById('tax').value) || 0;
    return Math.max(0, sub - disc + tax);
}

function updateSchBalance() {
    const total = computeTotal();
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const pct   = parseFloat(document.getElementById('sch_pct')?.value) || 0;
    const effectiveRate = currentRate > 0 ? currentRate : pct;
    const rem   = Math.max(0, total - dp);
    const remWithInterest = rem * (1 + effectiveRate / 100);
    const interestAmt = rem * (effectiveRate / 100);
    const interestInput = document.getElementById('interest_input');
    if (interestInput) interestInput.value = interestAmt.toFixed(2);

    const instSum = Array.from(document.querySelectorAll('.sch-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const diff = (instSum - remWithInterest).toFixed(2);
    const ind  = document.getElementById('schBalanceIndicator');
    if (!ind) return;
    const ok   = Math.abs(parseFloat(diff)) < 0.02;

    let recargoLine = effectiveRate > 0
        ? `<div class="text-muted small mb-1">Recargo ${effectiveRate.toFixed(2)}%: <strong>$${interestAmt.toFixed(2)}</strong> &middot; Total con recargo: <strong>$${(total + interestAmt).toFixed(2)}</strong></div>`
        : '';

    let interestBadge = effectiveRate > 0
        ? `<span class="badge bg-info-subtle text-info border border-info-subtle ms-2">
               <i class="bi bi-percent me-1"></i>Recargo: $${interestAmt.toFixed(2)}
           </span>`
        : '';

    ind.innerHTML = recargoLine + (ok
        ? `<span class="badge bg-success-subtle text-success border border-success-subtle">
               <i class="bi bi-check-circle me-1"></i>Cuotas cuadran: $${instSum.toFixed(2)}
           </span>${interestBadge}`
        : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">
               <i class="bi bi-exclamation-triangle me-1"></i>Suma cuotas: $${instSum.toFixed(2)} — Requerido: $${remWithInterest.toFixed(2)} (diff: $${diff})
           </span>${interestBadge}`);
}

// ── INIT ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    if (PREFILL.length) {
        PREFILL.forEach(function(it) {
            addItemRow(it.product_id, it.quantity, it.unit_price);
        });
        const rows = document.querySelectorAll('.item-row');
        PREFILL.forEach(function(it, i) {
            const row = rows[i];
            if (row) {
                const discInput = row.querySelector('.line-disc-input');
                if (discInput && it.discount !== undefined) {
                    discInput.value = it.discount;
                    recalcRow(parseInt(row.dataset.index));
                }
            }
        });
    } else {
        addItemRow();
    }
    recalcTotals();
    onSaleTypeChange();
    document.getElementById('sale_type').addEventListener('change', onSaleTypeChange);

    document.getElementById('sch_down')?.addEventListener('input', function() {
        updateSchBalance();
        applyPlanInterest();
    });

    document.getElementById('sch_pct')?.addEventListener('input', function() {
        if (currentRate === 0) updateSchBalance();
    });

    // Open modal → refresh balance
    document.getElementById('saleCreditModal')?.addEventListener('show.bs.modal', function() {
        updateSchBalance();
    });

    // Preselect plan from application if set
    if (FROM_APPLICATION_PLAN_ID) {
        const planSel = document.getElementById('sch_plan');
        if (planSel) {
            for (let i = 0; i < planSel.options.length; i++) {
                if (String(planSel.options[i].value) === String(FROM_APPLICATION_PLAN_ID)) {
                    planSel.selectedIndex = i;
                    onSchPlanChange(planSel);
                    break;
                }
            }
        }
    }
});

// ── NUEVO CLIENTE (alta rápida AJAX) ────────────────────────────────
function saveNewClient() {
    const btn   = document.getElementById('nc_save');
    const alertBox = document.getElementById('ncAlert');
    const name  = document.getElementById('nc_full_name').value.trim();
    alertBox.classList.add('d-none');

    if (!name) {
        alertBox.textContent = 'El nombre es obligatorio.';
        alertBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    const payload = {
        full_name: name,
        id_number: document.getElementById('nc_id_number').value.trim(),
        phone:     document.getElementById('nc_phone').value.trim(),
        email:     document.getElementById('nc_email').value.trim(),
        address:   document.getElementById('nc_address').value.trim(),
    };

    fetch('{{ route('clients.quick-store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        },
        body: JSON.stringify(payload),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.ok) {
            alertBox.textContent = d.message || 'No se pudo guardar el cliente.';
            alertBox.classList.remove('d-none');
            return;
        }
        // Agregar al select y seleccionarlo
        const sel = document.getElementById('client_id');
        const opt = new Option(d.client.full_name, d.client.id, true, true);
        sel.appendChild(opt);
        if (window.jQuery && jQuery.fn.select2) jQuery(sel).trigger('change');
        // Limpiar y cerrar
        ['nc_full_name','nc_id_number','nc_phone','nc_email','nc_address'].forEach(id => document.getElementById(id).value = '');
        bootstrap.Modal.getInstance(document.getElementById('newClientModal'))?.hide();
    })
    .catch(() => {
        alertBox.textContent = 'Error de conexión.';
        alertBox.classList.remove('d-none');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar cliente';
    });
}
</script>
@endpush

@endsection
