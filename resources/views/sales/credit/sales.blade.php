@extends('layouts.app')
@section('title', 'Ventas a Crédito')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-credit-card me-2 text-danger"></i>Ventas a Crédito</h1>
            <p class="text-muted mb-0 small">Todas las ventas financiadas con saldo pendiente o pagado.</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">

                <div class="d-flex flex-column gap-1">
                    <span class="text-muted small fw-semibold">Estado de pago</span>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('credit.sales', array_merge(request()->except('payment_status'), [])) }}"
                           class="btn btn-sm {{ !request('payment_status') ? 'btn-primary' : 'btn-light border' }}">Todos</a>
                        @foreach(['pending' => ['label' => 'Pendiente', 'color' => 'warning'], 'partial' => ['label' => 'Parcial', 'color' => 'info'], 'paid' => ['label' => 'Pagado', 'color' => 'success']] as $key => $meta)
                        <a href="{{ route('credit.sales', array_merge(request()->except('payment_status'), ['payment_status' => $key])) }}"
                           class="btn btn-sm {{ request('payment_status') === $key ? 'btn-' . $meta['color'] : 'btn-light border' }}">
                            {{ $meta['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>

                <div style="min-width:160px;">
                    <label class="form-label small fw-semibold mb-1">Categoría</label>
                    <select name="sale_category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <option value="producto" {{ request('sale_category') === 'producto' ? 'selected' : '' }}>Producto</option>
                        <option value="moto" {{ request('sale_category') === 'moto' ? 'selected' : '' }}>Moto</option>
                    </select>
                </div>

                <div style="min-width:200px;">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                @if(request()->hasAny(['payment_status','sale_category','client_id']))
                <a href="{{ route('credit.sales') }}" class="btn btn-sm btn-light border align-self-end">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
                @endif

            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Categoría</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Pagado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Saldo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $sale->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $sale->client_name }}</td>
                            <td class="py-2">
                                @if($sale->sale_category === 'moto')
                                <span class="badge bg-dark-subtle text-dark border border-dark-subtle" style="font-size:.68rem;">
                                    <i class="bi bi-bicycle me-1"></i>Moto
                                </span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.68rem;">
                                    <i class="bi bi-box-seam me-1"></i>Producto
                                </span>
                                @endif
                            </td>
                            <td class="py-2 small text-muted">{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td class="py-2 text-end small fw-semibold">${{ number_format($sale->total, 2) }}</td>
                            <td class="py-2 text-end small text-success">${{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="py-2 text-end fw-bold {{ $sale->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                ${{ number_format($sale->balance, 2) }}
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $sale->payment_status_color }}-subtle text-{{ $sale->payment_status_color }} border border-{{ $sale->payment_status_color }}-subtle" style="font-size:.68rem;">
                                    {{ $sale->payment_status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="btn btn-sm btn-light border me-1" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($sale->balance > 0)
                                <a href="{{ route('credit.cobranza', ['client_id' => $sale->client_id ?? '']) }}"
                                   class="btn btn-sm btn-primary" title="Cobrar">
                                    <i class="bi bi-cash-coin me-1"></i>Cobrar
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                                     style="width:64px;height:64px;">
                                    <i class="bi bi-credit-card fs-2 opacity-25"></i>
                                </div>
                                <p class="fw-semibold mb-1">Sin ventas a crédito</p>
                                <small>
                                    @if(request()->hasAny(['payment_status','sale_category','client_id']))
                                        No hay ventas con los filtros seleccionados.
                                        <a href="{{ route('credit.sales') }}" class="ms-1">Limpiar filtros</a>
                                    @else
                                        Aún no hay ventas a crédito registradas.
                                    @endif
                                </small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $sales->withQueryString()->links() }}</div>

</div>
@endsection
