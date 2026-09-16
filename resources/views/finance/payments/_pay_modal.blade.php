{{-- Modal de pago/gasto del hub "Pagos" (Personal y Gastos). Se rellena por JS
     con openPay({...}): kind, título, personal/servicio, concepto, monto.
     Origen: caja abierta del usuario o cuenta de tesorería. --}}
@php
    $available = $session ? (float) $session->expectedBalance() : null;
@endphp
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('payments.store') }}" id="payForm">
            @csrf
            <input type="hidden" name="back" value="{{ $tab }}">
            <input type="hidden" name="kind" id="pay_kind" value="other">
            <input type="hidden" name="personal_id" id="pay_personal_id" value="">
            <input type="hidden" name="expense_service_id" id="pay_service_id" value="">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="payTitle"><i class="bi bi-wallet2 me-2 text-danger"></i>Registrar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2 small text-muted" id="paySubtitle"></div>

                <div class="mb-3" id="payConceptWrap">
                    <label class="form-label small fw-semibold">Concepto</label>
                    <input type="text" name="concept" id="pay_concept" class="form-control" maxlength="255" placeholder="Ej: útiles de limpieza">
                </div>

                {{-- Período (solo pago a personal / servicios): Día · Semana · Quincena · Mes · Otro --}}
                <div class="mb-3" id="payPeriodWrap">
                    <label class="form-label small fw-semibold">Período</label>
                    <div class="d-flex flex-wrap gap-2 mb-2" id="payPeriodChips">
                        @foreach(['Día', 'Semana', 'Quincena', 'Mes', 'Otro'] as $p)
                        <button type="button" class="btn btn-sm btn-outline-secondary period-chip" data-period="{{ $p }}">{{ $p }}</button>
                        @endforeach
                    </div>
                    <input type="text" name="period" id="pay_period" class="form-control form-control-sm" maxlength="30" placeholder="Ej: Sep 2026, Quincena 1, Bono…">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-7">
                        <label class="form-label small fw-semibold">Monto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" step="0.01" min="0.01" name="amount" id="pay_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-semibold">Método</label>
                        <select name="method" class="form-select">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="qr">QR</option>
                        </select>
                    </div>
                </div>

                <label class="form-label small fw-semibold">Sale de</label>
                <div class="d-flex flex-column gap-2 mb-3">
                    <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ $session ? '' : 'text-muted' }}">
                        <input class="form-check-input mt-0" type="radio" name="payment_source" value="cash" {{ $session ? 'checked' : 'disabled' }}>
                        <i class="bi bi-cash-stack text-success"></i>
                        <span class="flex-grow-1">Mi caja
                            @if($session)
                                <small class="text-muted">· disponible {{ money($available) }}</small>
                            @else
                                <small class="text-muted">· no tienes una caja abierta</small>
                            @endif
                        </span>
                    </label>
                    <label class="border rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ $accounts->isEmpty() ? 'text-muted' : '' }}">
                        <input class="form-check-input mt-0" type="radio" name="payment_source" value="treasury" {{ $accounts->isEmpty() ? 'disabled' : (! $session ? 'checked' : '') }}>
                        <i class="bi bi-bank text-primary"></i>
                        <span class="flex-grow-1">Tesorería</span>
                    </label>
                    <select name="treasury_account_id" id="pay_account" class="form-select form-select-sm {{ $accounts->isEmpty() ? 'd-none' : '' }}">
                        <option value="">— Elegir cuenta —</option>
                        @foreach($accounts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }} · {{ money($a->balance) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-1">
                    <label class="form-label small fw-semibold">Notas</label>
                    <input type="text" name="notes" class="form-control form-control-sm" maxlength="500">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger px-4" type="submit"><i class="bi bi-check-lg me-1"></i>Registrar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Abre el modal preconfigurado. opts: {kind, title, subtitle, personalId, serviceId, concept, amount, period:boolean}
function openPay(opts) {
    document.getElementById('pay_kind').value        = opts.kind || 'other';
    document.getElementById('pay_personal_id').value = opts.personalId || '';
    document.getElementById('pay_service_id').value  = opts.serviceId || '';
    document.getElementById('payTitle').innerHTML    = '<i class="bi bi-wallet2 me-2 text-danger"></i>' + (opts.title || 'Registrar');
    document.getElementById('paySubtitle').textContent = opts.subtitle || '';
    const concept = document.getElementById('pay_concept');
    concept.value = opts.concept || '';
    document.getElementById('payConceptWrap').classList.toggle('d-none', opts.kind === 'payroll' || !!opts.serviceId);
    document.getElementById('payPeriodWrap').classList.toggle('d-none', !opts.period);
    document.getElementById('pay_period').value = '';
    document.querySelectorAll('.period-chip').forEach(b => b.classList.remove('active'));
    const amount = document.getElementById('pay_amount');
    amount.value = opts.amount ? Number(opts.amount).toFixed(2) : '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('payModal')).show();
    setTimeout(() => amount.focus(), 300);
}

(function () {
    // Chips de período → texto del período (Mes = mes actual en español).
    const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const now = new Date();
    document.querySelectorAll('.period-chip').forEach(btn => btn.addEventListener('click', function () {
        document.querySelectorAll('.period-chip').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const p = this.dataset.period;
        const input = document.getElementById('pay_period');
        input.value = p === 'Mes' ? (months[now.getMonth()] + ' ' + now.getFullYear())
                    : p === 'Otro' ? '' : p;
        if (p === 'Otro') input.focus();
    }));

    // Cuenta de tesorería solo cuando "Sale de" = Tesorería.
    const accountSel = document.getElementById('pay_account');
    function syncSource() {
        const src = document.querySelector('input[name="payment_source"]:checked')?.value;
        const isTreasury = src === 'treasury';
        accountSel.classList.toggle('d-none', !isTreasury || accountSel.options.length <= 1);
        accountSel.required = isTreasury;
    }
    document.querySelectorAll('input[name="payment_source"]').forEach(r => r.addEventListener('change', syncSource));
    syncSource();
})();
</script>
@endpush
