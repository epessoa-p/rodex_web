@extends('layouts.app')
@section('title', 'Cotizaciones')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-text me-2 text-dark"></i>Cotizaciones</h1>
            <p class="text-muted mb-0 small">Presupuestos y propuestas enviadas a clientes.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('quotes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva cotización
        </a>
        @endif
    </div>

    {{-- Filtros por estado --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex gap-1 flex-wrap align-items-center">
                <span class="text-muted small me-1">Estado:</span>
                <a href="{{ route('quotes.index', array_merge(request()->except('status'), [])) }}"
                   class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                    Todas
                </a>
                @foreach([
                    'draft'     => 'Borrador',
                    'sent'      => 'Enviada',
                    'accepted'  => 'Aceptada',
                    'rejected'  => 'Rechazada',
                    'expired'   => 'Vencida',
                    'converted' => 'Convertida',
                ] as $key => $lbl)
                <a href="{{ route('quotes.index', array_merge(request()->except('status'), ['status' => $key])) }}"
                   class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-light border' }}">
                    {{ $lbl }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Sucursal</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Válida hasta</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotes as $quote)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('quotes.show', $quote) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $quote->code }}
                                </a>
                                @else
                                <span class="fw-semibold">{{ $quote->code }}</span>
                                @endif
                            </td>
                            <td class="py-2 small">{{ $quote->client_name }}</td>
                            <td class="py-2 small text-muted">{{ $quote->branch?->name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $quote->quote_date->format('d/m/Y') }}</td>
                            <td class="py-2 small text-muted">
                                @if($quote->valid_until)
                                    {{ $quote->valid_until->format('d/m/Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold">{{ money($quote->total, null, 2) }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }} border border-{{ $quote->status_color }}-subtle" style="font-size:.72rem;">
                                    {{ $quote->status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('quotes.show', $quote) }}" class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay cotizaciones registradas.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('quotes.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primera cotización
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

    <div class="mt-4 d-flex justify-content-center">{{ $quotes->links() }}</div>

</div>
@endsection
