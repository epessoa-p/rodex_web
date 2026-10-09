@extends('layouts.app')
@section('title', 'Ganancias')
@section('page')
@php
    $t  = $report['totals'];
    $s  = $report['sales'];
    $w  = $report['workshop'];
    $q  = $report['quick'];
    $presets = [
        'today'      => 'Hoy',
        'this_week'  => 'Esta semana',
        'this_month' => 'Este mes',
        'last_month' => 'Mes anterior',
        'custom'     => 'Rango',
    ];
    $scopes = ['all' => 'Ventas y taller', 'sales' => 'Solo ventas', 'workshop' => 'Solo taller'];
    // Conserva los filtros al cambiar uno.
    $keep = fn (array $over = []) => route('profit-report.index', array_filter(array_merge([
        'preset'      => $preset,
        'from'        => $preset === 'custom' ? $report['from'] : null,
        'to'          => $preset === 'custom' ? $report['to'] : null,
        'branch_id'   => $branchId,
        'scope'       => $scope,
        'merge_quick' => $merge ? 1 : null,
    ], $over), fn ($v) => $v !== null && $v !== ''));
    $marginClass = fn ($m) => $m < 0 ? 'text-danger' : ($m < 15 ? 'text-warning' : 'text-success');
    $periodLabel = $report['from'] === $report['to']
        ? \Illuminate\Support\Carbon::parse($report['from'])->translatedFormat('l d/m/Y')
        : \Illuminate\Support\Carbon::parse($report['from'])->format('d/m/Y') . ' – ' . \Illuminate\Support\Carbon::parse($report['to'])->format('d/m/Y');
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Ganancias</h1>
            <p class="text-muted mb-0 small">Precio de venta menos costo{{ $scope !== 'sales' ? ' y comisión del mecánico' : '' }} · <span class="text-capitalize">{{ $periodLabel }}</span></p>
        </div>
        <div class="d-flex align-items-end gap-2 flex-wrap no-print">
            <div class="btn-group btn-group-sm" role="group" aria-label="Período">
                @foreach($presets as $key => $label)
                    @if($key === 'custom')
                        <button type="button" class="btn {{ $preset === 'custom' ? 'btn-primary' : 'btn-outline-primary' }}"
                                onclick="document.getElementById('prRange').classList.toggle('d-none')">
                            <i class="bi bi-calendar-range me-1"></i>{{ $label }}
                        </button>
                    @else
                        <a href="{{ $keep(['preset' => $key, 'from' => null, 'to' => null]) }}"
                           class="btn {{ $preset === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            <form method="GET" id="prRange" class="d-flex align-items-end gap-2 {{ $preset === 'custom' ? '' : 'd-none' }}">
                <input type="hidden" name="preset" value="custom">
                <input type="hidden" name="scope" value="{{ $scope }}">
                @if($branchId)<input type="hidden" name="branch_id" value="{{ $branchId }}">@endif
                @if($merge)<input type="hidden" name="merge_quick" value="1">@endif
                <div>
                    <label class="form-label small mb-0" for="prFrom">Desde</label>
                    <input type="date" id="prFrom" name="from" value="{{ $report['from'] }}" class="form-control form-control-sm border" data-no-uppercase>
                </div>
                <div>
                    <label class="form-label small mb-0" for="prTo">Hasta</label>
                    <input type="date" id="prTo" name="to" value="{{ $report['to'] }}" class="form-control form-control-sm border" data-no-uppercase>
                </div>
                <button class="btn btn-sm btn-primary" title="Aplicar rango"><i class="bi bi-funnel"></i></button>
            </form>
        </div>
    </div>

    {{-- Qué incluir --}}
    <div class="card border-0 shadow-sm mb-3 no-print">
        <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-3">
            <div class="btn-group btn-group-sm" role="group" aria-label="Qué incluir">
                @foreach($scopes as $key => $label)
                    <a href="{{ $keep(['scope' => $key]) }}" class="btn {{ $scope === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
            @if($branches->count() > 1)
            <select class="form-select form-select-sm w-auto" aria-label="Sucursal"
                    onchange="location.href=this.value">
                <option value="{{ $keep(['branch_id' => null]) }}">Todas las sucursales</option>
                @foreach($branches as $b)
                    <option value="{{ $keep(['branch_id' => $b->id]) }}" @selected($branchId === $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
            @endif
            @if($scope !== 'workshop')
            <div class="form-check form-switch mb-0 ms-md-auto">
                <input class="form-check-input" type="checkbox" role="switch" id="prMerge" @checked($merge)
                       onchange="location.href=this.checked ? '{{ $keep(['merge_quick' => 1]) }}' : '{{ $keep(['merge_quick' => null]) }}'">
                <label class="form-check-label small" for="prMerge">Unir ventas rápidas</label>
            </div>
            @endif
        </div>
    </div>

    {{-- Totales --}}
    <div class="row g-3 mb-3">
        @foreach([
            ['Ingresos', $t['revenue'], 'bi-cash-stack', 'primary', 'Ventas netas de descuentos y devoluciones'],
            ['Costo', $t['cost'] + $t['commission'], 'bi-box-seam', 'secondary', $t['commission'] > 0 ? 'Productos ' . money($t['cost']) . ' + comisión ' . money($t['commission']) : 'Costo de los productos vendidos'],
            ['Ganancia', $t['profit'], 'bi-graph-up-arrow', $t['profit'] < 0 ? 'danger' : 'success', 'Ingresos − costo'],
        ] as [$label, $value, $icon, $color, $hint])
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi {{ $icon }} me-1 text-{{ $color }}"></i>{{ $label }}</div>
                    <div class="fw-bold fs-4 text-{{ $color === 'secondary' ? 'body' : $color }}">{{ money($value) }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $hint }}</div>
                </div>
            </div>
        </div>
        @endforeach
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-percent me-1"></i>Margen</div>
                    <div class="fw-bold fs-4 {{ $marginClass($t['margin']) }}">{{ number_format($t['margin'], 1) }}%</div>
                    <div class="text-muted" style="font-size:.75rem">De cada {{ money(100) }} vendidos quedan {{ money($t['margin']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Avisos --}}
    @if($report['estimated_lines'] > 0)
    <div class="alert alert-warning border-0 small py-2">
        <i class="bi bi-info-circle me-1"></i>
        {{ $report['estimated_lines'] }} {{ $report['estimated_lines'] === 1 ? 'línea es anterior' : 'líneas son anteriores' }} a que el sistema guardara el costo al vender:
        para {{ $report['estimated_lines'] === 1 ? 'ella' : 'ellas' }} se usa el costo actual del producto (ganancia estimada).
    </div>
    @endif
    @if($scope !== 'workshop' && $q['count'] > 0)
    <div class="alert {{ $merge ? 'alert-warning' : 'alert-light border' }} small py-2">
        <i class="bi bi-lightning-charge me-1"></i>
        @if($merge)
            Incluye {{ money($q['revenue']) }} en {{ $q['count'] }} {{ $q['count'] === 1 ? 'venta rápida' : 'ventas rápidas' }} sin costo conocido: cuentan como ganancia completa y el margen sale más alto de lo real.
        @else
            Aparte: {{ money($q['revenue']) }} en {{ $q['count'] }} {{ $q['count'] === 1 ? 'venta rápida' : 'ventas rápidas' }} (productos no cargados, sin costo conocido). No se suman a la ganancia.
        @endif
    </div>
    @endif

    <div class="row g-3">
        {{-- Ventas y taller --}}
        @if($s['enabled'])
        <div class="{{ $w['enabled'] ? 'col-lg-6' : 'col-12' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cart3 me-2 text-primary"></i>Ventas</h6>
                    <span class="text-muted small">{{ $s['count'] }} {{ $s['count'] === 1 ? 'venta' : 'ventas' }}</span>
                </div>
                <div class="card-body px-4">
                    <div class="d-flex justify-content-between small mb-1"><span>Ingresos</span><span>{{ money($s['revenue']) }}</span></div>
                    <div class="d-flex justify-content-between small mb-1 text-muted"><span>Costo de productos</span><span>− {{ money($s['cost']) }}</span></div>
                    @if($s['returns_revenue'] > 0)
                    <div class="d-flex justify-content-between small mb-1 text-muted"><span>Devoluciones (ya restadas)</span><span>{{ money($s['returns_revenue']) }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                        <span>Ganancia</span>
                        <span class="{{ $s['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($s['profit']) }} <small class="{{ $marginClass($s['margin']) }}">({{ number_format($s['margin'], 1) }}%)</small></span>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($w['enabled'])
        <div class="{{ $s['enabled'] ? 'col-lg-6' : 'col-12' }}">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-tools me-2 text-warning"></i>Taller</h6>
                    <span class="text-muted small">{{ $w['count'] }} {{ $w['count'] === 1 ? 'OT entregada' : 'OTs entregadas' }}</span>
                </div>
                <div class="card-body px-4">
                    <div class="d-flex justify-content-between small mb-1"><span>Mano de obra</span><span>{{ money($w['labor']) }}</span></div>
                    <div class="d-flex justify-content-between small mb-1"><span>Repuestos</span><span>{{ money($w['parts']) }}</span></div>
                    <div class="d-flex justify-content-between small mb-1 text-muted"><span>Costo de repuestos</span><span>− {{ money($w['parts_cost']) }}</span></div>
                    @if($w['commission_paid'] > 0)
                    <div class="d-flex justify-content-between small mb-1 text-muted"><span>Comisión mecánicos (pagada)</span><span>− {{ money($w['commission_paid']) }}</span></div>
                    @endif
                    @if($w['commission_pending'] > 0)
                    <div class="d-flex justify-content-between small mb-1 text-muted"><span>Comisión mecánicos (por pagar)</span><span>− {{ money($w['commission_pending']) }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                        <span>Ganancia</span>
                        <span class="{{ $w['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($w['profit']) }} <small class="{{ $marginClass($w['margin']) }}">({{ number_format($w['margin'], 1) }}%)</small></span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Por día --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2 text-muted"></i>Por día</h6>
                </div>
                <div class="card-body p-0">
                    @if(empty($report['by_day']))
                        <div class="text-center text-muted small py-4">Sin ventas ni OTs entregadas en el período.</div>
                    @else
                    <div class="table-responsive" style="max-height:420px">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th class="ps-4">Día</th><th class="text-end">Ingresos</th><th class="text-end">Costo</th><th class="text-end pe-4">Ganancia</th>
                            </tr></thead>
                            <tbody>
                            @foreach(array_reverse($report['by_day']) as $d)
                                <tr>
                                    <td class="ps-4 text-capitalize small">{{ \Illuminate\Support\Carbon::parse($d['date'])->translatedFormat('D d/m') }}</td>
                                    <td class="text-end small">{{ money($d['revenue']) }}</td>
                                    <td class="text-end small text-muted">{{ money($d['cost']) }}</td>
                                    <td class="text-end fw-semibold small pe-4 {{ $d['profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ money($d['profit']) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Productos --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2 text-muted"></i>Productos</h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-pr-tab="top">Más ganancia</button>
                        <button type="button" class="btn btn-outline-secondary" data-pr-tab="low">Margen bajo</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @foreach(['top' => $report['top_products'], 'low' => $report['low_margin']] as $key => $rows)
                    <div data-pr-pane="{{ $key }}" class="{{ $key === 'top' ? '' : 'd-none' }}">
                        @if(empty($rows))
                            <div class="text-center text-muted small py-4">{{ $key === 'top' ? 'Sin productos vendidos en el período.' : 'Ningún producto con margen menor a 15%.' }}</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr>
                                    <th class="ps-4">Producto</th><th class="text-end">Cant.</th><th class="text-end">Ganancia</th><th class="text-end pe-4">Margen</th>
                                </tr></thead>
                                <tbody>
                                @foreach($rows as $p)
                                    <tr>
                                        <td class="ps-4 small">{{ $p['name'] }}</td>
                                        <td class="text-end small">{{ rtrim(rtrim(number_format($p['quantity'], 2), '0'), '.') }}</td>
                                        <td class="text-end small fw-semibold {{ $p['profit'] < 0 ? 'text-danger' : '' }}">{{ money($p['profit']) }}</td>
                                        <td class="text-end small pe-4 {{ $marginClass($p['margin']) }}">{{ number_format($p['margin'], 1) }}%</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-pr-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-pr-tab]').forEach(b => b.classList.toggle('active', b === btn));
        document.querySelectorAll('[data-pr-pane]').forEach(p => p.classList.toggle('d-none', p.dataset.prPane !== btn.dataset.prTab));
    });
});
</script>
@endpush
