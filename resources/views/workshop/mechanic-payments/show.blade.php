@extends('layouts.app')
@section('title', 'Pago a ' . $mechanic->name)
@section('page')
@php
    $u = auth()->user(); $cc = $u->getCurrentCompany();
    $canPay = $u->is_super_admin || $u->hasPermissionInCompany('mechanic-payments.pay', $cc);
    $canTreasury = $u->is_super_admin || $u->hasPermissionInCompany('treasury.view', $cc);
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-person-gear me-2 text-success"></i>{{ $mechanic->name }}</h1>
            <p class="text-muted mb-0 small">
                Comisión {{ rtrim(rtrim(number_format($summary['commission_rate'], 2), '0'), '.') }}% ·
                Pendiente <strong class="text-danger">{{ money($summary['pending_total']) }}</strong> ·
                Pagado {{ money($summary['paid_total']) }}
            </p>
        </div>
        <a href="{{ route('workshop.mechanic-payments.index') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @unless($hasCash)
    <div class="alert alert-warning border-0 shadow-sm small"><i class="bi bi-info-circle me-2"></i>No tienes una caja abierta: para pagar en efectivo abre tu caja, o paga desde una cuenta de tesorería.</div>
    @endunless

    <form method="POST" action="{{ route('workshop.mechanic-payments.store') }}" id="payForm">
        @csrf
        <input type="hidden" name="mechanic_id" value="{{ $mechanic->id }}">

        {{-- Pendientes --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-hourglass-split me-2 text-danger"></i>OTs pendientes ({{ count($pending) }})</h6>
                @if($canPay && count($pending))
                <button type="button" class="btn btn-success btn-sm" id="btnPay" disabled><i class="bi bi-cash me-1"></i>Pagar seleccionadas (<span id="selCount">0</span>)</button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @if($canPay)<th class="ps-4" style="width:44px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>@endif
                            <th>OT</th>
                            <th>Fecha</th>
                            <th class="text-end pe-4">Comisión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending as $p)
                        <tr>
                            @if($canPay)<td class="ps-4"><input type="checkbox" class="form-check-input row-check" name="work_order_ids[]" value="{{ $p['order_id'] }}" data-amount="{{ $p['commission'] }}"></td>@endif
                            <td class="fw-semibold"><a href="{{ route('workshop.orders.show', $p['order_id']) }}" class="text-decoration-none">{{ $p['code'] }}</a></td>
                            <td class="text-muted small">{{ $p['date'] ? \Illuminate\Support\Carbon::parse($p['date'])->format('d/m/Y') : '—' }}</td>
                            <td class="text-end pe-4 fw-semibold">{{ money($p['commission']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Sin OTs pendientes.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($pending))
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="{{ $canPay ? 3 : 2 }}" class="text-end fw-semibold">Seleccionado</td>
                            <td class="text-end pe-4 fw-bold text-success" id="selTotal">{{ money(0) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Modal de pago --}}
        @if($canPay)
        <div class="modal fade" id="payModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-cash-coin me-2 text-success"></i>Pagar a {{ $mechanic->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Comisión de <span id="modalSelCount">0</span> OT(s)</span><strong id="modalSel">{{ money(0) }}</strong></div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Total a pagar</label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control form-control-lg" required>
                            <div class="form-text">Se propone la comisión de las OTs seleccionadas; puedes ajustarlo. Las OTs seleccionadas quedan vinculadas a este pago.</div>
                        </div>

                        <label class="form-label small fw-semibold">Pagar con</label>
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="payment_source" id="src_cash" value="cash" checked>
                            <label class="btn btn-outline-success" for="src_cash"><i class="bi bi-cash-stack me-1"></i>Caja</label>
                            @if($canTreasury)
                            <input type="radio" class="btn-check" name="payment_source" id="src_treasury" value="treasury">
                            <label class="btn btn-outline-success" for="src_treasury"><i class="bi bi-bank me-1"></i>Tesorería</label>
                            @endif
                        </div>
                        @if($canTreasury)
                        <div class="mb-3 d-none" id="accountWrap">
                            <label class="form-label small fw-semibold">Cuenta de tesorería</label>
                            <select name="treasury_account_id" class="form-select">
                                <option value="">— Seleccionar —</option>
                                @foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }} · {{ money($a->balance) }}</option>@endforeach
                            </select>
                        </div>
                        @endif
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Método</label>
                                <select name="method" class="form-select">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="qr">QR</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Notas</label>
                                <input type="text" name="notes" class="form-control" maxlength="1000" placeholder="Periodo, detalle…">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Registrar pago</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </form>

    {{-- Pagos --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-circle me-2 text-success"></i>Pagos realizados ({{ count($payments) }})</h6>
        </div>
        <div class="card-body p-3">
            @forelse($payments as $pay)
            <div class="accordion mb-2" id="acc{{ $pay['id'] }}">
                <div class="accordion-item border rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#pay{{ $pay['id'] }}">
                            <div class="d-flex justify-content-between align-items-center w-100 me-2 flex-wrap gap-2">
                                <span>
                                    <strong>{{ $pay['date'] ? \Illuminate\Support\Carbon::parse($pay['date'])->format('d/m/Y') : '—' }}</strong>
                                    <span class="badge bg-light text-muted border ms-2">{{ count($pay['orders']) }} OT(s)</span>
                                    <span class="text-muted small ms-2">{{ ucfirst($pay['method'] ?? 'efectivo') }} · {{ $pay['source'] === 'treasury' ? 'Tesorería'.($pay['account'] ? ' · '.$pay['account'] : '') : 'Caja' }}</span>
                                </span>
                                <span class="fw-bold text-success">{{ money($pay['amount']) }}</span>
                            </div>
                        </button>
                    </h2>
                    <div id="pay{{ $pay['id'] }}" class="accordion-collapse collapse" data-bs-parent="#acc{{ $pay['id'] }}">
                        <div class="accordion-body pt-2">
                            <table class="table table-sm align-middle mb-2">
                                <thead class="table-light"><tr><th>OT</th><th>Fecha</th><th class="text-end">Comisión</th></tr></thead>
                                <tbody>
                                    @foreach($pay['orders'] as $o)
                                    <tr>
                                        <td><a href="{{ route('workshop.orders.show', $o['order_id']) }}" class="text-decoration-none">{{ $o['code'] }}</a></td>
                                        <td class="text-muted small">{{ $o['date'] ? \Illuminate\Support\Carbon::parse($o['date'])->format('d/m/Y') : '—' }}</td>
                                        <td class="text-end">{{ money($o['commission']) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($pay['notes'])<div class="small text-muted mb-2"><i class="bi bi-sticky me-1"></i>{{ $pay['notes'] }}</div>@endif
                            <a href="{{ route('workshop.mechanic-payments.receipt', $pay['id']) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-file-earmark-text me-1"></i>Comprobante
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">Aún no hay pagos registrados.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const fmt = n => new Intl.NumberFormat('es-BO', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(n);
    const checks = () => Array.from(document.querySelectorAll('.row-check'));
    const btnPay = document.getElementById('btnPay');
    const selCount = document.getElementById('selCount');
    const selTotal = document.getElementById('selTotal');

    function selectedAmount() {
        return checks().filter(c => c.checked).reduce((s, c) => s + parseFloat(c.dataset.amount || 0), 0);
    }
    function refresh() {
        const sel = checks().filter(c => c.checked);
        const amt = selectedAmount();
        if (selCount) selCount.textContent = sel.length;
        if (selTotal) selTotal.textContent = fmt(amt);
        if (btnPay) btnPay.disabled = sel.length === 0;
        const ms = document.getElementById('modalSel'); if (ms) ms.textContent = fmt(amt);
        const msc = document.getElementById('modalSelCount'); if (msc) msc.textContent = sel.length;
    }
    checks().forEach(c => c.addEventListener('change', refresh));
    document.getElementById('checkAll')?.addEventListener('change', function () {
        checks().forEach(c => c.checked = this.checked); refresh();
    });

    const modalEl = document.getElementById('payModal');
    if (modalEl && btnPay) {
        const modal = new bootstrap.Modal(modalEl);
        btnPay.addEventListener('click', () => {
            refresh();
            const amountEl = document.getElementById('amount');
            if (amountEl) amountEl.value = selectedAmount().toFixed(2); // prellenado editable
            modal.show();
        });
        document.querySelectorAll('input[name="payment_source"]').forEach(r => r.addEventListener('change', function () {
            const aw = document.getElementById('accountWrap');
            if (aw) aw.classList.toggle('d-none', this.value !== 'treasury');
        }));
    }
    refresh();
})();
</script>
@endpush
