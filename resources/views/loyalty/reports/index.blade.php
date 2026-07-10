@extends('layouts.app')
@section('title', 'Fidelización · Reportes')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bar-chart-line me-2 text-danger"></i>Reportes de Fidelización</h1>
            <p class="text-muted mb-0 small">Emisión, canje y vencimiento de puntos por período.</p>
        </div>
        <a href="{{ route('loyalty.dashboard') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small fw-semibold mb-1">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div>
                    <label class="form-label small fw-semibold mb-1">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Aplicar</button>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        @php
        $cards = [
            ['Puntos emitidos', $issued, 'bi-arrow-up-circle', 'success'],
            ['Puntos canjeados', $redeemed, 'bi-gift', 'danger'],
            ['Puntos vencidos', $expired, 'bi-hourglass-bottom', 'warning'],
            ['Ajustes', $adjusted, 'bi-sliders', 'secondary'],
        ];
        @endphp
        @foreach($cards as [$label, $val, $icon, $color])
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px;background:rgba(0,0,0,.04);">
                        <i class="bi {{ $icon }} fs-5 text-{{ $color }}"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $label }}</div>
                        <div class="fw-bold fs-5">{{ number_format($val, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2 text-warning"></i>Top clientes (acumulado)</h6></div>
                <div class="card-body p-0">
                    @forelse($topClients as $row)
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom border-light">
                        <span class="small fw-semibold">{{ $row->client?->full_name ?? '—' }}</span>
                        <span class="fw-bold text-success">+{{ number_format($row->total, 0) }}</span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted small">Sin datos en el período.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2 text-muted"></i>Recompensas más canjeadas</h6></div>
                <div class="card-body p-0">
                    @forelse($topRewards as $row)
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 border-bottom border-light">
                        <span class="small fw-semibold">{{ $row->reward?->name ?? '—' }}</span>
                        <span class="small"><span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">{{ $row->total }} canjes</span><span class="text-danger fw-semibold">−{{ number_format($row->puntos, 0) }}</span></span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted small">Sin canjes en el período.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle diario --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2 text-muted"></i>Detalle por día</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.83rem;">
                    <thead class="table-light"><tr>
                        <th class="ps-4 py-2">Día</th>
                        <th class="py-2 text-end">Emitidos</th>
                        <th class="py-2 text-end pe-4">Canjeados</th>
                    </tr></thead>
                    <tbody>
                        @forelse($series as $row)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 small">{{ \Illuminate\Support\Carbon::parse($row->d)->format('d/m/Y') }}</td>
                            <td class="py-2 text-end text-success">+{{ number_format($row->earned, 0) }}</td>
                            <td class="py-2 text-end pe-4 text-danger">−{{ number_format($row->redeemed, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted small">Sin movimientos en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
