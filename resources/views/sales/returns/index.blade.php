@extends('layouts.app')
@section('title', 'Devoluciones')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-arrow-return-left me-2 text-dark"></i>Devoluciones</h1>
            <p class="text-muted mb-0 small">Historial de devoluciones y reintegros.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Venta</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Método</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returns as $return)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sale-returns.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('sale-returns.show', $return) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $return->code }}
                                </a>
                                @else
                                <span class="fw-semibold">{{ $return->code }}</span>
                                @endif
                            </td>
                            <td class="py-2 small">
                                <a href="{{ route('sales.show', $return->sale) }}"
                                   class="text-decoration-none text-dark fw-semibold">
                                    {{ $return->sale->code }}
                                </a>
                            </td>
                            <td class="py-2 small text-muted">{{ $return->sale->client?->full_name ?? 'Cliente general' }}</td>
                            <td class="py-2 small text-muted">{{ $return->return_date->format('d/m/Y') }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $return->refund_method_color }}-subtle text-{{ $return->refund_method_color }} border border-{{ $return->refund_method_color }}-subtle" style="font-size:.72rem;">
                                    {{ $return->refund_method_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end fw-semibold text-danger">${{ number_format($return->total, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sale-returns.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('sale-returns.show', $return) }}" class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-return-left fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay devoluciones registradas.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $returns->links() }}</div>

</div>
@endsection
