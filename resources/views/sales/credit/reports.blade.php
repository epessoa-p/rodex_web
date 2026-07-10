@extends('layouts.app')
@section('title', 'Reportes de Crédito')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-graph-up me-2 text-danger"></i>Reportes de Crédito</h1>
            <p class="text-muted mb-0 small">Análisis de la cartera y recuperación de créditos.</p>
        </div>
        <button type="button" class="btn btn-light border" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #dc3545 !important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(220,53,69,.08);">
                            <i class="bi bi-hourglass-split fs-5 text-danger"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Por cobrar</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1 text-danger">${{ number_format($portfolio, 2) }}</div>
                    <div class="text-muted small">Cartera por cobrar</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(13,110,253,.08);">
                            <i class="bi bi-credit-card fs-5 text-primary"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Total</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">${{ number_format($totalCredit, 2) }}</div>
                    <div class="text-muted small">Total financiado</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(25,135,84,.08);">
                            <i class="bi bi-check-circle fs-5 text-success"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">Recuperado</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1 text-success">${{ number_format($totalPaid, 2) }}</div>
                    <div class="text-muted small">Total recuperado</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(32,201,151,.08);">
                            <i class="bi bi-calendar-check fs-5" style="color:#20c997;"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">Este mes</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1" style="color:#20c997;">${{ number_format($recoveredThisMonth, 2) }}</div>
                    <div class="text-muted small">Recuperado del mes</div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4">

        {{-- Aging --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart me-2 text-muted"></i>Antigüedad de saldos</h6>
                </div>
                <div class="card-body p-4">
                    @php
                        $agingConfig = [
                            'vigente'  => ['label' => 'Vigente',    'color' => 'success', 'bar' => '#198754'],
                            '1-30'     => ['label' => '1-30 días',  'color' => 'warning', 'bar' => '#ffc107'],
                            '31-60'    => ['label' => '31-60 días', 'color' => 'warning', 'bar' => '#fd7e14'],
                            '61-90'    => ['label' => '61-90 días', 'color' => 'danger',  'bar' => '#dc3545'],
                            '90+'      => ['label' => '+90 días',   'color' => 'danger',  'bar' => '#842029'],
                        ];
                        $maxAging = max(array_values($aging ?? []) + [1]);
                    @endphp
                    @if(empty(array_filter($aging ?? [])))
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bar-chart fs-1 d-block mb-2 opacity-25"></i>
                        Sin datos de antigüedad.
                    </div>
                    @else
                    <div class="d-flex flex-column gap-3">
                        @foreach($agingConfig as $key => $cfg)
                        @php $val = $aging[$key] ?? 0; @endphp
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold">{{ $cfg['label'] }}</span>
                                <span class="small fw-bold {{ $val > 0 ? 'text-' . $cfg['color'] : 'text-muted' }}">
                                    ${{ number_format($val, 2) }}
                                </span>
                            </div>
                            <div class="progress" style="height:8px;border-radius:4px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width:{{ $maxAging > 0 ? ($val / $maxAging * 100) : 0 }}%;background:{{ $cfg['bar'] }};border-radius:4px;"
                                     aria-valuenow="{{ $val }}" aria-valuemin="0" aria-valuemax="{{ $maxAging }}">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top debtors --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-muted"></i>Top deudores</h6>
                </div>
                <div class="card-body p-0">
                    @if($topDebtors->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                        Sin deudores registrados.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                            <thead>
                                <tr class="table-light border-bottom">
                                    <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">#</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Ventas</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topDebtors as $i => $debtor)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white"
                                             style="width:26px;height:26px;font-size:.72rem;background:{{ $i === 0 ? '#f59e0b' : ($i === 1 ? '#9ca3af' : ($i === 2 ? '#b45309' : '#6b7280')) }};">
                                            {{ $i + 1 }}
                                        </div>
                                    </td>
                                    <td class="py-2 small fw-semibold">{{ $debtor->client_name }}</td>
                                    <td class="py-2 text-center small">
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ $debtor->count }}</span>
                                    </td>
                                    <td class="py-2 text-end fw-bold text-danger pe-4">${{ number_format($debtor->balance, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

@push('styles')
<style>
@media print {
    .app-sidebar, .app-topbar, .btn { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
@endpush

@endsection
