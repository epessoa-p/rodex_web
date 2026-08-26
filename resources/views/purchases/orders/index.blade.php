@extends('layouts.app')
@section('title', 'Órdenes de compra')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-text me-2 text-danger"></i>Órdenes de compra</h1>
            <p class="text-muted mb-0 small">Gestión de pedidos a proveedores.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva orden
        </a>
        @endif
    </div>

    {{-- Filtro estado --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('purchase-orders.index') }}"
                       class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                        Todos
                    </a>
                    @foreach(['draft' => ['label' => 'Borrador', 'color' => 'secondary'], 'sent' => ['label' => 'Enviada', 'color' => 'info'], 'partial' => ['label' => 'Parcial', 'color' => 'warning'], 'received' => ['label' => 'Recibida', 'color' => 'success'], 'cancelled' => ['label' => 'Anulada', 'color' => 'danger']] as $key => $info)
                    <a href="{{ route('purchase-orders.index', ['status' => $key]) }}"
                       class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-light border' }}">
                        {{ $info['label'] }}
                    </a>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">OC</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Sucursal</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Fecha</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-center" style="letter-spacing:.04em">Items</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Total</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <a href="{{ route('purchase-orders.show', $order) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td class="py-3 small">{{ $order->supplier->name }}</td>
                            <td class="py-3 small text-muted">{{ $order->branch?->name ?: '—' }}</td>
                            <td class="py-3 small text-muted">{{ $order->order_date->format('d/m/Y') }}</td>
                            <td class="py-3">
                                <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-light text-muted border">{{ $order->items_count }}</span>
                            </td>
                            <td class="py-3 text-end fw-semibold">{{ money($order->total, null, 2) }}</td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-light border me-1" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.edit', auth()->user()->getCurrentCompany()))
                                @if(in_array($order->status ?? '', ['draft', 'sent']))
                                <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-sm btn-light border" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay órdenes de compra.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primera orden
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $orders->links() }}</div>

</div>
@endsection
