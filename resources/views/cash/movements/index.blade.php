@extends('layouts.app')
@section('title', 'Movimientos')

@section('page')
<div class="container-fluid">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4">
                <i class="bi bi-arrow-left-right me-2 text-muted"></i>Movimientos
            </h1>
            <p class="text-muted mb-0 small">
                Resumen financiero por sucursal y período
                @if($period === 'all')
                <span class="badge bg-primary text-white ms-2 fw-normal" style="font-size:.72rem;border-radius:999px;">
                    <i class="bi bi-clock-history me-1"></i>{{ $periodLabel }}
                </span>
                @else
                <span class="badge bg-dark text-white ms-2 fw-normal" style="font-size:.72rem;border-radius:999px;">
                    {{ $periodLabel }}
                </span>
                @endif
            </p>
        </div>

        {{-- Totales históricos (fijos, no se ajustan con los filtros) --}}
        <div class="card border-0 shadow-sm" style="min-width:300px;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted lh-1 text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">
                        <i class="bi bi-clock-history d-block mb-1" style="font-size:.95rem;"></i>Histórico
                    </div>
                    <div class="vr"></div>
                    <div class="text-center">
                        <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Balance</div>
                        <div class="fw-bold {{ $allBalance >= 0 ? 'text-dark' : 'text-danger' }}" style="font-size:.92rem;">Bs. {{ number_format($allBalance, 2) }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Ingresos</div>
                        <div class="fw-bold text-success" style="font-size:.92rem;">Bs. {{ number_format($allIncome, 2) }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Egresos</div>
                        <div class="fw-bold text-danger" style="font-size:.92rem;">Bs. {{ number_format($allExpense, 2) }}</div>
                    </div>
                </div>

                {{-- Histórico por sucursal (siempre visible, sin filtros) --}}
                @if($branchHistory->count())
                <hr class="my-2">
                <div class="text-muted text-uppercase mb-1" style="font-size:.58rem;letter-spacing:.04em;">
                    <i class="bi bi-buildings me-1"></i>Por sucursal
                </div>
                <div class="d-flex flex-column gap-1">
                    @foreach($branchHistory as $bh)
                    <div class="d-flex align-items-center justify-content-between gap-3" style="font-size:.72rem;">
                        <span class="d-inline-flex align-items-center gap-1 text-truncate" style="max-width:120px;" title="{{ $bh->name }}">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $bh->color }};display:inline-block;flex-shrink:0;"></span>
                            {{ $bh->name }}
                        </span>
                        <span class="d-flex align-items-center gap-2">
                            <span class="text-success" title="Ingresos">+{{ number_format($bh->income, 2) }}</span>
                            <span class="text-danger" title="Egresos">−{{ number_format($bh->expense, 2) }}</span>
                            <span class="fw-bold {{ $bh->balance >= 0 ? 'text-dark' : 'text-danger' }}" style="min-width:74px;text-align:right;" title="Balance">Bs. {{ number_format($bh->balance, 2) }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Sucursales (tabs con color, arriba del recuadro) ──────────── --}}
    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap" id="branchTabs">
        <button type="button" class="branch-tab {{ $branch === 'all' ? 'active' : '' }}" data-branch="all">
            <i class="bi bi-grid-3x3-gap me-1"></i>Todas
        </button>
        @foreach($branches as $b)
            @php
                $bc = $b->color ?: '#0a0a0a';
                $h  = ltrim($bc, '#');
                if (strlen($h) === 3) { $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2]; }
                $lum = (0.299 * hexdec(substr($h,0,2))) + (0.587 * hexdec(substr($h,2,2))) + (0.114 * hexdec(substr($h,4,2)));
                $bt = $lum > 150 ? '#111827' : '#ffffff';
            @endphp
            <button type="button" class="branch-tab {{ (string)$branch === (string)$b->id ? 'active' : '' }}"
                    data-branch="{{ $b->id }}" style="--bc:{{ $bc }}; --bt:{{ $bt }};">
                <span class="branch-dot" style="background:{{ $bc }};"></span>{{ $b->name }}
            </button>
        @endforeach
    </div>

    {{-- ── Filter card ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" action="{{ route('cash.movements') }}" id="filterForm" data-no-spinner>
                <input type="hidden" name="period" id="periodInput" value="{{ $period }}">
                <input type="hidden" name="branch" id="branchInput" value="{{ $branch }}">

                {{-- Fila 1: período (pills) + slot contextual --}}
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    {{-- Pills de período --}}
                    <div class="d-flex gap-1 flex-wrap" id="periodPills">
                        @foreach([
                            'daily'   => ['Día', 'bi-calendar-day'],
                            'weekly'  => ['Semana', 'bi-calendar-week'],
                            'monthly' => ['Mes', 'bi-calendar-month'],
                            'all'     => ['Todo', 'bi-clock-history'],
                            'range'   => ['Rango', 'bi-calendar-range'],
                        ] as $key => $opt)
                        <button type="button" class="mov-pill {{ $period === $key ? 'active' : '' }}" data-period="{{ $key }}">
                            <i class="bi {{ $opt[1] }} me-1"></i>{{ $opt[0] }}
                        </button>
                        @endforeach
                    </div>

                    {{-- Slot contextual: Día --}}
                    <div class="period-slot align-items-center gap-1 {{ $period === 'daily' ? 'is-active' : '' }}" data-slot="daily">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="day" data-dir="-1" title="Anterior"><i class="bi bi-chevron-left"></i></button>
                        <input type="date" name="date" class="form-control form-control-sm nav-input" value="{{ $dateValue }}" data-unit="day" style="width:150px;">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="day" data-dir="1" title="Siguiente"><i class="bi bi-chevron-right"></i></button>
                    </div>

                    {{-- Slot contextual: Semana --}}
                    <div class="period-slot align-items-center gap-1 {{ $period === 'weekly' ? 'is-active' : '' }}" data-slot="weekly">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="week" data-dir="-1" title="Semana anterior"><i class="bi bi-chevron-left"></i></button>
                        <input type="week" name="week" class="form-control form-control-sm nav-input" value="{{ $weekValue }}" data-unit="week" style="width:170px;">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="week" data-dir="1" title="Semana siguiente"><i class="bi bi-chevron-right"></i></button>
                    </div>

                    {{-- Slot contextual: Mes --}}
                    <div class="period-slot align-items-center gap-1 {{ $period === 'monthly' ? 'is-active' : '' }}" data-slot="monthly">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="month" data-dir="-1" title="Mes anterior"><i class="bi bi-chevron-left"></i></button>
                        <input type="month" name="month" class="form-control form-control-sm nav-input" value="{{ $monthValue }}" data-unit="month" style="width:160px;">
                        <button type="button" class="btn btn-light border btn-sm px-2 nav-step" data-unit="month" data-dir="1" title="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
                    </div>

                    {{-- Slot contextual: Todo --}}
                    <div class="period-slot align-items-center {{ $period === 'all' ? 'is-active' : '' }}" data-slot="all">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-infinity me-1"></i>Desde el inicio hasta hoy</span>
                    </div>

                    {{-- Slot contextual: Rango --}}
                    <div class="period-slot align-items-center gap-2 {{ $period === 'range' ? 'is-active' : '' }}" data-slot="range">
                        <input type="date" name="from" class="form-control form-control-sm" value="{{ $fromDate }}" id="fromInput" style="width:150px;" placeholder="Desde">
                        <span class="text-muted small">—</span>
                        <input type="date" name="to" class="form-control form-control-sm" value="{{ $toDate }}" id="toInput" style="width:150px;" placeholder="Hasta">
                    </div>
                </div>

                {{-- Fila 2: búsqueda + filtrar --}}
                <div class="d-flex flex-wrap gap-2 align-items-end mt-2">
                    <div style="min-width:180px; flex:2 1 180px; max-width:280px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted" style="font-size:.75rem;"></i>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0"
                                   value="{{ $q }}" placeholder="Buscar concepto..."
                                   style="box-shadow:none;">
                        </div>
                    </div>
                    <div style="flex:0 0 auto;">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i>Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Main segmented tabs ───────────────────────────────────────── --}}
    <div class="mb-4">
        <ul class="nav mov-main-tabs" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="mov-tab active" id="tab-transacciones"
                        data-bs-toggle="tab" data-bs-target="#paneTransacciones"
                        type="button" role="tab" aria-controls="paneTransacciones" aria-selected="true">
                    <i class="bi bi-arrow-left-right me-1"></i>Transacciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="mov-tab" id="tab-cierres"
                        data-bs-toggle="tab" data-bs-target="#paneCierres"
                        type="button" role="tab" aria-controls="paneCierres" aria-selected="false">
                    <i class="bi bi-lock me-1"></i>Cierres de caja
                    @if($closures->total())
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1"
                              style="font-size:.65rem;">{{ $closures->total() }}</span>
                    @endif
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="mainTabContent">

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- PANE: TRANSACCIONES                                         --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="paneTransacciones" role="tabpanel">

            {{-- KPI Cards (compactas) --}}
            <div class="row g-2 mb-3">

                {{-- Balance --}}
                <div class="col-sm-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:38px;height:38px;background:rgba(10,10,10,.07);">
                                <i class="bi bi-graph-up-arrow text-dark"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted text-uppercase" style="font-size:.62rem;letter-spacing:.04em;">Balance del período</div>
                                <div class="fw-bold fs-5 lh-1 {{ $balance >= 0 ? 'text-dark' : 'text-danger' }}">Bs. {{ number_format($balance, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ventas --}}
                <div class="col-sm-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:38px;height:38px;background:rgba(22,163,74,.08);">
                                <i class="bi bi-box-arrow-in-down text-success"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted text-uppercase" style="font-size:.62rem;letter-spacing:.04em;">Ingresos</div>
                                <div class="fw-bold fs-5 lh-1 text-success">Bs. {{ number_format($ventas, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Gastos --}}
                <div class="col-sm-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width:38px;height:38px;background:rgba(225,6,0,.07);">
                                <i class="bi bi-box-arrow-up text-danger"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted text-uppercase" style="font-size:.62rem;letter-spacing:.04em;">Egresos</div>
                                <div class="fw-bold fs-5 lh-1 text-danger">Bs. {{ number_format($gastos, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Sub-tabs --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white px-4 pt-3 pb-0 border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs" id="subTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active px-3 py-2" id="subtab-ingresos"
                                    data-bs-toggle="tab" data-bs-target="#subIngresos"
                                    type="button" role="tab" aria-selected="true">
                                <i class="bi bi-box-arrow-in-down me-1 text-success" style="font-size:.82rem;"></i>
                                Ingresos
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"
                                      style="font-size:.65rem;">{{ $ingresos->total() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3 py-2" id="subtab-egresos"
                                    data-bs-toggle="tab" data-bs-target="#subEgresos"
                                    type="button" role="tab" aria-selected="false">
                                <i class="bi bi-box-arrow-up me-1 text-danger" style="font-size:.82rem;"></i>
                                Egresos
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1"
                                      style="font-size:.65rem;">{{ $egresos->total() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3 py-2" id="subtab-cobrar"
                                    data-bs-toggle="tab" data-bs-target="#subCobrar"
                                    type="button" role="tab" aria-selected="false">
                                <i class="bi bi-hourglass-split me-1 text-warning" style="font-size:.82rem;"></i>
                                Por cobrar
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1"
                                      style="font-size:.65rem;">{{ $porCobrar->total() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3 py-2" id="subtab-pagar"
                                    data-bs-toggle="tab" data-bs-target="#subPagar"
                                    type="button" role="tab" aria-selected="false">
                                <i class="bi bi-cash-stack me-1 text-danger" style="font-size:.82rem;"></i>
                                Por pagar
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1"
                                      style="font-size:.65rem;">{{ $porPagar->total() }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content">

                    {{-- ─── Sub-pane: Ingresos ──────────────────────── --}}
                    <div class="tab-pane fade show active" id="subIngresos" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                                <thead>
                                    <tr class="table-light border-bottom">
                                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;width:40px;"></th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Concepto</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Valor</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Medio de pago</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase pe-4"
                                            style="letter-spacing:.04em;font-size:.72rem;">Fecha y hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ingresos as $mov)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-4 py-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success"
                                                 style="width:28px;height:28px;font-size:.78rem;flex-shrink:0;">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-semibold text-dark">{{ $mov->description ?: '—' }}</div>
                                            @if($mov->cashRegister?->branch?->name)
                                                <div class="text-muted" style="font-size:.75rem;">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $mov->cashRegister->branch->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-2 fw-semibold text-success">
                                            Bs. {{ number_format($mov->amount, 2) }}
                                        </td>
                                        <td class="py-2 small text-muted">{{ $mov->method_label }}</td>
                                        <td class="py-2 pe-4">
                                            <div class="small">{{ $mov->movement_date->format('d/m/Y') }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $mov->movement_date->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-arrow-in-down fs-1 d-block mb-2 opacity-25"></i>
                                            <p class="mb-0 small">Sin ingresos registrados en este período.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($ingresos->hasPages())
                        <div class="px-3 py-2 border-top">{{ $ingresos->links() }}</div>
                        @endif
                    </div>

                    {{-- ─── Sub-pane: Egresos ───────────────────────── --}}
                    <div class="tab-pane fade" id="subEgresos" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                                <thead>
                                    <tr class="table-light border-bottom">
                                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;width:40px;"></th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Concepto</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Valor</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Medio de pago</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase pe-4"
                                            style="letter-spacing:.04em;font-size:.72rem;">Fecha y hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($egresos as $mov)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-4 py-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger"
                                                 style="width:28px;height:28px;font-size:.78rem;flex-shrink:0;">
                                                <i class="bi bi-box-arrow-up"></i>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-semibold text-dark">{{ $mov->description ?: '—' }}</div>
                                            @if($mov->cashRegister?->branch?->name)
                                                <div class="text-muted" style="font-size:.75rem;">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $mov->cashRegister->branch->name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-2 fw-semibold text-danger">
                                            Bs. {{ number_format($mov->amount, 2) }}
                                        </td>
                                        <td class="py-2 small text-muted">{{ $mov->method_label }}</td>
                                        <td class="py-2 pe-4">
                                            <div class="small">{{ $mov->movement_date->format('d/m/Y') }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $mov->movement_date->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-arrow-up fs-1 d-block mb-2 opacity-25"></i>
                                            <p class="mb-0 small">Sin egresos registrados en este período.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($egresos->hasPages())
                        <div class="px-3 py-2 border-top">{{ $egresos->links() }}</div>
                        @endif
                    </div>

                    {{-- ─── Sub-pane: Por cobrar ────────────────────── --}}
                    <div class="tab-pane fade" id="subCobrar" role="tabpanel">
                        @if($porCobrar->total())
                        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom"
                             style="background:rgba(245,158,11,.04);">
                            <span class="text-muted small">
                                <i class="bi bi-hourglass-split me-1 text-warning"></i>
                                {{ $porCobrar->total() }} {{ $porCobrar->total() === 1 ? 'pendiente' : 'pendientes' }}
                            </span>
                            <span class="fw-bold text-danger">
                                Total por cobrar: Bs. {{ number_format($totalCobrar, 2) }}
                            </span>
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                                <thead>
                                    <tr class="table-light border-bottom">
                                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Concepto</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Valor</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4"
                                            style="letter-spacing:.04em;font-size:.72rem;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($porCobrar as $item)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-4 py-2">
                                            <div class="fw-semibold text-dark">{{ $item->concept }}</div>
                                            <span class="badge bg-primary text-white"
                                                  style="font-size:.65rem;border-radius:999px;">{{ $item->tag }}</span>
                                        </td>
                                        <td class="py-2 fw-semibold text-danger">
                                            Bs. {{ number_format($item->value, 2) }}
                                        </td>
                                        <td class="py-2 small text-muted">
                                            {{ $item->date ? $item->date->format('d/m/Y') : '—' }}
                                        </td>
                                        <td class="py-2 text-end pe-4">
                                            <a href="{{ $item->url }}" class="btn btn-sm btn-light border"
                                               title="Ver detalle">
                                                <i class="bi bi-eye me-1"></i>Ver
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-check2-circle fs-1 d-block mb-2 opacity-25"></i>
                                            <p class="mb-0 small">Sin cuentas por cobrar pendientes.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($porCobrar->total())
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="ps-4 py-2 text-end pe-4 fw-bold text-danger small">
                                            Total por cobrar: Bs. {{ number_format($totalCobrar, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                        @if($porCobrar->hasPages())
                        <div class="px-3 py-2 border-top">{{ $porCobrar->links() }}</div>
                        @endif
                    </div>

                    {{-- ─── Sub-pane: Por pagar ─────────────────────── --}}
                    <div class="tab-pane fade" id="subPagar" role="tabpanel">
                        @if($porPagar->total())
                        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom"
                             style="background:rgba(225,6,0,.03);">
                            <span class="text-muted small">
                                <i class="bi bi-cash-stack me-1 text-danger"></i>
                                {{ $porPagar->total() }} {{ $porPagar->total() === 1 ? 'pendiente' : 'pendientes' }}
                            </span>
                            <span class="fw-bold text-danger">
                                Total por pagar: Bs. {{ number_format($totalPagar, 2) }}
                            </span>
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                                <thead>
                                    <tr class="table-light border-bottom">
                                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Concepto</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Valor</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase"
                                            style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                                        <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4"
                                            style="letter-spacing:.04em;font-size:.72rem;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($porPagar as $item)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-4 py-2">
                                            <div class="fw-semibold text-dark">{{ $item->concept }}</div>
                                        </td>
                                        <td class="py-2 fw-semibold text-danger">
                                            Bs. {{ number_format($item->value, 2) }}
                                        </td>
                                        <td class="py-2 small text-muted">
                                            {{ $item->date ? $item->date->format('d/m/Y') : '—' }}
                                        </td>
                                        <td class="py-2 text-end pe-4">
                                            <a href="{{ $item->url }}" class="btn btn-sm btn-light border"
                                               title="Ver detalle">
                                                <i class="bi bi-eye me-1"></i>Ver
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-check2-all fs-1 d-block mb-2 opacity-25"></i>
                                            <p class="mb-0 small">Sin cuentas por pagar pendientes.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($porPagar->total())
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="4" class="ps-4 py-2 text-end pe-4 fw-bold text-danger small">
                                            Total por pagar: Bs. {{ number_format($totalPagar, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                        @if($porPagar->hasPages())
                        <div class="px-3 py-2 border-top">{{ $porPagar->links() }}</div>
                        @endif
                    </div>

                </div>{{-- /sub tab-content --}}
            </div>{{-- /sub card --}}

        </div>{{-- /paneTransacciones --}}

        {{-- ════════════════════════════════════════════════════════════ --}}
        {{-- PANE: CIERRES DE CAJA                                       --}}
        {{-- ════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="paneCierres" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-lock me-2 text-muted"></i>Cierres de caja
                    </h6>
                    <small class="text-muted">{{ $closures->total() }} registros</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                            <thead>
                                <tr class="table-light border-bottom">
                                    <th class="ps-4 py-3" style="width:36px;"></th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Caja</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Sucursal</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Apertura</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Cierre</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Esperado</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Diferencia</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase"
                                        style="letter-spacing:.04em;font-size:.72rem;">Responsable</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase pe-4"
                                        style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($closures as $c)
                                @php
                                    $isOpen      = $c->status === 'open' || !$c->closed_at;
                                    $expectedAmt = $isOpen ? $c->expectedBalance() : ($c->expected_amount ?? $c->expectedBalance());
                                    $diff        = (float) $c->difference;
                                    $diffClass   = $diff == 0 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger');
                                    $diffPrefix  = $diff > 0 ? '+' : '';
                                @endphp
                                <tr class="border-bottom border-light closure-row" data-session="{{ $c->id }}"
                                    style="cursor:pointer;@if($isOpen)background:rgba(245,158,11,.06);@endif">
                                    <td class="ps-4 py-2 text-center">
                                        <i class="bi bi-chevron-right closure-chevron text-muted"></i>
                                    </td>
                                    <td class="py-2 fw-semibold small">
                                        {{ $c->cashRegister?->name ?? '—' }}
                                        @if($isOpen)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1"
                                              style="font-size:.62rem;"><i class="bi bi-unlock me-1"></i>Pendiente de cierre</span>
                                        @endif
                                    </td>
                                    <td class="py-2 small text-muted">
                                        {{ $c->cashRegister?->branch?->name ?? '—' }}
                                    </td>
                                    <td class="py-2 small">Bs. {{ number_format($c->opening_amount, 2) }}</td>
                                    <td class="py-2 small">
                                        @if($isOpen)
                                            <span class="text-warning fw-semibold">Sin cerrar</span>
                                        @else
                                            Bs. {{ number_format($c->closing_amount, 2) }}
                                        @endif
                                    </td>
                                    <td class="py-2 small">
                                        Bs. {{ number_format($expectedAmt, 2) }}
                                        @if($isOpen)<div class="text-muted" style="font-size:.7rem;">saldo actual</div>@endif
                                    </td>
                                    <td class="py-2 small fw-semibold {{ $isOpen ? 'text-muted' : $diffClass }}">
                                        @if($isOpen)
                                            —
                                        @else
                                            {{ $diffPrefix }}{{ number_format($diff, 2) }}
                                            @if($diff > 0)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"
                                                      style="font-size:.62rem;">sobrante</span>
                                            @elseif($diff < 0)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1"
                                                      style="font-size:.62rem;">faltante</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1"
                                                      style="font-size:.62rem;">exacto</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-2 small">
                                        @if($isOpen)
                                            {{ $c->openedBy?->name ?? '—' }}
                                        @else
                                            {{ $c->closedBy?->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4 small text-muted">
                                        @if($isOpen)
                                            <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle;"></i>Abierta</span>
                                            <div style="font-size:.72rem;">{{ $c->opened_at ? $c->opened_at->format('d/m/Y H:i') : '—' }}</div>
                                        @else
                                            {{ $c->closed_at ? $c->closed_at->format('d/m/Y H:i') : '—' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr class="closure-detail-row d-none" data-session="{{ $c->id }}">
                                    <td colspan="9" class="p-0 bg-body-tertiary">
                                        <div class="closure-detail" data-loaded="0"
                                             data-url="{{ route('cash.movements.session-detail', $c->id) }}">
                                            <div class="text-center py-4 text-muted closure-detail-spinner">
                                                <span class="spinner-border spinner-border-sm me-2"></span>Cargando detalle…
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-lock fs-1 d-block mb-2 opacity-25"></i>
                                        <p class="mb-0 small">Sin cajas aperturadas ni cierres en este período.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($closures->hasPages())
                    <div class="px-4 py-2 border-top">{{ $closures->links() }}</div>
                    @endif
                </div>
            </div>
        </div>{{-- /paneCierres --}}

    </div>{{-- /main tab-content --}}

    {{-- ── Modal compartido "Resolver diferencia" ────────────────────── --}}
    @include('cash.session._adjust-modal')

</div>{{-- /container-fluid --}}

@push('styles')
<style>
    /* ── Main segmented tabs (like the screenshot black/active pill) ── */
    .mov-main-tabs {
        border-bottom: 2px solid var(--border-soft);
        gap: 4px;
    }
    .mov-tab {
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px 8px 0 0;
        color: var(--text-secondary);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        font-size: .88rem;
        font-weight: 500;
        padding: 9px 20px;
        transition: all .16s ease;
        position: relative;
        bottom: -2px;
        border-bottom: 2px solid transparent;
    }
    .mov-tab:hover {
        color: var(--text-primary);
        background: var(--surface-muted);
        border-color: var(--border-soft) var(--border-soft) transparent;
    }
    .mov-tab.active {
        background: var(--brand-black);
        border-color: var(--brand-black);
        border-bottom-color: var(--brand-black);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 -2px 6px rgba(10,10,10,.12);
    }
    .mov-tab.active i { color: #fff; }

    /* Sub-tabs tweak */
    #subTabs .nav-link {
        font-size: .85rem;
        color: var(--text-secondary);
        border-color: transparent;
    }
    #subTabs .nav-link.active {
        color: var(--text-primary);
        font-weight: 600;
        border-color: var(--border-soft) var(--border-soft) #fff;
        background: #fff;
    }
    #subTabs .nav-link:hover:not(.active) {
        color: var(--text-primary);
        border-color: var(--border-soft) var(--border-soft) transparent;
        background: var(--surface-muted);
    }

    /* Income/expense circle icons tighter */
    .mov-type-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        flex-shrink: 0;
    }

    /* Search input group fix */
    .input-group-sm .input-group-text {
        padding: .28rem .6rem;
    }
    .input-group-sm .form-control {
        padding: .28rem .5rem;
    }

    /* Period pills */
    .mov-pill {
        display: inline-flex;
        align-items: center;
        padding: .28rem .8rem;
        border-radius: 50rem;
        font-size: .78rem;
        font-weight: 500;
        border: 1.5px solid #dee2e6;
        background: #fff;
        color: #495057;
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }
    .mov-pill:hover { border-color: var(--brand-black,#0a0a0a); color: var(--brand-black,#0a0a0a); }
    .mov-pill.active {
        background: var(--brand-black,#0a0a0a);
        border-color: var(--brand-black,#0a0a0a);
        color: #fff;
    }

    /* Slots de período: solo el activo visible */
    .period-slot { display: none; }
    .period-slot.is-active { display: flex; }

    /* Branch tabs (color por sucursal) */
    .branch-tab {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .26rem .8rem;
        border-radius: 50rem;
        font-size: .78rem;
        font-weight: 500;
        border: 1.5px solid #dee2e6;
        background: #fff;
        color: #495057;
        cursor: pointer;
        transition: all .15s ease;
        white-space: nowrap;
    }
    .branch-tab:hover { border-color: var(--bc,#0a0a0a); }
    .branch-tab.active {
        background: var(--bc,#0a0a0a);
        border-color: var(--bc,#0a0a0a);
        color: var(--bt,#fff);
    }
    .branch-tab.active .branch-dot { display: none; }
    .branch-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

    /* ── Cierres: fila expandible ─────────────────────────────── */
    .closure-row:hover { background: var(--surface-muted, #f8f9fa) !important; }
    .closure-chevron { transition: transform .18s ease; font-size: .8rem; }
    .closure-row.expanded .closure-chevron { transform: rotate(90deg); }
    .closure-row.expanded { background: var(--surface-muted, #f6f7f9) !important; }

    /* Mini KPIs del detalle */
    .session-detail { border-top: 2px solid var(--brand-black, #0a0a0a); }
    .sd-kpi {
        background: #fff;
        border: 1px solid #eceef1;
        border-radius: 10px;
        padding: .5rem .7rem;
        height: 100%;
    }
    .sd-kpi-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .04em; color: #8a929c; }
    .sd-kpi-value { font-weight: 700; font-size: .95rem; line-height: 1.2; }
    .sd-kpi-income   { border-left: 3px solid #16a34a; }
    .sd-kpi-expense  { border-left: 3px solid #e11d48; }
    .sd-kpi-expected { border-left: 3px solid #0d6efd; }

</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var filterForm   = document.getElementById('filterForm');
    var periodInput  = document.getElementById('periodInput');
    function autoSubmit() { if (filterForm) filterForm.submit(); }

    // ── Pills de período ─────────────────────────────────────────────
    function showSlot(period) {
        document.querySelectorAll('.period-slot').forEach(function (s) {
            s.classList.toggle('is-active', s.dataset.slot === period);
        });
        document.querySelectorAll('.mov-pill').forEach(function (p) {
            p.classList.toggle('active', p.dataset.period === period);
        });
    }

    document.querySelectorAll('.mov-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var period = this.dataset.period;
            periodInput.value = period;
            showSlot(period);
            // Rango: esperar a que el usuario complete ambas fechas.
            if (period !== 'range') autoSubmit();
        });
    });

    // ── ISO week helpers ─────────────────────────────────────────────
    function isoWeekString(d) {
        var date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
        var dayNum = (date.getUTCDay() + 6) % 7;
        date.setUTCDate(date.getUTCDate() - dayNum + 3);
        var firstThursday = new Date(Date.UTC(date.getUTCFullYear(), 0, 4));
        var week = 1 + Math.round(((date - firstThursday) / 86400000 - 3 + ((firstThursday.getUTCDay() + 6) % 7)) / 7);
        return date.getUTCFullYear() + '-W' + String(week).padStart(2, '0');
    }
    function isoWeekToMonday(str) {
        var m = String(str).match(/^(\d{4})-W(\d{1,2})$/);
        if (!m) return new Date();
        var simple = new Date(Date.UTC(+m[1], 0, 1 + (+m[2] - 1) * 7));
        var dow = (simple.getUTCDay() + 6) % 7;
        simple.setUTCDate(simple.getUTCDate() - dow);
        return simple;
    }
    function pad2(n) { return String(n).padStart(2, '0'); }

    // ── Navegación prev/next ─────────────────────────────────────────
    document.querySelectorAll('.nav-step').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var unit = this.dataset.unit;
            var dir  = parseInt(this.dataset.dir, 10);
            var input = this.parentElement.querySelector('.nav-input');
            if (!input) return;

            if (unit === 'day') {
                var d = input.value ? new Date(input.value + 'T00:00:00') : new Date();
                d.setDate(d.getDate() + dir);
                input.value = d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
            } else if (unit === 'month') {
                var parts = (input.value || '').split('-');
                var y = parseInt(parts[0], 10), mo = parseInt(parts[1], 10);
                if (!y || !mo) { var now = new Date(); y = now.getFullYear(); mo = now.getMonth() + 1; }
                var base = new Date(Date.UTC(y, mo - 1, 1));
                base.setUTCMonth(base.getUTCMonth() + dir);
                input.value = base.getUTCFullYear() + '-' + pad2(base.getUTCMonth() + 1);
            } else if (unit === 'week') {
                var mon = isoWeekToMonday(input.value);
                mon.setUTCDate(mon.getUTCDate() + dir * 7);
                input.value = isoWeekString(new Date(mon.getUTCFullYear(), mon.getUTCMonth(), mon.getUTCDate()));
            }
            autoSubmit();
        });
    });

    // ── Auto-submit al cambiar selector contextual ───────────────────
    document.querySelectorAll('.nav-input').forEach(function (input) {
        input.addEventListener('change', autoSubmit);
    });

    // ── Rango: enviar cuando ambas fechas están completas ────────────
    var fromInput = document.getElementById('fromInput');
    var toInput   = document.getElementById('toInput');
    function rangeAutoSubmit() {
        if (fromInput && toInput && fromInput.value && toInput.value) autoSubmit();
    }
    if (fromInput) fromInput.addEventListener('change', rangeAutoSubmit);
    if (toInput)   toInput.addEventListener('change', rangeAutoSubmit);

    // ── Sucursal (tabs) ──────────────────────────────────────────────
    var branchInput = document.getElementById('branchInput');
    document.querySelectorAll('.branch-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            if (branchInput) branchInput.value = this.dataset.branch;
            autoSubmit();
        });
    });

    // ── Persistir pestaña activa al paginar (vía hash) ───────────────
    var SUBTABS = ['#subIngresos', '#subEgresos', '#subCobrar', '#subPagar'];

    function showTab(targetSel) {
        var btn = document.querySelector('[data-bs-target="' + targetSel + '"]');
        if (btn && window.bootstrap) bootstrap.Tab.getOrCreateInstance(btn).show();
    }

    // Restaurar desde el hash al cargar
    if (location.hash) {
        var h = location.hash;
        if (SUBTABS.indexOf(h) !== -1) showTab('#paneTransacciones');
        showTab(h);
    }

    // Guardar el hash cuando se cambia de pestaña (sin saltar el scroll)
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (t) {
        t.addEventListener('shown.bs.tab', function (e) {
            var tgt = e.target.getAttribute('data-bs-target');
            if (tgt) history.replaceState(null, '', tgt);
        });
    });

    // Al hacer click en un link de paginación, conservar la pestaña activa
    function currentTabHash() {
        var sub = document.querySelector('#subTabs .nav-link.active');
        if (sub) return sub.getAttribute('data-bs-target');
        var main = document.querySelector('.mov-tab.active');
        return main ? main.getAttribute('data-bs-target') : '';
    }
    document.querySelectorAll('.pagination a').forEach(function (a) {
        a.addEventListener('click', function () {
            var h = currentTabHash();
            if (h) a.href = a.href.split('#')[0] + h;
        });
    });

    // ── Cierres: expandir fila y cargar detalle (AJAX diferido) ──────
    document.querySelectorAll('.closure-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var id = this.dataset.session;
            var detailRow = document.querySelector('.closure-detail-row[data-session="' + id + '"]');
            if (!detailRow) return;

            var isHidden = detailRow.classList.contains('d-none');
            detailRow.classList.toggle('d-none');
            this.classList.toggle('expanded', isHidden);

            if (isHidden) {
                var box = detailRow.querySelector('.closure-detail');
                if (box && box.dataset.loaded === '0') {
                    box.dataset.loaded = '1';
                    fetch(box.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
                        .then(function (html) { box.innerHTML = html; })
                        .catch(function () {
                            box.dataset.loaded = '0';
                            box.innerHTML = '<div class="text-center py-4 text-danger small">' +
                                '<i class="bi bi-exclamation-triangle me-1"></i>No se pudo cargar el detalle. Intenta de nuevo.</div>';
                        });
                }
            }
        });
    });

    // (El modal "Resolver diferencia" vive en el parcial cash.session._adjust-modal,
    //  con su propio script autocontenido.)

    // Al cargar: si venimos de un ajuste, abrir la pestaña "Cierres de caja"
    try {
        if (sessionStorage.getItem('cashOpenCierres') === '1') {
            sessionStorage.removeItem('cashOpenCierres');
            showTab('#paneCierres');
        }
    } catch (e) {}

})();
</script>
@endpush

@endsection
