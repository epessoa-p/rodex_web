@extends('layouts.app')
@section('title', 'Recepción: ' . $receipt->code)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:linear-gradient(90deg,#198754,#4ade80);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="mb-1 fw-bold fs-4">{{ $receipt->code }}</h1>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span>
                            <i class="bi bi-file-earmark-text me-1"></i>
                            OC: <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="text-muted text-decoration-none fw-semibold">
                                {{ $receipt->purchaseOrder->code }}
                            </a>
                        </span>
                        <span><i class="bi bi-truck me-1"></i>{{ $receipt->purchaseOrder->supplier->name }}</span>
                        <span><i class="bi bi-building me-1"></i>{{ $receipt->warehouse->name }}</span>
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $receipt->receipt_date->format('d/m/Y') }}</span>
                        <span><i class="bi bi-person me-1"></i>{{ $receipt->receivedBy->name }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6">
                        <i class="bi bi-check-circle me-1"></i>Recepcionado
                    </span>
                    @if($receipt->purchase_id)
                    <a href="{{ route('purchases.show', $receipt->purchase_id) }}"
                       class="badge bg-primary text-white text-decoration-none fs-6">
                        <i class="bi bi-receipt me-1"></i>Facturada &mdash; {{ $receipt->purchase?->code }}
                    </a>
                    @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-6">
                        <i class="bi bi-clock me-1"></i>Pendiente de facturar
                    </span>
                    @endif
                    <a href="{{ route('goods-receipts.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Items recibidos --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2 text-muted"></i>Productos recibidos</h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        {{ $receipt->items->count() }} producto{{ $receipt->items->count() !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted">Producto</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Cantidad recibida</th>
                                    <th class="py-3 small fw-semibold text-muted text-end pe-4">Costo unitario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($receipt->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end fw-semibold text-success small">
                                        {{ number_format($item->quantity, 0) }}
                                    </td>
                                    <td class="py-3 text-end small text-muted pe-4">
                                        ${{ number_format($item->unit_cost ?? 0, 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Sin items.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info sidebar --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Detalle de recepción</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $receipt->code }}</dd>
                        <dt class="col-5 text-muted fw-normal">Orden de compra</dt>
                        <dd class="col-7 mb-0">
                            <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="text-decoration-none">
                                {{ $receipt->purchaseOrder->code }}
                            </a>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Proveedor</dt>
                        <dd class="col-7 mb-0">{{ $receipt->purchaseOrder->supplier->name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Almacén</dt>
                        <dd class="col-7 mb-0">{{ $receipt->warehouse->name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $receipt->receipt_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Recibido por</dt>
                        <dd class="col-7 mb-0">{{ $receipt->receivedBy->name }}</dd>
                    </dl>
                </div>
            </div>
            <div class="alert alert-success-subtle border border-success-subtle rounded-3 p-3 small">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Esta recepción generó movimientos de entrada en el inventario del almacén
                <strong>{{ $receipt->warehouse->name }}</strong>.
            </div>
        </div>

    </div>

</div>
@endsection
