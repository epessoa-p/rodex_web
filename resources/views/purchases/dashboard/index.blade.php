@extends('layouts.app')
@section('title', 'Dashboard de Compras')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cart3 me-2 text-danger"></i>Compras</h1>
            <p class="text-muted mb-0 small">Resumen del módulo de compras y cuentas por pagar.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.create', auth()->user()->getCurrentCompany()))
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-light border">
                <i class="bi bi-file-earmark-plus me-1"></i>Nueva OC
            </a>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.create', auth()->user()->getCurrentCompany()))
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="bi bi-receipt me-1"></i>Nueva factura
            </a>
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(230,57,70,.08)">
                            <i class="bi bi-cart-check fs-5 text-danger"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Este mes</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">${{ number_format($purchasedThisMonth, 2) }}</div>
                    <div class="text-muted small">Compras del mes</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(220,53,69,.08)">
                            <i class="bi bi-exclamation-circle fs-5" style="color:#dc3545"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small">Por pagar</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1 text-danger">${{ number_format($totalPayable, 2) }}</div>
                    <div class="text-muted small">Total por pagar</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(25,135,84,.08)">
                            <i class="bi bi-safe2 fs-5 text-success"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">Disponible</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1 text-success">${{ number_format($treasuryBalance, 2) }}</div>
                    <div class="text-muted small">Saldo en tesorería</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(13,110,253,.08)">
                            <i class="bi bi-hourglass-split fs-5 text-primary"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Pendientes</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">{{ $pendingOrdersCount }}</div>
                    <div class="text-muted small">Órdenes pendientes</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Compras recientes --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2 text-muted"></i>Compras recientes</h6>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.view', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-light border">Ver todas</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Código</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Fecha</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em">Total</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase pe-4" style="letter-spacing:.04em">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPurchases as $purchase)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <a href="{{ route('purchases.show', $purchase) }}" class="text-decoration-none fw-semibold text-dark small">
                                            {{ $purchase->code }}
                                        </a>
                                    </td>
                                    <td class="py-3 small">{{ $purchase->supplier->name }}</td>
                                    <td class="py-3 small text-muted">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                                    <td class="py-3 text-end fw-semibold small">${{ number_format($purchase->total, 2) }}</td>
                                    <td class="py-3 pe-4">
                                        <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle">
                                            {{ $purchase->payment_status_label }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25"></i>
                                        Sin compras registradas.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Órdenes pendientes --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-hourglass-split me-2 text-muted"></i>Órdenes pendientes</h6>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.view', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-light border">Ver todas</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">OC</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Estado</th>
                                    <th class="py-3 small fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingOrders as $order)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <a href="{{ route('purchase-orders.show', $order) }}" class="text-decoration-none fw-semibold text-dark small">
                                            {{ $order->code }}
                                        </a>
                                    </td>
                                    <td class="py-3 small">{{ $order->supplier->name }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-end fw-semibold small pe-4">${{ number_format($order->total, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-check2-all fs-2 d-block mb-2 opacity-25"></i>
                                        Sin órdenes pendientes.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna derecha --}}
        <div class="col-lg-4">

            {{-- Cuentas de tesorería --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bank me-2 text-muted"></i>Tesorería</h6>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.view', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('treasury.index') }}" class="btn btn-sm btn-light border">Ver</a>
                    @endif
                </div>
                <div class="card-body p-3">
                    @forelse($accounts as $account)
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border mb-2
                                {{ $loop->last ? 'mb-0' : '' }}"
                         style="background:#fafafa">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                 style="width:34px;height:34px;background:{{ $account->type === 'bank' ? 'rgba(13,110,253,.08)' : 'rgba(25,135,84,.08)' }}">
                                <i class="bi {{ $account->type === 'bank' ? 'bi-bank text-primary' : 'bi-cash-coin text-success' }} small"></i>
                            </div>
                            <div>
                                <div class="fw-semibold small">{{ $account->name }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $account->type_label }}</div>
                            </div>
                        </div>
                        <div class="fw-bold small {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($account->balance, 2) }}
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-safe2 d-block fs-3 mb-1 opacity-25"></i>
                        Sin cuentas configuradas.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Top proveedores --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2 text-muted"></i>Top proveedores</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($topSuppliers as $i => $supplier)
                    <div class="d-flex align-items-center gap-3 mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                             style="width:32px;height:32px;font-size:.78rem;background:{{ $i === 0 ? '#f59e0b' : ($i === 1 ? '#9ca3af' : ($i === 2 ? '#b45309' : '#6b7280')) }}">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate small">{{ $supplier->name }}</div>
                            <div class="progress mt-1" style="height:4px;border-radius:2px">
                                @php $maxVal = $topSuppliers->first()->total_purchased ?? 1; @endphp
                                <div class="progress-bar bg-danger" style="width:{{ $maxVal > 0 ? ($supplier->total_purchased / $maxVal * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="fw-semibold small text-muted flex-shrink-0">${{ number_format($supplier->total_purchased, 0) }}</div>
                    </div>
                    @empty
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-people d-block fs-3 mb-1 opacity-25"></i>
                        Sin datos de proveedores.
                    </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
