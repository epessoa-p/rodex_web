@extends('layouts.app')
@section('title', 'OC: ' . $order->code)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $order->code }}</h1>
                        <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle fs-6">
                            {{ $order->status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-truck me-1"></i>{{ $order->supplier->name }}</span>
                        @if($order->branch)
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $order->branch->name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $order->order_date->format('d/m/Y') }}</span>
                        @if($order->expected_date)
                        <span><i class="bi bi-calendar-check me-1"></i>Esperada: {{ $order->expected_date->format('d/m/Y') }}</span>
                        @endif
                        <span><i class="bi bi-person me-1"></i>{{ $order->createdBy?->name ?: '—' }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(!in_array($order->status, ['received', 'cancelled']))
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('goods-receipts.create', auth()->user()->getCurrentCompany()))
                        <a href="{{ route('goods-receipts.create', $order) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-in-down me-1"></i>Recepcionar
                        </a>
                        @endif
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.edit', auth()->user()->getCurrentCompany()))
                        @if(in_array($order->status, ['draft', 'sent']))
                        <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-light border btn-sm">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>
                        @endif
                        @endif
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.delete', auth()->user()->getCurrentCompany()))
                        <form action="{{ route('purchase-orders.cancel', $order) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Anular la orden {{ addslashes($order->code) }}? Esta acción no se puede deshacer.')">
                            @csrf
                            <button class="btn btn-sm btn-light border text-danger">
                                <i class="bi bi-x-circle me-1"></i>Anular
                            </button>
                        </form>
                        @endif
                    @endif
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-light border btn-sm">
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
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos ordenados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted">Producto</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Cant. ord.</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Cant. recib.</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Costo unit.</th>
                                    <th class="py-3 small fw-semibold text-muted text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end small">{{ number_format($item->quantity, 0) }}</td>
                                    <td class="py-3 text-end small">
                                        @php $received = $item->received_quantity ?? 0; @endphp
                                        <span class="{{ $received >= $item->quantity ? 'text-success fw-semibold' : 'text-warning' }}">
                                            {{ number_format($received, 0) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-end small">{{ money($item->unit_cost, null, 2) }}</td>
                                    <td class="py-3 text-end fw-semibold small pe-4">{{ money($item->quantity * $item->unit_cost, null, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Sin items.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3">
                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            @php
                                $subtotal = $order->items->sum(fn($i) => $i->quantity * $i->unit_cost);
                            @endphp
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal</span>
                                <span>{{ money($subtotal, null, 2) }}</span>
                            </div>
                            @if($order->tax)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Impuesto</span>
                                <span>{{ money($order->tax, null, 2) }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total</span>
                                <span>{{ money($order->total, null, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recepciones --}}
            @if($order->receipts->count())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-arrow-in-down me-2 text-muted"></i>Recepciones</h6>
                    <span class="badge bg-light text-muted border">{{ $order->receipts->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @foreach($order->receipts as $receipt)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <a href="{{ route('goods-receipts.show', $receipt) }}" class="fw-semibold text-decoration-none text-dark small">
                                {{ $receipt->code }}
                            </a>
                            <div class="text-muted" style="font-size:.78rem">
                                <i class="bi bi-building me-1"></i>{{ $receipt->warehouse?->name ?: '—' }}
                                &middot; {{ $receipt->receipt_date->format('d/m/Y') }}
                            </div>
                        </div>
                        <a href="{{ route('goods-receipts.show', $receipt) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Facturas vinculadas --}}
            @if($order->purchases->count())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2 text-muted"></i>Facturas vinculadas</h6>
                    <span class="badge bg-light text-muted border">{{ $order->purchases->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @foreach($order->purchases as $purchase)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-decoration-none text-dark small">
                                {{ $purchase->code }}
                            </a>
                            @if($purchase->invoice_number)
                            <span class="text-muted small ms-1">/ {{ $purchase->invoice_number }}</span>
                            @endif
                            <div class="text-muted" style="font-size:.78rem">{{ $purchase->purchase_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold small">{{ money($purchase->total, null, 2) }}</div>
                            <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle" style="font-size:.7rem">
                                {{ $purchase->payment_status_label }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Notes & meta --}}
        <div class="col-lg-4">
            @if($order->notes)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sticky me-2 text-muted"></i>Notas</h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-0" style="white-space:pre-wrap">{{ $order->notes }}</p>
                </div>
            </div>
            @endif
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <dl class="row g-2 mb-0 small">
                        <dt class="col-6 text-muted fw-normal">Creado por</dt>
                        <dd class="col-6 mb-0 fw-semibold">{{ $order->createdBy?->name ?: '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Creado</dt>
                        <dd class="col-6 mb-0">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
