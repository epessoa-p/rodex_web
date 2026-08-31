@extends('layouts.app')
@section('title', 'Pago a mecánicos')
@section('page')
@php
    $u = auth()->user(); $cc = $u->getCurrentCompany();
    $canPay = $u->is_super_admin || $u->hasPermissionInCompany('mechanic-payments.pay', $cc);
    $canTreasury = $u->is_super_admin || $u->hasPermissionInCompany('treasury.view', $cc);
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-coin me-2 text-success"></i>Pago a mecánicos</h1>
            <p class="text-muted mb-0 small">Comisión sobre la mano de obra de las OTs entregadas. Paga desde caja o tesorería.</p>
        </div>
        <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @unless($hasCash)
    <div class="alert alert-warning border-0 shadow-sm small"><i class="bi bi-info-circle me-2"></i>No tienes una caja abierta: para pagar en efectivo abre tu caja, o paga desde una cuenta de tesorería.</div>
    @endunless

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Mecánico</th>
                        <th class="text-center">Comisión</th>
                        <th class="text-end">Ganado</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Pendiente</th>
                        @if($canPay)<th class="text-end pe-4">Acción</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($mechanics as $m)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $m['name'] }}@unless($m['active'])<span class="badge bg-secondary-subtle text-secondary-emphasis ms-2">inactivo</span>@endunless</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($m['commission_rate'], 2), '0'), '.') }}%</td>
                        <td class="text-end">{{ money($m['earned']) }}</td>
                        <td class="text-end text-muted">{{ money($m['paid']) }}</td>
                        <td class="text-end fw-bold {{ $m['pending'] > 0 ? 'text-danger' : 'text-success' }}">{{ money($m['pending']) }}</td>
                        @if($canPay)
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-success btn-pay"
                                    data-id="{{ $m['id'] }}" data-name="{{ $m['name'] }}" data-pending="{{ max(0, $m['pending']) }}">
                                <i class="bi bi-cash me-1"></i>Pagar
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-person-gear fs-3 d-block mb-2 opacity-50"></i>No hay mecánicos con comisiones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canPay)
{{-- Modal de pago --}}
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('workshop.mechanic-payments.store') }}">
                @csrf
                <input type="hidden" name="mechanic_id" id="pay_mechanic_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2 text-success"></i>Pagar a <span id="pay_mechanic_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="pay_amount" class="form-control" required>
                        <div class="form-text">Se propone el pendiente; puedes ajustarlo (pago parcial o bono).</div>
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
                        <select name="treasury_account_id" id="pay_account" class="form-select">
                            <option value="">— Seleccionar —</option>
                            @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }} · {{ money($a->balance) }}</option>
                            @endforeach
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
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const el = document.getElementById('payModal');
    if (!el) return;
    const modal = new bootstrap.Modal(el);
    document.querySelectorAll('.btn-pay').forEach(b => b.addEventListener('click', function () {
        document.getElementById('pay_mechanic_id').value = this.dataset.id;
        document.getElementById('pay_mechanic_name').textContent = this.dataset.name;
        document.getElementById('pay_amount').value = this.dataset.pending;
        document.getElementById('src_cash').checked = true;
        const aw = document.getElementById('accountWrap');
        if (aw) aw.classList.add('d-none');
        modal.show();
    }));
    document.querySelectorAll('input[name="payment_source"]').forEach(r => r.addEventListener('change', function () {
        const aw = document.getElementById('accountWrap');
        if (aw) aw.classList.toggle('d-none', this.value !== 'treasury');
    }));
})();
</script>
@endpush
