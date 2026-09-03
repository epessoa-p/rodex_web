@extends('layouts.app')
@section('title', 'Estado de resultados')
@section('page')
@php
    $net = $report['net'];
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard-data me-2 text-primary"></i>Estado de resultados</h1>
            <p class="text-muted mb-0 small">Ingresos y egresos reales (caja + tesorería) del período.</p>
        </div>
        <form method="GET" class="d-flex align-items-end gap-2 no-print">
            <div>
                <label class="form-label small mb-0">Desde</label>
                <input type="date" name="from" value="{{ $report['from'] }}" class="form-control form-control-sm border">
            </div>
            <div>
                <label class="form-label small mb-0">Hasta</label>
                <input type="date" name="to" value="{{ $report['to'] }}" class="form-control form-control-sm border">
            </div>
            <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
            <button type="button" class="btn btn-sm btn-light border" onclick="window.print()"><i class="bi bi-printer"></i></button>
        </form>
    </div>

    <div class="row g-4">
        {{-- Ingresos --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-success"><i class="bi bi-arrow-down-circle me-2"></i>Ingresos</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            @forelse($report['income'] as $r)
                            <tr><td class="ps-4">{{ $r['label'] }}</td><td class="text-end pe-4">{{ money($r['amount']) }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin ingresos en el período.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold"><td class="ps-4">Total ingresos</td><td class="text-end pe-4 text-success">{{ money($report['total_income']) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Egresos --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-arrow-up-circle me-2"></i>Egresos</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            @forelse($report['expense'] as $r)
                            <tr><td class="ps-4">{{ $r['label'] }}</td><td class="text-end pe-4">{{ money($r['amount']) }}</td></tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">Sin egresos en el período.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold"><td class="ps-4">Total egresos</td><td class="text-end pe-4 text-danger">{{ money($report['total_expense']) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Resultado --}}
    <div class="card border-0 shadow-sm mt-4" style="background:{{ $net >= 0 ? 'rgba(var(--bs-success-rgb),.10)' : 'rgba(var(--bs-danger-rgb),.10)' }};">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted small">Resultado del período ({{ \Illuminate\Support\Carbon::parse($report['from'])->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($report['to'])->format('d/m/Y') }})</div>
                <div class="fw-bold">{{ $net >= 0 ? 'Utilidad' : 'Pérdida' }}</div>
            </div>
            <div class="fs-3 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ money($net) }}</div>
        </div>
    </div>
</div>
@endsection
