@extends('layouts.app')
@section('title', 'Fidelización')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-award me-2 text-danger"></i>Fidelización</h1>
            <p class="text-muted mb-0 small">Resumen del programa de puntos y recompensas.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty.redeem', auth()->user()->getCurrentCompany()))
            <a href="{{ route('loyalty.redemptions.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-gift me-1"></i>Registrar canje</a>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-settings.view', auth()->user()->getCurrentCompany()))
            <a href="{{ route('loyalty.settings.edit') }}" class="btn btn-light border btn-sm"><i class="bi bi-gear me-1"></i>Configuración</a>
            @endif
        </div>
    </div>

    @if(!$settings || !$settings->enabled)
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>El programa de fidelización está <strong>desactivado</strong>. Actívalo en
            <a href="{{ route('loyalty.settings.edit') }}" class="alert-link">Configuración</a> para empezar a acumular puntos.</div>
    </div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        @php
        $kpis = [
            ['Puntos en circulación', $pointsBalance, 'bi-coin', 'primary'],
            ['Puntos acumulados', $pointsEarned, 'bi-arrow-up-circle', 'success'],
            ['Puntos canjeados', $pointsRedeemed, 'bi-gift', 'danger'],
            ['Canjes realizados', $redemptionsCount, 'bi-bag-check', 'dark'],
        ];
        @endphp
        @foreach($kpis as [$label, $val, $icon, $color])
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:44px;height:44px;background:rgba(0,0,0,.04);">
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
        {{-- Ranking de clientes --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2 text-warning"></i>Ranking de clientes</h6>
                    <span class="badge bg-light text-muted border">Top {{ $ranking->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($ranking as $i => $c)
                    <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom border-light">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $i < 3 ? 'bg-warning text-dark' : 'bg-light text-muted border' }}" style="width:26px;">{{ $i + 1 }}</span>
                            <span class="small fw-semibold">{{ $c->full_name }}</span>
                        </div>
                        <span class="fw-bold text-primary">{{ number_format($c->points_balance, 0) }} <span class="text-muted small fw-normal">pts</span></span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted small">Sin clientes con puntos todavía.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recompensas top + canjes recientes --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2 text-muted"></i>Recompensas más canjeadas</h6>
                </div>
                <div class="card-body p-0">
                    @forelse($topRewards as $r)
                    <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom border-light">
                        <span class="small fw-semibold">{{ $r->reward?->name ?? '—' }}</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $r->total }} canjes</span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted small">Aún no hay canjes.</div>
                    @endforelse
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Canjes recientes</h6>
                    <a href="{{ route('loyalty.redemptions.index') }}" class="small text-decoration-none">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    @forelse($recentRedemptions as $red)
                    <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom border-light">
                        <div>
                            <div class="small fw-semibold">{{ $red->reward?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:.72rem;">{{ $red->client?->full_name }} · {{ $red->redeemed_at?->format('d/m/Y') }}</div>
                        </div>
                        <span class="text-danger fw-semibold small">−{{ number_format($red->points_spent, 0) }} pts</span>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted small">Sin canjes recientes.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
