@extends('layouts.app')
@section('title', 'Ventas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5">
                <i class="bi bi-receipt me-2 text-danger"></i>Ventas
                @unless($canAllSales)
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle align-middle ms-1" style="font-size:.6rem;">
                    <i class="bi bi-person-check me-1"></i>Solo mis ventas
                </span>
                @endunless
            </h1>
            <p class="text-muted mb-0 small">Registro de ventas realizadas.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('pos.access', auth()->user()->getCurrentCompany()))
            <a href="{{ route('pos.index') }}" class="btn btn-sm btn-light border">
                <i class="bi bi-cart3 me-1"></i>POS
            </a>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.create', auth()->user()->getCurrentCompany()))
            <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nueva venta
            </a>
            @endif
        </div>
    </div>

    {{-- Sucursales (pills) --}}
    @if($branches->count())
    <div class="d-flex align-items-center gap-2 flex-wrap mb-2 filter-zone">
        @if($canAllBranches)
        <a href="{{ route('sales.index', array_merge(request()->except('branch'), [])) }}"
           class="wh-pill btn btn-sm rounded-pill px-3 {{ (!$activeBranch || $activeBranch === 'all') ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap me-1"></i>Todos
        </a>
        @else
        <span class="wh-pill btn btn-sm rounded-pill px-3 disabled" aria-disabled="true"
              title="No tienes permiso para ver todas las sucursales" style="pointer-events:none;opacity:.55;">
            <i class="bi bi-grid-3x3-gap me-1"></i>Todos
        </span>
        @endif
        @foreach($branches as $b)
        <a href="{{ route('sales.index', array_merge(request()->except('branch'), ['branch' => $b->id])) }}"
           class="wh-pill btn btn-sm rounded-pill px-3 {{ (string)$activeBranch === (string)$b->id ? 'active' : '' }}">
            <i class="bi bi-building me-1"></i>{{ $b->name }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center filter-zone">
                {{-- Preservar los filtros de tipo enlace al enviar el filtro de fecha --}}
                @foreach(['sale_type', 'payment_status', 'returns', 'branch'] as $keep)
                    @if(request($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
                @endforeach
                <div class="d-flex gap-1 flex-wrap align-items-center">
                    <span class="text-muted small me-1">Tipo:</span>
                    <a href="{{ route('sales.index', array_merge(request()->except('sale_type'), [])) }}"
                       class="btn btn-sm {{ !request('sale_type') ? 'btn-primary' : 'btn-light border' }}">
                        Todas
                    </a>
                    <a href="{{ route('sales.index', array_merge(request()->except('sale_type'), ['sale_type' => 'cash'])) }}"
                       class="btn btn-sm {{ request('sale_type') === 'cash' ? 'btn-primary' : 'btn-light border' }}">
                        Contado
                    </a>
                    <a href="{{ route('sales.index', array_merge(request()->except('sale_type'), ['sale_type' => 'credit'])) }}"
                       class="btn btn-sm {{ request('sale_type') === 'credit' ? 'btn-primary' : 'btn-light border' }}">
                        Crédito
                    </a>
                </div>
                <div class="d-flex gap-1 flex-wrap align-items-center ms-2">
                    <span class="text-muted small me-1">Pago:</span>
                    <a href="{{ route('sales.index', array_merge(request()->except('payment_status'), [])) }}"
                       class="btn btn-sm {{ !request('payment_status') ? 'btn-primary' : 'btn-light border' }}">
                        Todos
                    </a>
                    @foreach(['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'] as $key => $lbl)
                    <a href="{{ route('sales.index', array_merge(request()->except('payment_status'), ['payment_status' => $key])) }}"
                       class="btn btn-sm {{ request('payment_status') === $key ? 'btn-primary' : 'btn-light border' }}">
                        {{ $lbl }}
                    </a>
                    @endforeach
                </div>
                <div class="d-flex gap-1 flex-wrap align-items-center ms-2">
                    <span class="text-muted small me-1">Devolución:</span>
                    <a href="{{ route('sales.index', array_merge(request()->except('returns'), [])) }}"
                       class="btn btn-sm {{ !request('returns') ? 'btn-primary' : 'btn-light border' }}">
                        Todas
                    </a>
                    @foreach(['con' => 'Con devolución', 'sin' => 'Sin devolución'] as $key => $lbl)
                    <a href="{{ route('sales.index', array_merge(request()->except('returns'), ['returns' => $key])) }}"
                       class="btn btn-sm {{ request('returns') === $key ? 'btn-primary' : 'btn-light border' }}">
                        {{ $lbl }}
                    </a>
                    @endforeach
                </div>
                <div class="d-flex gap-1 flex-wrap align-items-center ms-2">
                    <span class="text-muted small me-1">Fecha:</span>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" style="width:auto;" aria-label="Desde">
                    <span class="text-muted small">a</span>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" style="width:auto;" aria-label="Hasta">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                    <a href="{{ route('sales.index', array_merge(request()->except(['date_from', 'date_to']), ['date_from' => $today, 'date_to' => $today])) }}"
                       class="btn btn-sm btn-light border" title="Ver solo hoy">Hoy</a>
                    <a href="{{ route('sales.index', array_merge(request()->except(['date_from', 'date_to']), ['date_from' => '', 'date_to' => ''])) }}"
                       class="btn btn-sm btn-light border" title="Quitar filtro de fecha">Todas</a>
                </div>
            </form>
        </div>
    </div>

    @php $canViewSale = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.view', auth()->user()->getCurrentCompany()); @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.8rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Código</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Cliente</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Sucursal</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Registró</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Tipo</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.68rem;">Fecha</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.68rem;">Total</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.68rem;">Pagado</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.68rem;">Saldo</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase pe-4" style="letter-spacing:.04em;font-size:.68rem;">Estado pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr class="border-bottom border-light {{ $sale->status === 'cancelled' ? 'opacity-60' : '' }} {{ $canViewSale ? 'sale-row' : '' }}"
                            @if($canViewSale) onclick="window.location='{{ route('sales.show', $sale) }}'" @endif>
                            <td class="ps-4 py-1">
                                <span class="fw-semibold {{ $sale->status === 'cancelled' ? 'text-muted text-decoration-line-through' : 'text-dark' }}">{{ $sale->code }}</span>
                                @if($sale->status === 'cancelled')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:.62rem;">Anulada</span>
                                @endif
                            </td>
                            <td class="py-1 small">{{ $sale->client_name }}</td>
                            <td class="py-1 small text-muted">{{ $sale->branch?->name ?? '—' }}</td>
                            <td class="py-1 small text-muted">{{ $sale->createdBy?->name ?? '—' }}</td>
                            <td class="py-1">
                                <span class="badge bg-{{ $sale->sale_type_color }}-subtle text-{{ $sale->sale_type_color }} border border-{{ $sale->sale_type_color }}-subtle" style="font-size:.66rem;">
                                    {{ $sale->sale_type_label }}
                                </span>
                            </td>
                            <td class="py-1 small text-muted">
                                {{ $sale->sale_date->format('d/m/Y') }}
                                <span class="d-block text-muted" style="font-size:.7rem;"><i class="bi bi-clock me-1"></i>{{ $sale->sale_date->format('H:i') }}</span>
                            </td>
                            <td class="py-1 text-end fw-semibold">${{ number_format($sale->total, 2) }}</td>
                            <td class="py-1 text-end small text-success">${{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="py-1 text-end fw-semibold {{ $sale->balance > 0 ? 'text-danger' : 'text-muted' }}">${{ number_format($sale->balance, 2) }}</td>
                            <td class="py-1 pe-4">
                                <span class="badge bg-{{ $sale->payment_status_color }}-subtle text-{{ $sale->payment_status_color }} border border-{{ $sale->payment_status_color }}-subtle" style="font-size:.66rem;">
                                    {{ $sale->payment_status_label }}
                                </span>
                                @if($sale->returns_count > 0)
                                <span class="badge bg-dark text-white" style="font-size:.64rem;" title="Tiene devoluciones">
                                    <i class="bi bi-arrow-return-left"></i> Dev.
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay ventas registradas.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('pos.index') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-cart3 me-1"></i>Ir al POS
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

    <div class="mt-3 d-flex justify-content-center">{{ $sales->links() }}</div>

</div>

@push('styles')
<style>
/* Cabecera de filtros compacta */
.filter-zone .btn {
    font-size: .72rem;
    padding: .2rem .6rem;
    line-height: 1.35;
}
.filter-zone .wh-pill {
    padding: .2rem .75rem;
}
.filter-zone span.small {
    font-size: .7rem !important;
}
.wh-pill {
    background: #fff;
    border: 1px solid #e3e3e3;
    color: #555;
    font-weight: 500;
    transition: all .15s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.wh-pill:hover {
    border-color: var(--brand-black, #0a0a0a);
    color: var(--brand-black, #0a0a0a);
    text-decoration: none;
}
.wh-pill.active {
    background: var(--brand-black, #0a0a0a);
    border-color: var(--brand-black, #0a0a0a);
    color: #fff;
}
/* Filas clicables → abren el detalle de la venta */
.sale-row { cursor: pointer; }
.sale-row:hover { background: #faf7f7; }
</style>
@endpush
@endsection
