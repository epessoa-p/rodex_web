@extends('layouts.app')
@section('title', 'Estadísticas')
@section('page')
@php
    $tabs = [
        'resumen'    => ['Resumen', 'bi-grid-1x2'],
        'ventas'     => ['Ventas', 'bi-cart'],
        'personal'   => ['Personal', 'bi-people'],
        'clientes'   => ['Clientes', 'bi-person-vcard'],
        'compras'    => ['Compras', 'bi-bag'],
        'inventario' => ['Inventario', 'bi-box-seam'],
        'taller'     => ['Taller', 'bi-tools'],
        'alquileres' => ['Alquileres', 'bi-bicycle'],
    ];
@endphp
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5"><i class="bi bi-bar-chart-line me-2 text-danger"></i>Estadísticas</h1>
            <p class="text-muted mb-0 small">Análisis por área con recomendaciones automáticas.</p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap" id="statPeriodForm" data-no-spinner>
            <input type="hidden" name="period" id="statPeriod" value="{{ $period }}">
            <input type="hidden" name="branch" id="statBranch" value="{{ $branch }}">
            <span class="badge bg-dark text-white fw-normal" id="statPeriodLabel" style="font-size:.72rem;border-radius:999px;">{{ $periodLabel }}</span>
            <div class="d-flex gap-1">
                @foreach(['daily' => 'Día', 'weekly' => 'Semana', 'quincenal' => 'Quincena', 'monthly' => 'Mes'] as $key => $lbl)
                <button type="button" class="stat-pill {{ $period === $key ? 'active' : '' }}" data-period="{{ $key }}">{{ $lbl }}</button>
                @endforeach
            </div>
            <span class="stat-slot {{ $period === 'daily' ? 'is-active' : '' }}" data-slot="daily">
                <input type="date" name="date" value="{{ $dateValue }}" class="form-control form-control-sm" style="width:150px;">
            </span>
            <span class="stat-slot {{ $period === 'weekly' ? 'is-active' : '' }}" data-slot="weekly">
                <input type="week" name="week" value="{{ $weekValue }}" class="form-control form-control-sm" style="width:160px;">
            </span>
            <span class="stat-slot {{ $period === 'quincenal' ? 'is-active' : '' }}" data-slot="quincenal">
                <input type="date" name="qdate" value="{{ $quincenaValue }}" class="form-control form-control-sm" style="width:150px;" title="Cualquier día de la quincena">
            </span>
            <span class="stat-slot {{ $period === 'monthly' ? 'is-active' : '' }}" data-slot="monthly">
                <input type="month" name="month" value="{{ $monthValue }}" class="form-control form-control-sm" style="width:150px;">
            </span>
        </form>
    </div>

    {{-- Sucursales (tabs con color) --}}
    @if($branches->count())
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="statBranchTabs">
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
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-pills flex-nowrap mb-3 gap-1" id="statTabs" role="tablist" style="overflow-x:auto;">
        @foreach($tabs as $key => $t)
        <li class="nav-item flex-shrink-0" role="presentation">
            <button class="nav-link {{ $key === 'resumen' ? 'active' : '' }} py-1 px-3" id="t-{{ $key }}" data-bs-toggle="pill"
                    data-bs-target="#p-{{ $key }}" data-tab="{{ $key }}" type="button" role="tab" style="font-size:.82rem;">
                <i class="bi {{ $t[1] }} me-1"></i>{{ $t[0] }}
            </button>
        </li>
        @endforeach
    </ul>

    {{-- Contenedor de contenido (se reemplaza por AJAX) + loader --}}
    <div class="position-relative" id="statContentWrap">
        <div class="tab-content" id="statTabContent">
            @include('statistics.partials.content')
        </div>
        <div class="stat-loader" id="statLoader">
            <div class="text-center">
                <div class="spinner-border text-danger" role="status"></div>
                <div class="text-muted small mt-2">Cargando…</div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.stat-pill {
    border: 1.5px solid #dee2e6; background: #fff; color: #495057;
    border-radius: 50rem; padding: .18rem .8rem; font-size: .76rem; font-weight: 600;
    cursor: pointer; transition: all .15s ease; white-space: nowrap;
}
.stat-pill:hover { border-color: var(--brand-black,#0a0a0a); color: var(--brand-black,#0a0a0a); }
.stat-pill.active { background: var(--brand-black,#0a0a0a); border-color: var(--brand-black,#0a0a0a); color: #fff; }
.stat-slot { display: none; }
.stat-slot.is-active { display: inline-flex; }

/* Caja de gráfico con altura fija → evita el "parpadeo"/redibujo infinito de Chart.js */
.chart-box { position: relative; width: 100%; }
.chart-box > canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }

/* Branch tabs (color por sucursal) */
.branch-tab {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .22rem .8rem; border-radius: 50rem; font-size: .76rem; font-weight: 600;
    border: 1.5px solid #dee2e6; background: #fff; color: #495057; cursor: pointer;
    transition: all .15s ease; white-space: nowrap;
}
.branch-tab:hover { border-color: var(--bc,#0a0a0a); }
.branch-tab.active { background: var(--bc,#0a0a0a); border-color: var(--bc,#0a0a0a); color: var(--bt,#fff); }
.branch-tab.active .branch-dot { display: none; }
.branch-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }

/* Loader overlay */
.stat-loader {
    position: absolute; inset: 0; background: rgba(255,255,255,.7);
    display: none; align-items: center; justify-content: center; z-index: 5; border-radius: 8px;
}
.stat-loader.is-active { display: flex; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    var STATS_URL = window.location.pathname;
    var charts = {};
    var DATA = {};
    var PALETTE = ['#0d6efd', '#198754', '#fd7e14', '#6f42c1', '#dc3545', '#0dcaf0', '#ffc107', '#20c997'];

    function readData() {
        var el = document.getElementById('statChartData');
        try { DATA = el ? JSON.parse(el.textContent) : {}; } catch (e) { DATA = {}; }
    }

    function baseOpts(extra) {
        return Object.assign({ responsive: true, maintainAspectRatio: false, animation: false, resizeDelay: 120 }, extra || {});
    }

    function lineChart(el, d, label) {
        return new Chart(el, {
            type: 'line',
            data: { labels: d.labels, datasets: [{ label: label, data: d.data, borderColor: '#0a0a0a', backgroundColor: 'rgba(10,10,10,.08)', fill: true, tension: .3, pointRadius: 2 }] },
            options: baseOpts({ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } })
        });
    }
    function barChart(el, labels, data, horizontal) {
        return new Chart(el, {
            type: 'bar',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: '#0a0a0a', borderRadius: 4 }] },
            options: baseOpts({ indexAxis: horizontal ? 'y' : 'x', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true }, y: { beginAtZero: true } } })
        });
    }
    function doughnut(el, labels, data) {
        return new Chart(el, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: PALETTE }] },
            options: baseOpts({ plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } })
        });
    }
    function comparisonChart(el, d) {
        // Modo "single": una barra por mes (total mensual), con el mes seleccionado resaltado
        if (d.mode === 'single') {
            var colors = (d.data || []).map(function (_, i) { return i === d.highlight ? '#1f9d57' : '#a7e8c4'; });
            return new Chart(el, {
                type: 'bar',
                data: { labels: d.labels, datasets: [{ label: 'Ventas del mes', data: d.data, backgroundColor: colors, borderRadius: 4 }] },
                options: baseOpts({ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } })
            });
        }
        // Modo "grouped": dos series (período anterior vs actual)
        return new Chart(el, {
            type: 'bar',
            data: { labels: d.labels, datasets: [
                { label: d.prevLabel, data: d.prev, backgroundColor: '#a7e8c4', borderRadius: 4 },
                { label: d.curLabel,  data: d.cur,  backgroundColor: '#1f9d57', borderRadius: 4 },
            ] },
            options: baseOpts({ plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 10, font: { size: 11 } } } }, scales: { y: { beginAtZero: true } } })
        });
    }

    function makeChart(el, key) {
        switch (key) {
            case 'ventasComparison': return comparisonChart(el, DATA.ventasComparison);
            case 'ventasTrend':      return lineChart(el, DATA.ventasTrend, 'Ventas');
            case 'comprasTrend':     return lineChart(el, DATA.comprasTrend, 'Compras');
            case 'clientesNew':      return lineChart(el, DATA.clientesNew, 'Nuevos');
            case 'ventasTop':        return barChart(el, DATA.ventasTop.labels, DATA.ventasTop.data, true);
            case 'comprasTop':       return barChart(el, DATA.comprasTop.labels, DATA.comprasTop.data, true);
            case 'clientesTop':      return barChart(el, DATA.clientesTop.labels, DATA.clientesTop.data, true);
            case 'personal':         return barChart(el, DATA.personal.labels, DATA.personal.data, true);
            case 'ventasCashCredit': return doughnut(el, ['Contado', 'Crédito'], [DATA.ventasCashCredit.cash, DATA.ventasCashCredit.credit]);
            case 'inventario':       return doughnut(el, ['Con stock', 'Bajo', 'Sin stock'], [DATA.inventario.ok, DATA.inventario.low, DATA.inventario.none]);
            case 'taller':           return doughnut(el, DATA.taller.labels, DATA.taller.data);
            case 'alquileres':       return doughnut(el, ['Disponibles', 'Alquiladas', 'Mantenimiento'], [DATA.alquileres.disp, DATA.alquileres.alq, DATA.alquileres.mant]);
        }
        return null;
    }

    function buildTab(tab) {
        var pane = document.getElementById('p-' + tab);
        if (!pane) return;
        pane.querySelectorAll('canvas[data-chart]').forEach(function (el) {
            var key = el.dataset.chart;
            if (charts[key]) return;
            try { var c = makeChart(el, key); if (c) charts[key] = c; } catch (e) { console.error(e); }
        });
    }

    function destroyAll() {
        Object.keys(charts).forEach(function (k) { try { charts[k].destroy(); } catch (e) {} delete charts[k]; });
    }

    function activeTab() {
        var btn = document.querySelector('#statTabs .nav-link.active');
        return btn ? btn.dataset.tab : 'resumen';
    }

    // ── Loader ───────────────────────────────────────────────────────
    var loader = document.getElementById('statLoader');
    function showLoader(on) { if (loader) loader.classList.toggle('is-active', !!on); }

    // ── Carga por AJAX al cambiar período (sin recargar la página) ────
    var pForm  = document.getElementById('statPeriodForm');
    var pInput = document.getElementById('statPeriod');

    function loadPeriod() {
        var params = new URLSearchParams(new FormData(pForm)).toString();
        showLoader(true);
        fetch(STATS_URL + '?' + params + '&partial=1', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                destroyAll();
                document.getElementById('statTabContent').innerHTML = res.html;
                readData();
                // mantener la pestaña activa visible (el HTML viene con "resumen" activo)
                var tab = activeTab();
                document.querySelectorAll('#statTabContent .tab-pane').forEach(function (p) { p.classList.remove('show', 'active'); });
                var pane = document.getElementById('p-' + tab);
                if (pane) pane.classList.add('show', 'active');
                buildTab(tab);
                var badge = document.getElementById('statPeriodLabel');
                if (badge && res.periodLabel) badge.textContent = res.periodLabel;
                history.replaceState(null, '', STATS_URL + '?' + params);
            })
            .catch(function () { pForm.submit(); })
            .finally(function () { showLoader(false); });
    }

    document.querySelectorAll('.stat-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var p = this.dataset.period;
            pInput.value = p;
            document.querySelectorAll('.stat-pill').forEach(function (b) { b.classList.toggle('active', b === pill); });
            document.querySelectorAll('.stat-slot').forEach(function (s) { s.classList.toggle('is-active', s.dataset.slot === p); });
            loadPeriod();
        });
    });
    pForm.querySelectorAll('input[type=date], input[type=week], input[type=month]').forEach(function (inp) {
        inp.addEventListener('change', function () { loadPeriod(); });
    });

    // ── Sucursal (tabs con color) ────────────────────────────────────
    var branchInput = document.getElementById('statBranch');
    document.querySelectorAll('#statBranchTabs .branch-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            if (branchInput) branchInput.value = this.dataset.branch;
            document.querySelectorAll('#statBranchTabs .branch-tab').forEach(function (b) { b.classList.toggle('active', b === tab); });
            loadPeriod();
        });
    });

    // ── Construir gráficos de cada pestaña al mostrarse ──────────────
    document.querySelectorAll('#statTabs [data-bs-toggle="pill"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () { buildTab(this.dataset.tab); });
    });

    // Init
    document.addEventListener('DOMContentLoaded', function () {
        readData();
        buildTab(activeTab());
    });
})();
</script>
@endpush
@endsection
