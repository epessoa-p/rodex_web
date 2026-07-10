@extends('layouts.app')
@section('title', 'Nueva Venta de Moto')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cart-check me-2 text-danger"></i>Nueva Venta de Moto</h1>
            <p class="text-muted mb-0 small">Registra la venta de una unidad — contado o crédito.</p>
        </div>
        <a href="{{ route('moto-sales.index') }}" class="btn btn-light border">
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

    <form action="{{ route('moto-sales.store') }}" method="POST" id="motoSaleForm">
        @csrf
        <input type="hidden" name="payment_plan_id" id="plan_id_input" value="{{ old('payment_plan_id') }}">
        <input type="hidden" name="interest" id="interest_input" value="{{ old('interest', '0') }}">

        <div class="row g-4">

            {{-- ── MAIN (left) ──────────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Card: Datos principales --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la venta</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="moto_unit_id">
                                    Unidad / Moto <span class="text-danger">*</span>
                                </label>
                                <select id="moto_unit_id" name="moto_unit_id"
                                        class="form-select @error('moto_unit_id') is-invalid @enderror"
                                        required onchange="onUnitChange(this)">
                                    <option value="">— Seleccionar moto disponible —</option>
                                    @foreach($units as $u)
                                    <option value="{{ $u->id }}"
                                            data-price="{{ (float)$u->price }}"
                                            data-model="{{ $u->model?->display_name ?? '' }}"
                                            data-chassis="{{ $u->chassis_number }}"
                                            data-color="{{ $u->color }}"
                                            {{ old('moto_unit_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->display_name }} — {{ $u->chassis_number }}
                                        @if($u->color) ({{ $u->color }})@endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('moto_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Mini detalle de la moto seleccionada --}}
                            <div class="col-12" id="unitDetailBox" style="display:none;">
                                <div class="rounded-2 border bg-light p-3 d-flex flex-wrap gap-3 small text-muted">
                                    <span><i class="bi bi-bicycle me-1"></i><span id="detModel">—</span></span>
                                    <span><i class="bi bi-upc me-1"></i><span id="detChassis">—</span></span>
                                    <span><i class="bi bi-palette me-1"></i><span id="detColor">—</span></span>
                                    <span class="fw-semibold text-dark"><i class="bi bi-currency-dollar me-1"></i><span id="detPrice">—</span></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="client_id">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select id="client_id" name="client_id"
                                        class="form-select @error('client_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="sale_type">
                                    Tipo de venta <span class="text-danger">*</span>
                                </label>
                                <select id="sale_type" name="sale_type"
                                        class="form-select @error('sale_type') is-invalid @enderror"
                                        required onchange="onSaleTypeChange()">
                                    <option value="cash" {{ old('sale_type', 'cash') === 'cash' ? 'selected' : '' }}>Contado</option>
                                    <option value="credit" {{ old('sale_type') === 'credit' ? 'selected' : '' }}>Crédito</option>
                                </select>
                                @error('sale_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="price">
                                    Precio de venta <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" id="price" name="price"
                                           class="form-control @error('price') is-invalid @enderror"
                                           value="{{ old('price', '0.00') }}"
                                           step="0.01" min="0" required
                                           oninput="updateSummary()">
                                </div>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                {{-- Card: Cronograma de cuotas (visible solo en crédito) --}}
                <div class="card border-0 shadow-sm" id="creditCard" style="display:none!important;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-check me-2 text-muted"></i>Cronograma de cuotas</h6>
                    </div>
                    <div class="card-body p-4">

                        {{-- Plan de pago --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Plan de pago</label>
                                <select id="sch_plan" class="form-select form-select-sm" onchange="onSchPlanChange(this)">
                                    <option value="">— Plan personalizado —</option>
                                    @foreach($plans as $p)
                                    <option value="{{ $p->id }}"
                                            data-n="{{ $p->number_of_installments }}"
                                            data-freq="{{ $p->frequency_days }}"
                                            data-rate="{{ (float)$p->interest_rate }}">
                                        {{ $p->name }}
                                        @if($p->interest_rate > 0) ({{ number_format($p->interest_rate, 2) }}% interés) @else (sin interés) @endif
                                        — {{ $p->number_of_installments }} cuotas c/{{ $p->frequency_days }}d
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">N° cuotas</label>
                                <input type="number" id="sch_cuotas" class="form-control form-control-sm" min="1" value="3">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Frecuencia (días)</label>
                                <input type="number" id="sch_dias" class="form-control form-control-sm" min="1" value="30">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Primera fecha</label>
                                <input type="date" id="sch_fecha" class="form-control form-control-sm"
                                       value="{{ now()->addDays(30)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Pago inicial</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light px-2">$</span>
                                    <input type="number" id="sch_down" name="down_payment"
                                           class="form-control" min="0" step="0.01"
                                           value="{{ old('down_payment', '0') }}"
                                           placeholder="0.00"
                                           oninput="updateSchBalance()">
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
                                <table class="table table-sm align-middle mb-0" style="font-size:.83rem;" id="schTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-2" style="width:36px;">#</th>
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
                            <span class="text-muted">Precio moto</span>
                            <span class="fw-semibold" id="summaryPrice">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small">
                            <span class="text-muted">Interés</span>
                            <span class="fw-semibold text-info" id="summaryInterest">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3">
                            <span>Total</span>
                            <span id="summaryTotal" class="fs-5">$0.00</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100" id="btnMotoSubmit">
                                <i class="bi bi-check-lg me-1"></i>Registrar venta de moto
                            </button>
                            <a href="{{ route('moto-sales.index') }}" class="btn btn-light border w-100">
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
#creditCard[style*="none"] { display: none !important; }
</style>
@endpush

@php
    $plansData = [];
    foreach ($plans as $p) {
        $plansData[] = [
            'id'   => $p->id,
            'name' => $p->name,
            'n'    => $p->number_of_installments,
            'freq' => $p->frequency_days,
            'rate' => (float) $p->interest_rate,
        ];
    }
@endphp

@push('scripts')
<script>
const PLANS = @json($plansData);
let schRowIndex = 0;
let currentRate = 0;

// ── UNIT SELECTOR ────────────────────────────────────────────────────
function onUnitChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.dataset.price) || 0;
    const model = opt.dataset.model || '';
    const chassis = opt.dataset.chassis || '';
    const color = opt.dataset.color || '';
    const box = document.getElementById('unitDetailBox');

    if (sel.value) {
        document.getElementById('detModel').textContent = model || '—';
        document.getElementById('detChassis').textContent = chassis || '—';
        document.getElementById('detColor').textContent = color || '—';
        document.getElementById('detPrice').textContent = '$' + price.toFixed(2);
        box.style.display = 'block';
        const priceInput = document.getElementById('price');
        priceInput.value = price.toFixed(2);
    } else {
        box.style.display = 'none';
    }
    updateSummary();
}

// ── SALE TYPE TOGGLE ─────────────────────────────────────────────────
function onSaleTypeChange() {
    const isCredit = document.getElementById('sale_type').value === 'credit';
    const card = document.getElementById('creditCard');
    card.style.cssText = isCredit ? '' : 'display:none!important;';
    if (!isCredit) {
        document.getElementById('interest_input').value = '0';
        document.getElementById('summaryInterest').textContent = '$0.00';
        updateSummary();
    }
}

// ── SUMMARY ──────────────────────────────────────────────────────────
function updateSummary() {
    const price    = parseFloat(document.getElementById('price').value) || 0;
    const interest = parseFloat(document.getElementById('interest_input').value) || 0;
    const total    = price + interest;
    document.getElementById('summaryPrice').textContent    = '$' + price.toFixed(2);
    document.getElementById('summaryInterest').textContent = '$' + interest.toFixed(2);
    document.getElementById('summaryTotal').textContent    = '$' + total.toFixed(2);
}

// ── PLAN SELECTOR ────────────────────────────────────────────────────
function onSchPlanChange(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const n    = opt.dataset.n;
    const freq = opt.dataset.freq;
    const rate = parseFloat(opt.dataset.rate) || 0;

    currentRate = rate;
    document.getElementById('plan_id_input').value = sel.value || '';

    if (n && freq) {
        document.getElementById('sch_cuotas').value = n;
        document.getElementById('sch_dias').value   = freq;
    }
    applyPlanInterest();
}

function applyPlanInterest() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const rem   = Math.max(0, price - dp);
    const interestAmt = rem * (currentRate / 100);
    document.getElementById('interest_input').value = interestAmt.toFixed(2);
    updateSummary();
}

// ── SCHEDULE ─────────────────────────────────────────────────────────
function generateSchedule() {
    const n    = parseInt(document.getElementById('sch_cuotas').value) || 3;
    const days = parseInt(document.getElementById('sch_dias').value) || 30;
    const fd   = document.getElementById('sch_fecha').value;
    if (!fd) { alert('Ingresa la primera fecha de vencimiento.'); return; }

    const price = parseFloat(document.getElementById('price').value) || 0;
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const rem   = Math.max(0, price - dp);
    const remWithInterest = rem * (1 + currentRate / 100);
    const interestAmt = rem * (currentRate / 100);
    document.getElementById('interest_input').value = interestAmt.toFixed(2);
    updateSummary();

    const each = (remWithInterest / n).toFixed(2);
    const body = document.getElementById('schBody');
    body.innerHTML = '';
    schRowIndex = 0;

    let d = new Date(fd + 'T00:00:00');
    for (let i = 0; i < n; i++) {
        addSchRow(d.toISOString().slice(0, 10), each);
        d.setDate(d.getDate() + days);
    }
    updateSchBalance();
}

function addSchRow(dateVal, amtVal) {
    const i = schRowIndex++;
    const tr = document.createElement('tr');
    tr.dataset.schrow = i;
    tr.innerHTML = `
        <td class="py-1">${i + 1}</td>
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
    const price = parseFloat(document.getElementById('price').value) || 0;
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const rem   = Math.max(0, price - dp);
    const remWithInterest = rem * (1 + currentRate / 100);
    const each  = (remWithInterest / rows.length).toFixed(2);
    rows.forEach(tr => {
        const el = tr.querySelector('.sch-amount');
        if (el) el.value = each;
    });
    updateSchBalance();
}

function updateSchBalance() {
    const price = parseFloat(document.getElementById('price').value) || 0;
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const rem   = Math.max(0, price - dp);
    const remWithInterest = rem * (1 + currentRate / 100);
    const interestAmt = rem * (currentRate / 100);
    document.getElementById('interest_input').value = interestAmt.toFixed(2);
    updateSummary();

    const instSum = Array.from(document.querySelectorAll('.sch-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const diff = (instSum - remWithInterest).toFixed(2);
    const ind  = document.getElementById('schBalanceIndicator');
    if (!ind) return;
    const ok = Math.abs(parseFloat(diff)) < 0.02;

    let interestBadge = currentRate > 0
        ? `<span class="badge bg-info-subtle text-info border border-info-subtle ms-2">
               <i class="bi bi-percent me-1"></i>Interés: $${interestAmt.toFixed(2)}
           </span>`
        : '';

    ind.innerHTML = ok
        ? `<span class="badge bg-success-subtle text-success border border-success-subtle">
               <i class="bi bi-check-circle me-1"></i>Cuotas cuadran: $${instSum.toFixed(2)}
           </span>${interestBadge}`
        : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">
               <i class="bi bi-exclamation-triangle me-1"></i>Suma cuotas: $${instSum.toFixed(2)} — Requerido: $${remWithInterest.toFixed(2)} (diff: $${diff})
           </span>${interestBadge}`;
}

// ── INIT ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    onSaleTypeChange();
    updateSummary();

    document.getElementById('sale_type').addEventListener('change', onSaleTypeChange);
    document.getElementById('price').addEventListener('input', function () {
        updateSummary();
        updateSchBalance();
    });
    document.getElementById('sch_down')?.addEventListener('input', function () {
        updateSchBalance();
        applyPlanInterest();
    });

    // Restore old unit selection on validation error
    const unitSel = document.getElementById('moto_unit_id');
    if (unitSel && unitSel.value) {
        onUnitChange(unitSel);
    }

    // Spinner + bloqueo al enviar
    document.getElementById('motoSaleForm').addEventListener('submit', function () {
        const b = document.getElementById('btnMotoSubmit');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
    });
});
</script>
@endpush

@endsection
