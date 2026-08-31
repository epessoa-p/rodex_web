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
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Comisión seleccionada</span><strong id="modalSel">{{ money(0) }}</strong></div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Bono / extra (opcional)</label>
                            <input type="number" step="0.01" min="0" name="bonus" id="bonus" class="form-control" value="0">
                        </div>
                        <div class="d-flex justify-content-between mb-3 fs-5"><span class="fw-semibold">Total a pagar</span><strong class="text-success" id="modalTotal">{{ money(0) }}</strong></div>

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

    {{-- Pagadas --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-circle me-2 text-success"></i>OTs pagadas ({{ count($paid) }})</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-4">OT</th><th>Fecha OT</th><th>Pagada el</th><th class="text-end pe-4">Comisión</th></tr>
                </thead>
                <tbody>
                    @forelse($paid as $p)
                    <tr>
                        <td class="ps-4 fw-semibold"><a href="{{ route('workshop.orders.show', $p['order_id']) }}" class="text-decoration-none">{{ $p['code'] }}</a></td>
                        <td class="text-muted small">{{ $p['date'] ? \Illuminate\Support\Carbon::parse($p['date'])->format('d/m/Y') : '—' }}</td>
                        <td class="text-muted small">{{ $p['payment_date'] ? \Illuminate\Support\Carbon::parse($p['payment_date'])->format('d/m/Y') : '—' }}</td>
                        <td class="text-end pe-4">{{ money($p['commission']) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay OTs pagadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
    const bonusEl = document.getElementById('bonus');

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
        updateTotal();
    }
    function updateTotal() {
        const amt = selectedAmount() + (parseFloat(bonusEl?.value || 0) || 0);
        const mt = document.getElementById('modalTotal'); if (mt) mt.textContent = fmt(amt);
    }
    checks().forEach(c => c.addEventListener('change', refresh));
    document.getElementById('checkAll')?.addEventListener('change', function () {
        checks().forEach(c => c.checked = this.checked); refresh();
    });
    bonusEl?.addEventListener('input', updateTotal);

    const modalEl = document.getElementById('payModal');
    if (modalEl && btnPay) {
        const modal = new bootstrap.Modal(modalEl);
        btnPay.addEventListener('click', () => { refresh(); modal.show(); });
        document.querySelectorAll('input[name="payment_source"]').forEach(r => r.addEventListener('change', function () {
            const aw = document.getElementById('accountWrap');
            if (aw) aw.classList.toggle('d-none', this.value !== 'treasury');
        }));
    }
    refresh();
})();
</script>
@endpush
