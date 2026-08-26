@extends('layouts.app')
@section('title', 'Factura: ' . $purchase->code)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $purchase->code }}</h1>
                        <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle fs-6">
                            {{ $purchase->payment_status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-truck me-1"></i>{{ $purchase->supplier->name }}</span>
                        @if($purchase->invoice_number)
                        <span><i class="bi bi-receipt me-1"></i>Fac: {{ $purchase->invoice_number }}</span>
                        @endif
                        @if($purchase->purchaseOrder)
                        <a href="{{ route('purchase-orders.show', $purchase->purchaseOrder) }}" class="text-muted text-decoration-none">
                            <i class="bi bi-file-earmark-text me-1"></i>{{ $purchase->purchaseOrder->code }}
                        </a>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $purchase->purchase_date->format('d/m/Y') }}</span>
                        <span><i class="bi bi-person me-1"></i>{{ $purchase->createdBy?->name ?: '—' }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if($purchase->balance > 0)
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.pay', auth()->user()->getCurrentCompany()))
                        <button type="button" class="btn btn-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#payModal{{ $purchase->id }}">
                            <i class="bi bi-cash-coin me-1"></i>Registrar pago
                        </button>
                        @endif
                    @endif
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.delete', auth()->user()->getCurrentCompany()))
                    <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('¿Eliminar factura {{ addslashes($purchase->code) }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-light border text-danger">
                            <i class="bi bi-trash me-1"></i>Eliminar
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('purchases.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Items --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos facturados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted">Producto</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Cantidad</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Costo unit.</th>
                                    <th class="py-3 small fw-semibold text-muted text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchase->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end small">{{ number_format($item->quantity, 0) }}</td>
                                    <td class="py-3 text-end small">{{ money($item->unit_cost, null, 2) }}</td>
                                    <td class="py-3 text-end fw-semibold small pe-4">{{ money($item->quantity * $item->unit_cost, null, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Sin items.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3">
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            @php
                                $subtotal = $purchase->items->sum(fn($i) => $i->quantity * $i->unit_cost);
                            @endphp
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal</span>
                                <span>{{ money($subtotal, null, 2) }}</span>
                            </div>
                            @if($purchase->tax)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Impuesto</span>
                                <span>{{ money($purchase->tax, null, 2) }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total</span>
                                <span>{{ money($purchase->total, null, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-success mt-1">
                                <span>Pagado</span>
                                <span>{{ money($purchase->paid_amount, null, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 {{ $purchase->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                <span>Saldo</span>
                                <span>{{ money($purchase->balance, null, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Historial de pagos --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Pagos registrados</h6>
                    <span class="badge bg-light text-muted border">{{ $purchase->payments->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($purchase->payments as $payment)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <div class="fw-semibold small">{{ money($payment->amount, null, 2) }}</div>
                            <div class="text-muted" style="font-size:.78rem">
                                {{ $payment->payment_date->format('d/m/Y') }}
                                &middot; {{ $payment->treasuryAccount?->name ?: '—' }}
                                @if($payment->method)
                                &middot; {{ ucfirst($payment->method) }}
                                @endif
                            </div>
                            @if($payment->reference)
                            <small class="text-muted">Ref: {{ $payment->reference }}</small>
                            @endif
                        </div>
                        <div class="text-muted small">{{ $payment->user?->name ?: '—' }}</div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-cash-coin fs-2 d-block mb-2 opacity-25"></i>
                        Sin pagos registrados aún.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Sidebar info --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $purchase->code }}</dd>
                        <dt class="col-5 text-muted fw-normal">N° Factura</dt>
                        <dd class="col-7 mb-0">{{ $purchase->invoice_number ?: '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Proveedor</dt>
                        <dd class="col-7 mb-0">{{ $purchase->supplier->name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $purchase->purchase_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">OC vinculada</dt>
                        <dd class="col-7 mb-0">
                            @if($purchase->purchaseOrder)
                            <a href="{{ route('purchase-orders.show', $purchase->purchaseOrder) }}" class="text-decoration-none">
                                {{ $purchase->purchaseOrder->code }}
                            </a>
                            @else —
                            @endif
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Creado por</dt>
                        <dd class="col-7 mb-0">{{ $purchase->createdBy?->name ?: '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Creado</dt>
                        <dd class="col-7 mb-0">{{ $purchase->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($purchase->balance > 0)
            <div class="card border-0 shadow-sm mt-4" style="border-left:4px solid var(--brand-red) !important">
                <div class="card-body p-4">
                    <div class="text-muted small mb-1">Saldo pendiente de pago</div>
                    <div class="fw-bold text-danger fs-4">{{ money($purchase->balance, null, 2) }}</div>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.pay', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('accounts-payable.index') }}" class="btn btn-primary btn-sm w-100 mt-3">
                        <i class="bi bi-cash-coin me-1"></i>Ir a cuentas por pagar
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Datos del proveedor --}}
            @if($purchase->supplier)
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-truck me-2 text-muted"></i>Datos del proveedor</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-3">
                        <dt class="col-5 text-muted fw-normal">Nombre</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $purchase->supplier->name }}</dd>
                        @if($purchase->supplier->nit)
                        <dt class="col-5 text-muted fw-normal">NIT</dt>
                        <dd class="col-7 mb-0">{{ $purchase->supplier->nit }}</dd>
                        @endif
                        @if($purchase->supplier->contact_name)
                        <dt class="col-5 text-muted fw-normal">Contacto</dt>
                        <dd class="col-7 mb-0">{{ $purchase->supplier->contact_name }}</dd>
                        @endif
                        @if($purchase->supplier->phone)
                        <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                        <dd class="col-7 mb-0">
                            <a href="tel:{{ $purchase->supplier->phone }}" class="text-decoration-none">{{ $purchase->supplier->phone }}</a>
                        </dd>
                        @endif
                        @if($purchase->supplier->email)
                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 mb-0 text-truncate">
                            <a href="mailto:{{ $purchase->supplier->email }}" class="text-decoration-none">{{ $purchase->supplier->email }}</a>
                        </dd>
                        @endif
                        @if($purchase->supplier->address)
                        <dt class="col-5 text-muted fw-normal">Dirección</dt>
                        <dd class="col-7 mb-0">{{ $purchase->supplier->address }}</dd>
                        @endif
                    </dl>
                    <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="btn btn-light border btn-sm w-100">
                        <i class="bi bi-building me-1"></i>Ver ficha del proveedor
                    </a>
                </div>
            </div>
            @endif

        </div>

    </div>

</div>

{{-- Modal de registro de pago --}}
@if($purchase->balance > 0 && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.pay', auth()->user()->getCurrentCompany())))
@php $hasCaja = (bool) $cashSession; @endphp
<div class="modal fade" id="payModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-cash-coin me-2 text-muted"></i>Registrar pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('accounts-payable.payment', $purchase) }}" method="POST">
                @csrf
                <div class="modal-body p-4">

                    {{-- Info de la factura --}}
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
                                <div class="text-muted">Total</div>
                                <div class="fw-semibold">{{ money($purchase->total, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Pagado</div>
                                <div class="fw-semibold text-success">{{ money($purchase->paid_amount, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Saldo</div>
                                <div class="fw-bold text-danger fs-6">{{ money($purchase->balance, null, 2) }}</div>
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
                                <span class="fw-bold text-success">Disponible: {{ money($cashSession->expectedBalance()) }}</span>
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
                                    {{ $account->name }} (saldo: {{ money($account->balance, null, 2) }})
                                </option>
                                @endforeach
                            </select>
                            @error('treasury_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="amt_{{ $purchase->id }}">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="amt_{{ $purchase->id }}" name="amount"
                                       step="0.01" min="0.01" max="{{ $purchase->balance }}"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', number_format($purchase->balance, 2, '.', '')) }}" required>
                            </div>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="pd_{{ $purchase->id }}">Fecha de pago <span class="text-danger">*</span></label>
                            <input type="date" id="pd_{{ $purchase->id }}" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
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
                        <textarea id="notes_{{ $purchase->id }}" name="notes" class="form-control" rows="2"
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

@push('scripts')
<script>
(function () {
    document.addEventListener('shown.bs.tab', function (e) {
        const btn   = e.target;
        const modal = btn.closest('.modal');
        if (!modal || !btn.dataset.bsTarget) return;
        const isCaja   = btn.dataset.bsTarget.indexOf('srcCaja') !== -1;
        const hidden   = modal.querySelector('.pay-source-input');
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
