@extends('layouts.app')
@section('title', 'Nueva Reserva de Alquiler')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bookmark-plus me-2 text-danger"></i>Nueva Reserva</h1>
            <p class="text-muted mb-0 small">Renta periódica (diaria/semanal/mensual) o pago único.</p>
        </div>
        <a href="{{ route('rentals.reservations') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @include('rentals.partials.flash')

    <form action="{{ route('rentals.store') }}" method="POST" id="rentalForm">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                {{-- Datos principales --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la reserva</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="moto_unit_id">Moto <span class="text-danger">*</span></label>
                                <select id="moto_unit_id" name="moto_unit_id" class="form-select @error('moto_unit_id') is-invalid @enderror" required onchange="onUnitChange(this)">
                                    <option value="">— Seleccionar moto disponible —</option>
                                    @foreach($units as $u)
                                    <option value="{{ $u->id }}"
                                            data-rate="{{ (float) ($u->model?->daily_rate ?? 0) }}"
                                            {{ old('moto_unit_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->display_name }} @if($u->color)({{ $u->color }})@endif
                                    </option>
                                    @endforeach
                                </select>
                                @error('moto_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold" for="client_id">Cliente <span class="text-danger">*</span></label>
                                <select id="client_id" name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('clients.create', auth()->user()->getCurrentCompany()))
                                <button type="button" class="btn btn-light border w-100" data-bs-toggle="modal" data-bs-target="#quickClientModal"><i class="bi bi-person-plus me-1"></i>Nuevo cliente</button>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="start_date">Fecha inicio <span class="text-danger">*</span></label>
                                <input type="date" id="start_date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', now()->format('Y-m-d')) }}" required oninput="onRangeChange()">
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="end_date">Fecha fin <span class="text-danger">*</span></label>
                                <input type="date" id="end_date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', now()->addDays(6)->format('Y-m-d')) }}" required oninput="onRangeChange()">
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="deposit">Depósito de garantía</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Bs.</span>
                                    <input type="number" id="deposit" name="deposit" class="form-control @error('deposit') is-invalid @enderror" value="{{ old('deposit', '0.00') }}" step="0.01" min="0" oninput="updateSummary()">
                                </div>
                                @error('deposit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas</label>
                                <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="Observaciones opcionales...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modalidad de cobro --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-stack me-2 text-muted"></i>Modalidad de cobro</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-2 mb-3">
                            @foreach(\App\Models\Rentals\RentalContract::PAYMENT_MODES as $key => $lbl)
                            <div class="col-md-6">
                                <input type="radio" class="btn-check" name="payment_mode" id="mode_{{ $key }}" value="{{ $key }}"
                                       {{ old('payment_mode', 'renta') === $key ? 'checked' : '' }} onchange="onModeChange()">
                                <label class="btn btn-outline-dark w-100 text-start py-3" for="mode_{{ $key }}">
                                    <i class="bi bi-{{ $key === 'renta' ? 'calendar2-week' : 'cash-coin' }} me-2"></i>{{ $lbl }}
                                    <div class="small text-muted mt-1">{{ $key === 'renta' ? 'Cobros diarios/semanales/mensuales' : 'Cobro completo en la entrega' }}</div>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        {{-- Bloque RENTA --}}
                        <div id="rentaBlock">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="billing_period">Frecuencia</label>
                                    <select id="billing_period" name="billing_period" class="form-select" onchange="generateSchedule()">
                                        @foreach(\App\Models\Rentals\RentalContract::BILLING_PERIODS as $key => $lbl)
                                        <option value="{{ $key }}" {{ old('billing_period', 'diario') === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="period_amount">Monto por período</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Bs.</span>
                                        <input type="number" id="period_amount" name="period_amount" class="form-control" value="{{ old('period_amount', '0.00') }}" step="0.01" min="0" oninput="generateSchedule()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="late_fee_per_day">Mora por día (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Bs.</span>
                                        <input type="number" id="late_fee_per_day" name="late_fee_per_day" class="form-control" value="{{ old('late_fee_per_day', '0.00') }}" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                                <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">Calendario de cuotas</span>
                                <button type="button" class="btn btn-sm btn-light border" onclick="generateSchedule()"><i class="bi bi-arrow-repeat me-1"></i>Regenerar</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" style="font-size:.83rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-2" style="width:36px;">#</th>
                                            <th class="py-2">Período</th>
                                            <th class="py-2" style="width:150px;">Vence</th>
                                            <th class="py-2" style="width:150px;">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody id="schBody"></tbody>
                                </table>
                            </div>
                            <div class="text-muted small mt-2" id="schEmpty">Define monto por período y rango de fechas para generar las cuotas.</div>
                        </div>

                        {{-- Bloque ÚNICO --}}
                        <div id="unicoBlock" style="display:none;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="daily_rate">Tarifa por día <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Bs.</span>
                                        <input type="number" id="daily_rate" name="daily_rate" class="form-control" value="{{ old('daily_rate', '0.00') }}" step="0.01" min="0" oninput="updateSummary()">
                                    </div>
                                    <div class="form-text">El alquiler completo se cobra en la entrega.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Resumen --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-muted"></i>Resumen</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted" id="lblQty">Días</span><span class="fw-semibold" id="sumQty">0</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Total renta/alquiler</span><span class="fw-semibold" id="sumRental">Bs. 0.00</span></div>
                        <div class="d-flex justify-content-between mb-3 small"><span class="text-muted">Depósito</span><span class="fw-semibold text-info" id="sumDeposit">Bs. 0.00</span></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3"><span id="lblGrand">A cobrar en entrega</span><span id="sumGrand" class="fs-5">Bs. 0.00</span></div>
                        <div class="form-text mt-2" id="grandHint"></div>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <button type="submit" class="btn btn-primary w-100" id="btnSubmit"><i class="bi bi-check-lg me-1"></i>Crear reserva</button>
                        <a href="{{ route('rentals.reservations') }}" class="btn btn-light border w-100 mt-2"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal alta rápida de cliente --}}
<div class="modal fade" id="quickClientModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold"><i class="bi bi-person-plus me-2"></i>Nuevo cliente</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="qcError" class="alert alert-danger d-none small"></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Nombre completo *</label><input type="text" id="qc_full_name" class="form-control"></div>
        <div class="row g-2">
            <div class="col-md-6 mb-3"><label class="form-label small fw-semibold">CI / NIT</label><input type="text" id="qc_id_number" class="form-control"></div>
            <div class="col-md-6 mb-3"><label class="form-label small fw-semibold">Teléfono</label><input type="text" id="qc_phone" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="qcSave"><i class="bi bi-check-lg me-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
function fmtD(d) { return ('0'+d.getDate()).slice(-2)+'/'+('0'+(d.getMonth()+1)).slice(-2)+'/'+d.getFullYear(); }
function iso(d) { return d.getFullYear()+'-'+('0'+(d.getMonth()+1)).slice(-2)+'-'+('0'+d.getDate()).slice(-2); }
function parseD(s) { return s ? new Date(s + 'T00:00:00') : null; }

function isRenta() { return document.querySelector('input[name=payment_mode]:checked').value === 'renta'; }

function onModeChange() {
    const renta = isRenta();
    document.getElementById('rentaBlock').style.display = renta ? '' : 'none';
    document.getElementById('unicoBlock').style.display = renta ? 'none' : '';
    document.getElementById('billing_period').disabled = !renta;
    document.getElementById('period_amount').disabled = !renta;
    document.getElementById('daily_rate').required = !renta;
    if (renta) generateSchedule(); else updateSummary();
}

function onUnitChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const rate = parseFloat(opt.dataset.rate) || 0;
    if (sel.value && rate > 0) {
        if (!isRenta()) document.getElementById('daily_rate').value = rate.toFixed(2);
        else if (parseFloat(document.getElementById('period_amount').value) === 0) {
            document.getElementById('period_amount').value = rate.toFixed(2);
            generateSchedule();
            return;
        }
    }
    onRangeChange();
}

function onRangeChange() {
    if (isRenta()) generateSchedule(); else updateSummary();
}

function daysBetween(s, e) {
    return Math.max(1, Math.round((e - s) / 86400000) + 1);
}

function generateSchedule() {
    const body = document.getElementById('schBody');
    const empty = document.getElementById('schEmpty');
    body.innerHTML = '';
    const s = parseD(document.getElementById('start_date').value);
    const e = parseD(document.getElementById('end_date').value);
    const freq = document.getElementById('billing_period').value;
    const amount = parseFloat(document.getElementById('period_amount').value) || 0;

    if (!s || !e || e < s || amount <= 0) {
        empty.style.display = '';
        updateSummary();
        return;
    }
    empty.style.display = 'none';

    const rows = [];
    if (freq === 'diario') {
        let d = new Date(s), n = 1;
        while (d <= e) {
            rows.push({ label: fmtD(d), start: iso(d), end: iso(d), due: iso(d) });
            d.setDate(d.getDate() + 1); n++;
        }
    } else if (freq === 'semanal') {
        let ps = new Date(s), n = 1;
        while (ps <= e) {
            let pe = new Date(ps); pe.setDate(pe.getDate() + 6);
            if (pe > e) pe = new Date(e);
            rows.push({ label: 'Semana ' + n + ': ' + fmtD(ps) + '–' + fmtD(pe), start: iso(ps), end: iso(pe), due: iso(ps) });
            ps = new Date(ps); ps.setDate(ps.getDate() + 7); n++;
        }
    } else { // mensual
        let ps = new Date(s), n = 1;
        while (ps <= e) {
            let pe = new Date(ps); pe.setMonth(pe.getMonth() + 1); pe.setDate(pe.getDate() - 1);
            if (pe > e) pe = new Date(e);
            rows.push({ label: MESES[ps.getMonth()] + ' ' + ps.getFullYear(), start: iso(ps), end: iso(pe), due: iso(ps) });
            ps = new Date(ps); ps.setMonth(ps.getMonth() + 1); n++;
        }
    }

    rows.forEach((r, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-1">${i + 1}</td>
            <td class="py-1">
                ${r.label}
                <input type="hidden" name="installments[${i}][period_label]" value="${r.label}">
                <input type="hidden" name="installments[${i}][period_start]" value="${r.start}">
                <input type="hidden" name="installments[${i}][period_end]" value="${r.end}">
            </td>
            <td class="py-1"><input type="date" name="installments[${i}][due_date]" class="form-control form-control-sm" value="${r.due}"></td>
            <td class="py-1">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light px-2">Bs.</span>
                    <input type="number" name="installments[${i}][amount]" class="form-control sch-amount text-end" step="0.01" min="0.01" value="${amount.toFixed(2)}" oninput="updateSummary()">
                </div>
            </td>`;
        body.appendChild(tr);
    });
    updateSummary();
}

function updateSummary() {
    const deposit = parseFloat(document.getElementById('deposit').value) || 0;
    document.getElementById('sumDeposit').textContent = 'Bs. ' + deposit.toFixed(2);

    if (isRenta()) {
        const amounts = Array.from(document.querySelectorAll('.sch-amount')).map(el => parseFloat(el.value) || 0);
        const total = amounts.reduce((a, b) => a + b, 0);
        document.getElementById('lblQty').textContent = 'N° cuotas';
        document.getElementById('sumQty').textContent = amounts.length;
        document.getElementById('sumRental').textContent = 'Bs. ' + total.toFixed(2);
        document.getElementById('lblGrand').textContent = 'A cobrar en entrega';
        document.getElementById('sumGrand').textContent = 'Bs. ' + deposit.toFixed(2);
        document.getElementById('grandHint').textContent = 'En renta solo se cobra el depósito al entregar; las cuotas se cobran en Cobros.';
    } else {
        const s = parseD(document.getElementById('start_date').value);
        const e = parseD(document.getElementById('end_date').value);
        const rate = parseFloat(document.getElementById('daily_rate').value) || 0;
        const days = (s && e && e >= s) ? daysBetween(s, e) : 0;
        const rental = days * rate;
        document.getElementById('lblQty').textContent = 'Días';
        document.getElementById('sumQty').textContent = days;
        document.getElementById('sumRental').textContent = 'Bs. ' + rental.toFixed(2);
        document.getElementById('lblGrand').textContent = 'A cobrar en entrega';
        document.getElementById('sumGrand').textContent = 'Bs. ' + (rental + deposit).toFixed(2);
        document.getElementById('grandHint').textContent = 'Pago único: alquiler + depósito al entregar.';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    onModeChange();

    const saveBtn = document.getElementById('qcSave');
    if (saveBtn) saveBtn.addEventListener('click', async function () {
        const name = document.getElementById('qc_full_name').value.trim();
        const err  = document.getElementById('qcError');
        err.classList.add('d-none');
        if (!name) { err.textContent = 'El nombre es obligatorio.'; err.classList.remove('d-none'); return; }
        saveBtn.disabled = true;
        try {
            const res = await fetch('{{ route('clients.quick-store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({
                    full_name: name,
                    id_number: document.getElementById('qc_id_number').value.trim(),
                    phone: document.getElementById('qc_phone').value.trim(),
                })
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message || 'Error al guardar.');
            const opt = new Option(data.client.full_name, data.client.id, true, true);
            const csel = document.getElementById('client_id');
            csel.appendChild(opt);
            csel.dispatchEvent(new Event('change'));
            bootstrap.Modal.getInstance(document.getElementById('quickClientModal')).hide();
            document.getElementById('qc_full_name').value = '';
            document.getElementById('qc_id_number').value = '';
            document.getElementById('qc_phone').value = '';
        } catch (e) {
            err.textContent = e.message; err.classList.remove('d-none');
        } finally {
            saveBtn.disabled = false;
        }
    });
});
</script>
@endpush
@endsection
