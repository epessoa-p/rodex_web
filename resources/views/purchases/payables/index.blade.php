@extends('layouts.app')
@section('title', 'Cuentas por pagar')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-credit-card me-2 text-danger"></i>Cuentas por pagar</h1>
            <p class="text-muted mb-0 small">Facturas pendientes de pago a proveedores.</p>
        </div>
    </div>

    {{-- KPI total --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid var(--brand-red) !important">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:48px;height:48px;background:rgba(225,6,0,.08)">
                            <i class="bi bi-exclamation-circle fs-4 text-danger"></i>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Total por pagar</div>
                            <div class="fw-bold text-danger fs-4">${{ number_format($totalOwed, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(13,110,253,.08)">
                        <i class="bi bi-receipt fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Facturas pendientes</div>
                        <div class="fw-bold fs-4">{{ $payables->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Código</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Fecha</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Total</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Pagado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Saldo</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payables as $purchase)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $purchase->code }}
                                </a>
                            </td>
                            <td class="py-3 small">{{ $purchase->supplier->name }}</td>
                            <td class="py-3 small text-muted">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td class="py-3 text-end small">${{ number_format($purchase->total, 2) }}</td>
                            <td class="py-3 text-end small text-success">${{ number_format($purchase->paid_amount, 2) }}</td>
                            <td class="py-3 text-end fw-bold text-danger">${{ number_format($purchase->balance, 2) }}</td>
                            <td class="py-3">
                                <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle">
                                    {{ $purchase->payment_status_label }}
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-light border me-1" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.pay', auth()->user()->getCurrentCompany()))
                                @if($purchase->balance > 0)
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#payModal{{ $purchase->id }}"
                                        title="Registrar pago">
                                    <i class="bi bi-cash-coin me-1"></i>Pagar
                                </button>
                                @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">Sin cuentas por pagar. Todo al día.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $payables->links() }}</div>

</div>

{{-- Payment modals (one per row) --}}
@if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.pay', auth()->user()->getCurrentCompany()))
@php $hasCaja = (bool) $cashSession; @endphp
@foreach($payables as $purchase)
@if($purchase->balance > 0)
<div class="modal fade" id="payModal{{ $purchase->id }}" tabindex="-1"
     aria-labelledby="payModalLabel{{ $purchase->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="payModalLabel{{ $purchase->id }}">
                    <i class="bi bi-cash-coin me-2 text-muted"></i>Registrar pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('accounts-payable.payment', $purchase) }}" method="POST">
                @csrf
                <div class="modal-body p-4">

                    {{-- Purchase info --}}
                    <div class="rounded-3 border p-3 mb-4 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $purchase->code }}</div>
                                <div class="text-muted small">{{ $purchase->supplier->name }}</div>
                            </div>
                            <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle">
                                {{ $purchase->payment_status_label }}
                            </span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4">
                                <div class="text-muted">Total factura</div>
                                <div class="fw-semibold">${{ number_format($purchase->total, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Pagado</div>
                                <div class="fw-semibold text-success">${{ number_format($purchase->paid_amount, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Saldo</div>
                                <div class="fw-bold text-danger fs-6">${{ number_format($purchase->balance, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Origen del pago: Caja del personal / Cuenta de tesorería --}}
                    <input type="hidden" name="payment_source" class="pay-source-input" value="{{ $hasCaja ? 'caja' : 'tesoreria' }}">
                    <ul class="nav nav-pills nav-fill gap-2 mb-3" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link {{ $hasCaja ? 'active' : '' }}" type="button"
                                    data-bs-toggle="pill" data-bs-target="#srcCaja{{ $purchase->id }}"
                                    {{ $hasCaja ? '' : 'disabled' }}>
                                <i class="bi bi-cash-stack me-1"></i>Caja
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $hasCaja ? '' : 'active' }}" type="button"
                                    data-bs-toggle="pill" data-bs-target="#srcTes{{ $purchase->id }}">
                                <i class="bi bi-bank me-1"></i>Cuenta de tesorería
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mb-3">
                        {{-- Caja del personal --}}
                        <div class="tab-pane fade {{ $hasCaja ? 'show active' : '' }}" id="srcCaja{{ $purchase->id }}">
                            @if($hasCaja)
                            <div class="d-flex align-items-center justify-content-between rounded-3 px-3 py-2"
                                 style="background:#f0fbf4;border:1px solid #c9efd6;">
                                <span class="small fw-semibold text-success-emphasis">
                                    <i class="bi bi-cash-stack me-1"></i>{{ $cashSession->cashRegister->name ?? 'Caja' }}
                                </span>
                                <span class="fw-bold text-success">Disponible: Bs. {{ number_format($cashSession->expectedBalance(), 2) }}</span>
                            </div>
                            <div class="form-text">El pago saldrá de tu caja abierta como egreso.</div>
                            @else
                            <div class="alert alert-warning border-0 small mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>No tienes una caja abierta. Usa una cuenta de tesorería.
                            </div>
                            @endif
                        </div>

                        {{-- Cuenta de tesorería --}}
                        <div class="tab-pane fade {{ $hasCaja ? '' : 'show active' }}" id="srcTes{{ $purchase->id }}">
                            <label class="form-label fw-semibold" for="ta_{{ $purchase->id }}">
                                Cuenta de tesorería <span class="text-danger">*</span>
                            </label>
                            <select id="ta_{{ $purchase->id }}" name="treasury_account_id"
                                    class="form-select @error('treasury_account_id') is-invalid @enderror"
                                    {{ $hasCaja ? '' : 'required' }}>
                                <option value="">— Seleccionar cuenta —</option>
                                @foreach($accounts as $account)
                                <option value="{{ $account->id }}" {{ old('treasury_account_id') == $account->id ? 'selected' : '' }}>
                                    {{ $account->name }} (saldo: ${{ number_format($account->balance, 2) }})
                                </option>
                                @endforeach
                            </select>
                            @error('treasury_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="amt_{{ $purchase->id }}">
                                Monto <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" id="amt_{{ $purchase->id }}" name="amount"
                                       step="0.01" min="0.01" max="{{ $purchase->balance }}"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', number_format($purchase->balance, 2, '.', '')) }}"
                                       required>
                            </div>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="pd_{{ $purchase->id }}">
                                Fecha de pago <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="pd_{{ $purchase->id }}" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                   required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de pago</label>
                        <input type="hidden" name="method" value="efectivo">
                        <input type="text" class="form-control" value="Efectivo" disabled>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold" for="notes_{{ $purchase->id }}">Notas</label>
                        <textarea id="notes_{{ $purchase->id }}" name="notes"
                                  class="form-control" rows="2"
                                  placeholder="Observaciones del pago...">{{ old('notes') }}</textarea>
                    </div>

                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Registrar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@push('scripts')
<script>
(function () {
    // Al cambiar de tab (Caja / Tesorería) ajustar el origen y la obligatoriedad del select.
    document.addEventListener('shown.bs.tab', function (e) {
        const btn   = e.target;
        const modal = btn.closest('.modal');
        if (!modal || !btn.dataset.bsTarget) return;
        const isCaja  = btn.dataset.bsTarget.indexOf('srcCaja') !== -1;
        const hidden  = modal.querySelector('.pay-source-input');
        const treasury = modal.querySelector('select[name="treasury_account_id"]');
        if (hidden) hidden.value = isCaja ? 'caja' : 'tesoreria';
        if (treasury) {
            if (isCaja) treasury.removeAttribute('required');
            else        treasury.setAttribute('required', 'required');
        }
    });
})();
</script>
@endpush
@endif

@endsection
