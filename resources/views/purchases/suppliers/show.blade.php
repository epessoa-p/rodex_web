@extends('layouts.app')
@section('title', 'Proveedor: ' . $supplier->name)
@section('page')
<div class="container-fluid">

    {{-- Hero header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="rounded-3 border d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                     style="width:80px;height:80px;background:#f4f4f5;font-size:1.8rem;color:#ccc;">
                    {{ strtoupper(substr($supplier->name, 0, 2)) }}
                </div>
                <div class="flex-grow-1">
                    <h1 class="mb-1 fw-bold fs-4">{{ $supplier->name }}</h1>
                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size:.88rem">
                        @if($supplier->nit)
                        <span><i class="bi bi-card-text me-1"></i>NIT: {{ $supplier->nit }}</span>
                        @endif
                        @if($supplier->phone)
                        <span><i class="bi bi-telephone me-1"></i>{{ $supplier->phone }}</span>
                        @endif
                        @if($supplier->email)
                        <a href="mailto:{{ $supplier->email }}" class="text-muted text-decoration-none">
                            <i class="bi bi-envelope me-1"></i>{{ $supplier->email }}
                        </a>
                        @endif
                    </div>
                    @if($supplier->address)
                    <p class="text-muted mb-0 mt-1 small"><i class="bi bi-geo-alt me-1"></i>{{ $supplier->address }}</p>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-start">
                    @if($supplier->active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                    @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                    @endif
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI saldo --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="text-muted small mb-1">Saldo pendiente</div>
                    <div class="fw-bold fs-4 {{ $supplier->balance_owed > 0 ? 'text-danger' : 'text-success' }}">
                        {{ money($supplier->balance_owed, null, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="text-muted small mb-1">Total compras</div>
                    <div class="fw-bold fs-4">{{ $supplier->purchases->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="text-muted small mb-1">Órdenes de compra</div>
                    <div class="fw-bold fs-4">{{ $supplier->purchaseOrders->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Información del proveedor --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 mb-0" style="font-size:.9rem">
                        <dt class="col-5 text-muted fw-normal">Nombre</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $supplier->name }}</dd>
                        <dt class="col-5 text-muted fw-normal">NIT</dt>
                        <dd class="col-7 mb-0">{{ $supplier->nit ?: '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Contacto</dt>
                        <dd class="col-7 mb-0">{{ $supplier->contact_name ?: '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                        <dd class="col-7 mb-0">
                            @if($supplier->phone)
                            <a href="tel:{{ $supplier->phone }}" class="text-decoration-none">{{ $supplier->phone }}</a>
                            @else —
                            @endif
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 mb-0">
                            @if($supplier->email)
                            <a href="mailto:{{ $supplier->email }}" class="text-decoration-none">{{ $supplier->email }}</a>
                            @else —
                            @endif
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Dirección</dt>
                        <dd class="col-7 mb-0 small">{{ $supplier->address ?: '—' }}</dd>
                        @if($supplier->notes)
                        <dt class="col-5 text-muted fw-normal">Notas</dt>
                        <dd class="col-7 mb-0">
                            <div class="p-2 bg-light rounded-2 small" style="white-space:pre-wrap">{{ $supplier->notes }}</div>
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Historial --}}
        <div class="col-lg-7">

            {{-- Compras --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2 text-muted"></i>Compras</h6>
                    <span class="badge bg-light text-muted border">{{ $supplier->purchases->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($supplier->purchases->take(10) as $purchase)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-decoration-none text-dark small">
                                {{ $purchase->code }}
                            </a>
                            @if($purchase->invoice_number)
                            <span class="text-muted small ms-2">Fac: {{ $purchase->invoice_number }}</span>
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
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                        Sin compras registradas.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Órdenes de compra --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-text me-2 text-muted"></i>Órdenes de compra</h6>
                    <span class="badge bg-light text-muted border">{{ $supplier->purchaseOrders->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($supplier->purchaseOrders->take(10) as $order)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <a href="{{ route('purchase-orders.show', $order) }}" class="fw-semibold text-decoration-none text-dark small">
                                {{ $order->code }}
                            </a>
                            <div class="text-muted" style="font-size:.78rem">{{ $order->order_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold small">{{ money($order->total, null, 2) }}</div>
                            <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle" style="font-size:.7rem">
                                {{ $order->status_label }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark-text fs-2 d-block mb-2 opacity-25"></i>
                        Sin órdenes de compra.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
