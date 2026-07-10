@extends('layouts.app')
@section('title', 'Entregar OT: ' . $order->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-right me-2 text-danger"></i>Entregar vehículo</h1>
            <p class="text-muted mb-0 small">OT {{ $order->code }} &mdash; {{ $order->client?->full_name ?? '—' }} &mdash; {{ $order->vehicle?->display_name ?? '—' }}</p>
        </div>
        <a href="{{ route('workshop.orders.show', $order) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver a la OT
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

    @if(!$session)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle flex-shrink-0 fs-5"></i>
        <span>No tienes caja abierta. Solo podrás entregar a crédito sin pago inicial o registrar el cobro después.</span>
    </div>
    @endif

    @php
        $svcSubtotal = $order->services->sum(fn($s) => ($s->pivot->price ?? $s->price) * ($s->pivot->quantity ?? 1));
        $partsSubtotal = $order->parts->sum(fn($p) => $p->unit_price * $p->quantity);
        $baseTotal = $svcSubtotal + $partsSubtotal;
    @endphp

    <form action="{{ route('workshop.deliveries.store', $order) }}" method="POST" id="deliveryForm">
        @csrf

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Resumen OT --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Resumen de la OT</h6>
                    </div>
                    <div class="card-body p-0">
                        {{-- Services --}}
                        @if($order->services->isNotEmpty())
                        <div class="px-4 pt-3 pb-2">
                            <p class="small fw-semibold text-muted mb-2 text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Servicios</p>
                            @foreach($order->services as $svc)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $svc->pivot->description ?? $svc->name }}</span>
                                <span class="fw-semibold">
                                    ${{ number_format(($svc->pivot->price ?? $svc->price) * ($svc->pivot->quantity ?? 1), 2) }}
                                </span>
                            </div>
                            @endforeach
                            <div class="d-flex justify-content-between small border-top pt-2 mt-2 text-muted">
                                <span>Subtotal servicios</span>
                                <span id="displaySvcSubtotal">${{ number_format($svcSubtotal, 2) }}</span>
                            </div>
                        </div>
                        @endif

                        {{-- Parts --}}
                        @if($order->parts->isNotEmpty())
                        <div class="px-4 pt-2 pb-3 border-top border-light mt-2">
                            <p class="small fw-semibold text-muted mb-2 text-uppercase" style="font-size:.7rem;letter-spacing:.05em;">Repuestos</p>
                            @foreach($order->parts as $part)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $part->product?->name ?? '—' }} &times; {{ $part->quantity }}</span>
                                <span class="fw-semibold">${{ number_format($part->unit_price * $part->quantity, 2) }}</span>
                            </div>
                            @endforeach
                            <div class="d-flex justify-content-between small border-top pt-2 mt-2 text-muted">
                                <span>Subtotal repuestos</span>
                                <span id="displayPartsSubtotal">${{ number_format($partsSubtotal, 2) }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-top px-4 py-3">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-2 small">
                                    <label class="text-muted mb-0" for="del_discount">Descuento</label>
                                    <div class="input-group input-group-sm" style="width:130px">
                                        <span class="input-group-text bg-light">$</span>
                                        <input type="number" id="del_discount" name="discount"
                                               step="0.01" min="0"
                                               class="form-control text-end"
                                               value="{{ old('discount', '0.00') }}"
                                               oninput="recalcDeliveryTotal()">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 small">
                                    <label class="text-muted mb-0" for="del_tax">Impuesto</label>
                                    <div class="input-group input-group-sm" style="width:130px">
                                        <span class="input-group-text bg-light">$</span>
                                        <input type="number" id="del_tax" name="tax"
                                               step="0.01" min="0"
                                               class="form-control text-end"
                                               value="{{ old('tax', '0.00') }}"
                                               oninput="recalcDeliveryTotal()">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total</span>
                                    <span id="displayDeliveryTotal">${{ number_format($baseTotal, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datos de entrega --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2-check me-2 text-muted"></i>Datos de entrega</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="payment_type">
                                    Tipo de pago <span class="text-danger">*</span>
                                </label>
                                <select id="payment_type" name="payment_type"
                                        class="form-select @error('payment_type') is-invalid @enderror"
                                        required onchange="onPaymentTypeChange()">
                                    <option value="contado" {{ old('payment_type', 'contado') === 'contado' ? 'selected' : '' }}>Contado</option>
                                    <option value="credito" {{ old('payment_type') === 'credito' ? 'selected' : '' }}>Crédito</option>
                                </select>
                                @error('payment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="method">Método de pago</label>
                                <select id="method" name="method"
                                        class="form-select @error('method') is-invalid @enderror">
                                    <option value="efectivo" {{ old('method', 'efectivo') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="transferencia" {{ old('method') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                                    <option value="tarjeta" {{ old('method') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                                </select>
                                @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="delivered_to">Recibido por</label>
                                <input type="text" id="delivered_to" name="delivered_to"
                                       class="form-control @error('delivered_to') is-invalid @enderror"
                                       value="{{ old('delivered_to') }}"
                                       placeholder="Nombre de quien recibe el vehículo">
                                @error('delivered_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="delivery_notes">Notas de entrega</label>
                                <textarea id="delivery_notes" name="delivery_notes" rows="2"
                                          class="form-control @error('delivery_notes') is-invalid @enderror"
                                          placeholder="Observaciones de la entrega...">{{ old('delivery_notes') }}</textarea>
                                @error('delivery_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Cronograma de crédito --}}
                <div class="card border-0 shadow-sm" id="creditScheduleCard" style="display:none!important;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-check me-2 text-muted"></i>Cronograma de cuotas</h6>
                    </div>
                    <div class="card-body p-4">
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

            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Confirmar entrega</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Subtotal servicios</span>
                            <span class="fw-semibold" id="sumSvc">${{ number_format($svcSubtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Subtotal repuestos</span>
                            <span class="fw-semibold" id="sumParts">${{ number_format($partsSubtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Descuento</span>
                            <span class="fw-semibold" id="sumDiscount">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small">
                            <span class="text-muted">Impuesto</span>
                            <span class="fw-semibold" id="sumTax">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3">
                            <span>Total</span>
                            <span id="sumTotal" class="fs-5">${{ number_format($baseTotal, 2) }}</span>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-right me-1"></i>Confirmar entrega
                            </button>
                            <a href="{{ route('workshop.orders.show', $order) }}" class="btn btn-light border w-100">
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
#creditScheduleCard[style*="none"] { display: none !important; }
</style>
@endpush

@php
    $svcSubtotalVal   = $order->services->sum(fn($s) => ($s->pivot->price ?? $s->price) * ($s->pivot->quantity ?? 1));
    $partsSubtotalVal = $order->parts->sum(fn($p) => $p->unit_price * $p->quantity);
    $baseTotalVal     = $svcSubtotalVal + $partsSubtotalVal;
@endphp

@push('scripts')
<script>
const BASE_SVC_SUBTOTAL   = {{ (float) $svcSubtotalVal }};
const BASE_PARTS_SUBTOTAL = {{ (float) $partsSubtotalVal }};
let schRowIndex = 0;

function recalcDeliveryTotal() {
    const disc  = parseFloat(document.getElementById('del_discount').value) || 0;
    const tax   = parseFloat(document.getElementById('del_tax').value) || 0;
    const total = Math.max(0, BASE_SVC_SUBTOTAL + BASE_PARTS_SUBTOTAL - disc + tax);
    const fmt   = v => '$' + v.toFixed(2);
    document.getElementById('displayDeliveryTotal').textContent = fmt(total);
    document.getElementById('sumDiscount').textContent = fmt(disc);
    document.getElementById('sumTax').textContent      = fmt(tax);
    document.getElementById('sumTotal').textContent    = fmt(total);
    updateSchBalance();
    return total;
}

function onPaymentTypeChange() {
    const isCredit = document.getElementById('payment_type').value === 'credito';
    const card = document.getElementById('creditScheduleCard');
    card.style.cssText = isCredit ? '' : 'display:none!important;';
}

// ── SCHEDULE ──────────────────────────────────────────────────────────
function generateSchedule() {
    const n    = parseInt(document.getElementById('sch_cuotas').value) || 3;
    const days = parseInt(document.getElementById('sch_dias').value) || 30;
    const fd   = document.getElementById('sch_fecha').value;
    if (!fd) { alert('Ingresa la primera fecha de vencimiento.'); return; }

    const total = recalcDeliveryTotal();
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const rem   = Math.max(0, total - dp);
    const each  = (rem / n).toFixed(2);

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
    const total = recalcDeliveryTotal();
    const dp    = parseFloat(document.getElementById('sch_down').value) || 0;
    const rem   = Math.max(0, total - dp);
    const each  = (rem / rows.length).toFixed(2);
    rows.forEach(tr => { const el = tr.querySelector('.sch-amount'); if (el) el.value = each; });
    updateSchBalance();
}

function updateSchBalance() {
    const total = recalcDeliveryTotal();
    const dp    = parseFloat(document.getElementById('sch_down')?.value) || 0;
    const rem   = Math.max(0, total - dp);
    const instSum = Array.from(document.querySelectorAll('.sch-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const diff = (instSum - rem).toFixed(2);
    const ind  = document.getElementById('schBalanceIndicator');
    if (!ind) return;
    const ok = Math.abs(parseFloat(diff)) < 0.02;
    ind.innerHTML = ok
        ? `<span class="badge bg-success-subtle text-success border border-success-subtle">
               <i class="bi bi-check-circle me-1"></i>Cuotas cuadran: $${instSum.toFixed(2)}
           </span>`
        : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">
               <i class="bi bi-exclamation-triangle me-1"></i>Suma cuotas: $${instSum.toFixed(2)} — Requerido: $${rem.toFixed(2)} (diff: $${diff})
           </span>`;
}

document.addEventListener('DOMContentLoaded', function() {
    recalcDeliveryTotal();
    onPaymentTypeChange();
    document.getElementById('payment_type').addEventListener('change', onPaymentTypeChange);
    document.getElementById('sch_down')?.addEventListener('input', updateSchBalance);
});
</script>
@endpush

@endsection
