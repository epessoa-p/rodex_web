{{-- Contenido de pestañas de Estadísticas (se reemplaza vía AJAX al cambiar el período) --}}

{{-- ── RESUMEN ──────────────────────────────────────────── --}}
<div class="tab-pane fade show active" id="p-resumen" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['resumen']['kpis']])
    @include('statistics.partials.insights', ['items' => $stats['resumen']['insights']])
</div>

{{-- ── VENTAS ───────────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-ventas" role="tabpanel">
    @php $cmp = $stats['ventas']['comparison']; @endphp

    {{-- Comparativa: total y ganancia vs período anterior comparable --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1">Total ventas</div>
                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                    <span class="fw-bold fs-5">Bs. {{ number_format($cmp['totalCur'], 2) }}</span>
                    <span class="badge {{ $cmp['totalPct'] >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}" style="font-size:.7rem;">
                        <i class="bi bi-arrow-{{ $cmp['totalPct'] >= 0 ? 'up' : 'down' }}-right me-1"></i>{{ $cmp['totalPct'] }}%
                    </span>
                </div>
                <div class="text-muted" style="font-size:.72rem;">{{ $cmp['note'] }}</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100"><div class="card-body p-3">
                <div class="text-muted small mb-1">Ganancia de las ventas</div>
                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                    <span class="fw-bold fs-5">Bs. {{ number_format($cmp['profitCur'], 2) }}</span>
                    <span class="badge {{ $cmp['profitPct'] >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}" style="font-size:.7rem;">
                        <i class="bi bi-arrow-{{ $cmp['profitPct'] >= 0 ? 'up' : 'down' }}-right me-1"></i>{{ $cmp['profitPct'] }}%
                    </span>
                </div>
                <div class="text-muted" style="font-size:.72rem;">{{ $cmp['note'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-bar-chart-line me-2 text-muted"></i>Detalle de ventas</h6></div>
        <div class="card-body p-3"><div class="chart-box" style="height:300px;"><canvas data-chart="ventasComparison"></canvas></div></div>
    </div>

    @include('statistics.partials.kpis', ['kpis' => $stats['ventas']['kpis']])
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Tendencia de ventas</h6></div><div class="card-body p-3"><div class="chart-box" style="height:220px;"><canvas data-chart="ventasTrend"></canvas></div></div></div>
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-box-seam me-2 text-muted"></i>Top productos del período</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="ventasTop"></canvas></div></div></div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Contado vs Crédito</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="ventasCashCredit"></canvas></div></div></div>
        </div>
        <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['ventas']['insights']])</div>
    </div>
</div>

{{-- ── PERSONAL ─────────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-personal" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['personal']['kpis']])
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-bar-chart me-2 text-muted"></i>Ventas por vendedor</h6></div><div class="card-body p-3"><div class="chart-box" style="height:260px;"><canvas data-chart="personal"></canvas></div></div></div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-trophy me-2 text-muted"></i>Ranking</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0" style="font-size:.8rem;">
                        <tbody>
                        @forelse($stats['personal']['ranking'] as $i => $r)
                        <tr><td class="ps-3">{{ $i + 1 }}. {{ $r['name'] }}<div class="text-muted" style="font-size:.7rem;">{{ $r['count'] }} ventas · {{ $r['pct'] }}%</div></td><td class="text-end pe-3 fw-semibold">Bs. {{ number_format($r['total'], 2) }}</td></tr>
                        @empty
                        <tr><td class="text-center py-3 text-muted small">Sin datos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['personal']['insights']])</div>
    </div>
</div>

{{-- ── CLIENTES ─────────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-clientes" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['clientes']['kpis']])
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Clientes nuevos</h6></div><div class="card-body p-3"><div class="chart-box" style="height:220px;"><canvas data-chart="clientesNew"></canvas></div></div></div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-star me-2 text-muted"></i>Top compradores</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="clientesTop"></canvas></div></div></div>
        </div>
        <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['clientes']['insights']])</div>
    </div>
</div>

{{-- ── COMPRAS ──────────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-compras" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['compras']['kpis']])
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-graph-up me-2 text-muted"></i>Compras (tendencia)</h6></div><div class="card-body p-3"><div class="chart-box" style="height:220px;"><canvas data-chart="comprasTrend"></canvas></div></div></div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-truck me-2 text-muted"></i>Top proveedores</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="comprasTop"></canvas></div></div></div>
        </div>
        <div class="col-12">@include('statistics.partials.insights', ['items' => $stats['compras']['insights']])</div>
    </div>
</div>

{{-- ── INVENTARIO ───────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-inventario" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['inventario']['kpis']])
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Estado del stock</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="inventario"></canvas></div></div></div>
        </div>
        <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['inventario']['insights']])</div>
    </div>
</div>

{{-- ── TALLER ───────────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-taller" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['taller']['kpis']])
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Órdenes por estado</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="taller"></canvas></div></div></div>
        </div>
        <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['taller']['insights']])</div>
    </div>
</div>

{{-- ── ALQUILERES ───────────────────────────────────────── --}}
<div class="tab-pane fade" id="p-alquileres" role="tabpanel">
    @include('statistics.partials.kpis', ['kpis' => $stats['alquileres']['kpis']])
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm"><div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold small"><i class="bi bi-pie-chart me-2 text-muted"></i>Ocupación de flota</h6></div><div class="card-body p-3"><div class="chart-box" style="height:240px;"><canvas data-chart="alquileres"></canvas></div></div></div>
        </div>
        <div class="col-lg-7">@include('statistics.partials.insights', ['items' => $stats['alquileres']['insights']])</div>
    </div>
</div>

{{-- Datos de los gráficos para el período actual (los lee el JS tras cada swap) --}}
<script type="application/json" id="statChartData">@json($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
