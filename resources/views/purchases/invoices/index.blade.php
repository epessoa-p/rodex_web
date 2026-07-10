@extends('layouts.app')
@section('title', 'Facturas de compra')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-receipt me-2 text-danger"></i>Facturas de compra</h1>
            <p class="text-muted mb-0 small">Registro de compras e historial de pagos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva compra
        </a>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <div class="d-flex gap-1 flex-wrap">
                    <a href="{{ route('purchases.index') }}"
                       class="btn btn-sm {{ !request('payment_status') ? 'btn-primary' : 'btn-light border' }}">
                        Todas
                    </a>
                    @foreach(['pending' => ['label' => 'Pendiente', 'color' => 'warning'], 'partial' => ['label' => 'Parcial', 'color' => 'info'], 'paid' => ['label' => 'Pagada', 'color' => 'success']] as $key => $info)
                    <a href="{{ route('purchases.index', ['payment_status' => $key]) }}"
                       class="btn btn-sm {{ request('payment_status') === $key ? 'btn-primary' : 'btn-light border' }}">
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
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Código</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">N° Factura</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Fecha</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Total</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Pagado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em">Saldo</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <a href="{{ route('purchases.show', $purchase) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $purchase->code }}
                                </a>
                            </td>
                            <td class="py-3 small">{{ $purchase->supplier->name }}</td>
                            <td class="py-3 small text-muted">{{ $purchase->invoice_number ?: '—' }}</td>
                            <td class="py-3 small text-muted">{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td class="py-3 text-end fw-semibold small">${{ number_format($purchase->total, 2) }}</td>
                            <td class="py-3 text-end small text-success">${{ number_format($purchase->paid_amount, 2) }}</td>
                            <td class="py-3 text-end fw-bold small {{ $purchase->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                ${{ number_format($purchase->balance, 2) }}
                            </td>
                            <td class="py-3">
                                <span class="badge bg-{{ $purchase->payment_status_color }}-subtle text-{{ $purchase->payment_status_color }} border border-{{ $purchase->payment_status_color }}-subtle">
                                    {{ $purchase->payment_status_label }}
                                </span>
                            </td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-light border me-1" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar factura {{ addslashes($purchase->code) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light border text-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay facturas registradas.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primera factura
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

    <div class="mt-4 d-flex justify-content-center">{{ $purchases->links() }}</div>

</div>
@endsection
