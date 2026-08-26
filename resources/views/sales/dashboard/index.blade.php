@extends('layouts.app')
@section('title', 'Dashboard de ventas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-graph-up-arrow me-2 text-danger"></i>Dashboard de ventas</h1>
            <p class="text-muted mb-0 small">Indicadores y análisis de ventas.</p>
        </div>
        <a href="{{ route('sales.index') }}" class="btn btn-light border">
            <i class="bi bi-receipt me-1"></i>Ir a Ventas
        </a>
    </div>

    {{-- ── FILTROS ────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tipo</label>
                    <select name="sale_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="cash" {{ request('sale_type') === 'cash' ? 'selected' : '' }}>Contado</option>
                        <option value="credit" {{ request('sale_type') === 'credit' ? 'selected' : '' }}>Crédito</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <select name="client_id" class="form-select form-select-sm">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-search me-1"></i>Filtrar</button>
                    <a href="{{ route('sales.dashboard') }}" class="btn btn-light border btn-sm"><i class="bi bi-x-lg me-1"></i>Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── KPIs ───────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-currency-dollar me-1"></i>Total vendido</div>
                <div class="fw-bold fs-5">{{ money($totalSold, null, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-cash me-1 text-success"></i>Contado</div>
                <div class="fw-bold fs-5 text-success">{{ money($totalCash, null, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-calendar2-check me-1 text-primary"></i>Crédito</div>
                <div class="fw-bold fs-5 text-primary">{{ money($totalCredit, null, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-receipt me-1"></i>N° de ventas</div>
                <div class="fw-bold fs-5">{{ $count }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-tag me-1"></i>Ticket promedio</div>
                <div class="fw-bold fs-5">{{ money($avgTicket, null, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1"><i class="bi bi-arrow-return-left me-1 text-danger"></i>Devoluciones</div>
                <div class="fw-bold fs-5">{{ $returnsCount }} <span class="text-muted fw-normal fs-6">({{ money($returnsAmount, null, 2) }})</span></div>
            </div></div>
        </div>
    </div>

    {{-- ── GRÁFICOS ───────────────────────────────────────────────────── --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up me-2 text-muted"></i>Ventas por día</h6></div>
                <div class="card-body p-4">
                    @if($chartData->isEmpty())
                        <div class="text-center text-muted py-5"><i class="bi bi-bar-chart fs-2 opacity-25 d-block mb-2"></i>Sin datos en el período.</div>
                    @else
                        <canvas id="chartByDay" height="110"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2 text-muted"></i>Contado vs Crédito</h6></div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    @if($totalCash + $totalCredit <= 0)
                        <div class="text-center text-muted py-5"><i class="bi bi-pie-chart fs-2 opacity-25 d-block mb-2"></i>Sin datos.</div>
                    @else
                        <canvas id="chartCashCredit" height="200"></canvas>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2 text-muted"></i>Top 5 productos</h6></div>
                <div class="card-body p-4">
                    @if($topProducts->isEmpty())
                        <div class="text-center text-muted py-4"><i class="bi bi-box fs-2 opacity-25 d-block mb-2"></i>Sin ventas de productos.</div>
                    @else
                        <canvas id="chartTopProducts" height="160"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-muted"></i>Top 5 clientes</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <tbody>
                            @forelse($topClients as $tc)
                            <tr>
                                <td class="ps-4 py-2">{{ $tc['name'] }}<div class="text-muted small">{{ $tc['ventas'] }} venta(s)</div></td>
                                <td class="py-2 text-end fw-semibold pe-4">{{ money($tc['total'], null, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-center py-4 text-muted small">Sin datos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── VENTAS RECIENTES ───────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Ventas recientes</h6>
            <a href="{{ route('sales.index') }}" class="small text-decoration-none">Ver todas</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Tipo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                        <tr class="border-bottom border-light {{ $sale->status === 'cancelled' ? 'opacity-50' : '' }}">
                            <td class="ps-4 py-2 small text-muted">{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td class="py-2">
                                <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-semibold text-dark">{{ $sale->code }}</a>
                            </td>
                            <td class="py-2 small">{{ $sale->client_name }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $sale->sale_type_color }}-subtle text-{{ $sale->sale_type_color }} border border-{{ $sale->sale_type_color }}-subtle" style="font-size:.68rem;">{{ $sale->sale_type_label }}</span>
                            </td>
                            <td class="py-2 text-end fw-semibold pe-4">{{ money($sale->total, null, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-receipt fs-2 opacity-25 d-block mb-2"></i>No hay ventas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const money = (v) => window.money(v);

    // Ventas por día
    const byDayEl = document.getElementById('chartByDay');
    if (byDayEl) {
        new Chart(byDayEl, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Ventas',
                    data: @json($chartData),
                    borderColor: '#22242e',
                    backgroundColor: 'rgba(34,36,46,.08)',
                    fill: true, tension: .3, pointRadius: 3,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => money(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => money(v) } } }
            }
        });
    }

    // Contado vs Crédito
    const ccEl = document.getElementById('chartCashCredit');
    if (ccEl) {
        new Chart(ccEl, {
            type: 'doughnut',
            data: {
                labels: ['Contado', 'Crédito'],
                datasets: [{ data: [{{ (float) $totalCash }}, {{ (float) $totalCredit }}], backgroundColor: ['#198754', '#0d6efd'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.label + ': ' + money(c.parsed) } } } }
        });
    }

    // Top productos
    const tpEl = document.getElementById('chartTopProducts');
    if (tpEl) {
        new Chart(tpEl, {
            type: 'bar',
            data: {
                labels: @json($topProducts->pluck('name')),
                datasets: [{ label: 'Vendido', data: @json($topProducts->pluck('total')), backgroundColor: '#22242e' }]
            },
            options: {
                indexAxis: 'y', responsive: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => money(c.parsed.x) } } },
                scales: { x: { beginAtZero: true, ticks: { callback: (v) => money(v) } } }
            }
        });
    }
});
</script>
@endpush
@endsection
