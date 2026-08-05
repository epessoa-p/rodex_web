@extends('layouts.app')
@section('title', 'Devolución: ' . $return->code)
@section('page')
<div class="container-fluid">

    {{-- Print-only header --}}
    <div class="print-header d-none">
        <h3 class="fw-bold mb-1">{{ $return->company?->name ?? config('brand.name') }}</h3>
        <p class="mb-0 text-muted small">Comprobante de devolución</p>
    </div>

    {{-- ── HEADER CARD ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden no-print-shadow">
        <div style="height:6px;background:linear-gradient(90deg,#212529 0%,#495057 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $return->code }}</h1>
                        <span class="badge bg-{{ $return->refund_method_color }}-subtle text-{{ $return->refund_method_color }} border border-{{ $return->refund_method_color }}-subtle">
                            {{ $return->refund_method_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span>
                            <i class="bi bi-receipt me-1"></i>Venta:
                            <a href="{{ route('sales.show', $return->sale) }}"
                               class="text-decoration-none fw-semibold text-dark">
                                {{ $return->sale->code }}
                            </a>
                        </span>
                        @if($return->sale->client)
                        <span><i class="bi bi-person me-1"></i>{{ $return->sale->client->full_name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $return->return_date->format('d/m/Y') }}</span>
                        @if($return->createdBy)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $return->createdBy->name }}</span>
                        @endif
                        @if($return->session?->cashRegister ?? false)
                        <span><i class="bi bi-safe2 me-1"></i>{{ $return->session->cashRegister->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <button onclick="window.print()" class="btn btn-light border btn-sm">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>
                    <a href="{{ route('sale-returns.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Info alerts --}}
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-arrow-counterclockwise flex-shrink-0 fs-5"></i>
        <span>Stock reingresado al inventario.</span>
    </div>
    @php $balanceCancelled = (float) $return->total - (float) $return->refunded_amount; @endphp
    @if($return->refunded_amount > 0)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-cash-coin flex-shrink-0 fs-5"></i>
        <span>Se reembolsaron <strong>${{ number_format($return->refunded_amount, 2) }}</strong> en efectivo (egreso de caja).</span>
    </div>
    @endif
    @if($balanceCancelled > 0)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-calendar2-x flex-shrink-0 fs-5"></i>
        <span>Se canceló <strong>${{ number_format($balanceCancelled, 2) }}</strong> del saldo de crédito pendiente (cuotas reducidas).</span>
    </div>
    @endif

    <div class="row g-4">

        {{-- ── ITEMS + TOTALS ─────────────────────────────────────── --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos devueltos</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted" style="font-size:.72rem;">Producto</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Cantidad</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Precio unit.</th>
                                    <th class="py-3 fw-semibold text-muted text-end pe-4" style="font-size:.72rem;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($return->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end small">{{ number_format($item->quantity, 0) }}</td>
                                    <td class="py-3 text-end small">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-3 text-end fw-semibold small pe-4">
                                        ${{ number_format($item->quantity * $item->unit_price, 2) }}
                                    </td>
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
                            @php $balanceCancelled = (float) $return->total - (float) $return->refunded_amount; @endphp
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Valor mercadería devuelta</span>
                                <span>${{ number_format($return->total, 2) }}</span>
                            </div>
                            @if($balanceCancelled > 0)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Saldo de crédito cancelado</span>
                                <span>−${{ number_format($balanceCancelled, 2) }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Efectivo reembolsado</span>
                                <span class="text-danger">${{ number_format($return->refunded_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reason/notes --}}
            @if($return->reason || $return->notes)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-muted"></i>Observaciones</h6>
                </div>
                <div class="card-body p-4">
                    @if($return->reason)
                    <div class="mb-2">
                        <span class="text-muted small fw-semibold">Motivo: </span>
                        <span class="small">{{ $return->reason }}</span>
                    </div>
                    @endif
                    @if($return->notes)
                    <div>
                        <span class="text-muted small fw-semibold">Notas: </span>
                        <span class="small">{{ $return->notes }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── SIDEBAR INFO ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $return->code }}</dd>
                        <dt class="col-5 text-muted fw-normal">Venta</dt>
                        <dd class="col-7 mb-0">
                            <a href="{{ route('sales.show', $return->sale) }}"
                               class="text-decoration-none fw-semibold text-dark">
                                {{ $return->sale->code }}
                            </a>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Cliente</dt>
                        <dd class="col-7 mb-0">{{ $return->sale->client?->full_name ?? 'Cliente general' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $return->return_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Método</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge bg-{{ $return->refund_method_color }}-subtle text-{{ $return->refund_method_color }} border border-{{ $return->refund_method_color }}-subtle" style="font-size:.72rem;">
                                {{ $return->refund_method_label }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Creado por</dt>
                        <dd class="col-7 mb-0">{{ $return->createdBy?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Registrado</dt>
                        <dd class="col-7 mb-0">{{ $return->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>

</div>

@push('styles')
<style>
@media print {
    .no-print, .no-print * { display: none !important; }
    .app-sidebar, .app-topbar, .btn { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .print-header { display: block !important; margin-bottom: 1rem; }
    .no-print-shadow { box-shadow: none !important; }
}
</style>
@endpush

@endsection
