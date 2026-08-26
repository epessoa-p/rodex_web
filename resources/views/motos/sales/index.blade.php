@extends('layouts.app')
@section('title', 'Ventas de Motos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cart-check me-2 text-danger"></i>Ventas de Motos
                @unless($canAllRecords)
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle align-middle ms-1" style="font-size:.6rem;"><i class="bi bi-person-check me-1"></i>Solo las mías</span>
                @endunless
            </h1>
            <p class="text-muted mb-0 small">Registro de ventas de unidades — contado y crédito.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-sales.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('moto-sales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva venta
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Tipo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado pago</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('moto-sales.show', $sale) }}"
                                   class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">
                                    {{ $sale->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $sale->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $sale->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2">
                                @if($sale->sale_type === 'credit')
                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.68rem;">
                                    <i class="bi bi-credit-card me-1"></i>Crédito
                                </span>
                                @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.68rem;">
                                    <i class="bi bi-cash me-1"></i>Contado
                                </span>
                                @endif
                            </td>
                            <td class="py-2 small text-muted">{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td class="py-2 text-end fw-semibold">{{ money($sale->total, null, 2) }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $sale->payment_status_color }}-subtle text-{{ $sale->payment_status_color }} border border-{{ $sale->payment_status_color }}-subtle" style="font-size:.68rem;">
                                    {{ $sale->payment_status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('moto-sales.show', $sale) }}" class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                                     style="width:64px;height:64px;">
                                    <i class="bi bi-cart-check fs-2 opacity-25"></i>
                                </div>
                                <p class="fw-semibold mb-1">Sin ventas registradas</p>
                                <small>Aún no hay ventas de motos registradas.</small>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-sales.create', auth()->user()->getCurrentCompany()))
                                <div class="mt-3">
                                    <a href="{{ route('moto-sales.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1"></i>Registrar primera venta
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $sales->links() }}</div>

</div>
@endsection
