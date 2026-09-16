@extends('layouts.app')

@section('title', 'Plataforma')

@section('page')
@php
    $k = $overview['kpis'];
    $subColor = fn (string $s) => match ($s) {
        'active' => 'success', 'trial' => 'info', 'past_due' => 'warning',
        'suspended', 'cancelled' => 'danger', default => 'secondary',
    };
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Plataforma</h4>
            <p class="text-muted mb-0">Salud del sistema: quién lo usa, quién vence y quién se enfrió.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('companies.index') }}" class="btn btn-outline-primary"><i class="bi bi-buildings me-1"></i>Empresas</a>
            <a href="{{ route('subscriptions.index') }}" class="btn btn-primary"><i class="bi bi-credit-card me-1"></i>Suscripciones</a>
        </div>
    </div>

    {{-- ── KPIs ─────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
            $kpis = [
                ['v' => $k['companies'],    'l' => 'Empresas',          't' => $k['active_subs'] . ' con suscripción vigente', 'i' => 'bi-buildings',     'c' => '#4f46e5'],
                ['v' => $k['active_today'], 'l' => 'Activas hoy',       't' => $k['active_week'] . ' esta semana',            'i' => 'bi-activity',      'c' => '#16a34a'],
                ['v' => $k['trial'],        'l' => 'En prueba',         't' => 'periodo trial',                                'i' => 'bi-hourglass-split','c' => '#0284c7'],
                ['v' => $k['past_due'],     'l' => 'Vencidas',          't' => 'en gracia o sin acceso',                      'i' => 'bi-exclamation-triangle', 'c' => '#f59e0b'],
                ['v' => $k['idle'],         'l' => 'Sin actividad',     't' => '30+ días o nunca',                             'i' => 'bi-moon',          'c' => '#dc2626'],
                ['v' => $k['blocked'],      'l' => 'Suspendidas',       't' => 'o canceladas',                                 'i' => 'bi-slash-circle',  'c' => '#6b7280'],
            ];
        @endphp
        @foreach($kpis as $x)
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $x['v'] }}</div>
                        <div class="kpi-label">{{ $x['l'] }}</div>
                        <div class="kpi-trend text-muted">{{ $x['t'] }}</div>
                    </div>
                    <div class="kpi-icon" style="background:{{ $x['c'] }};"><i class="bi {{ $x['i'] }}"></i></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- ── Vencen pronto ─────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-alarm me-2 text-warning"></i>Vencen en ≤ {{ \App\Services\Admin\PlatformOverviewService::EXPIRING_DAYS }} días</h6>
                    <span class="badge bg-warning text-dark">{{ $overview['expiring']->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Empresa</th><th>Plan</th><th>Vence</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        @forelse($overview['expiring'] as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->company->name }}</td>
                            <td class="small">{{ $r->plan ?? '—' }} <span class="badge bg-{{ $subColor($r->sub_status) }} ms-1">{{ $r->sub_label }}</span></td>
                            <td class="small">
                                {{ $r->ends_at?->format('d/m/Y') }}
                                <span class="{{ $r->days_to_end <= 3 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    ({{ $r->days_to_end <= 0 ? 'hoy' : 'en ' . $r->days_to_end . ' d' }})
                                </span>
                            </td>
                            <td class="text-end"><a href="{{ route('subscriptions.edit', $r->company) }}" class="btn btn-sm btn-outline-primary">Renovar</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted small py-3">Ninguna suscripción vence pronto.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Vencidas ──────────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Vencidas (cobrar o cortar)</h6>
                    <span class="badge bg-danger">{{ $overview['past_due']->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Empresa</th><th>Plan</th><th>Venció</th><th>Uso</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        @forelse($overview['past_due'] as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->company->name }}</td>
                            <td class="small">{{ $r->plan ?? '—' }}</td>
                            <td class="small">{{ $r->ends_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><span class="badge bg-{{ $r->activity['color'] }}">{{ $r->activity['label'] }}</span></td>
                            <td class="text-end"><a href="{{ route('subscriptions.edit', $r->company) }}" class="btn btn-sm btn-outline-primary">Gestionar</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted small py-3">Todas las suscripciones están al día.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- ── Más activas ───────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-activity me-2 text-success"></i>Actividad reciente</h6>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($overview['most_active'] as $r)
                    <a href="{{ route('companies.show', $r->company) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                        <div class="lh-sm">
                            <div class="fw-semibold small">{{ $r->company->name }}</div>
                            <div class="small text-muted">{{ $r->plan ?? 'Sin plan' }} · último registro {{ $r->last_activity->diffForHumans() }}</div>
                        </div>
                        <span class="badge bg-{{ $r->activity['color'] }}">{{ $r->activity['label'] }}</span>
                    </a>
                    @empty
                    <div class="list-group-item text-muted small text-center py-3">Todavía no hay actividad registrada.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Con suscripción pero sin uso ──────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-moon me-2 text-secondary"></i>Pagan pero no usan (riesgo de baja)</h6>
                    <span class="badge bg-secondary">{{ $overview['idle']->count() }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($overview['idle'] as $r)
                    <a href="{{ route('companies.show', $r->company) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                        <div class="lh-sm">
                            <div class="fw-semibold small">{{ $r->company->name }}</div>
                            <div class="small text-muted">{{ $r->plan ?? 'Sin plan' }} · {{ $r->company->phone ?: ($r->company->email ?: 'sin contacto') }}</div>
                        </div>
                        <span class="badge bg-{{ $r->activity['color'] }}">{{ $r->activity['label'] }}</span>
                    </a>
                    @empty
                    <div class="list-group-item text-muted small text-center py-3">Todas las empresas con suscripción vigente están usando el sistema.</div>
                    @endforelse
                </div>
                @if($overview['recent_companies']->isNotEmpty())
                <div class="card-footer bg-white border-top py-2 small text-muted">
                    <i class="bi bi-plus-circle me-1"></i>Últimas altas:
                    @foreach($overview['recent_companies'] as $c)
                        <a href="{{ route('companies.show', $c) }}">{{ $c->name }}</a>{{ ! $loop->last ? ',' : '' }}
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .kpi-card { background:#fff; border-radius:10px; padding:18px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:0; height:100%; }
    .kpi-body { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
    .kpi-value { font-size:1.6rem; font-weight:700; line-height:1; margin-bottom:4px; color:#111; }
    .kpi-label { font-size:.82rem; color:#777; margin-bottom:6px; }
    .kpi-trend { font-size:.75rem; }
    .kpi-icon { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; color:#fff; font-size:1.15rem; flex-shrink:0; }
</style>
@endpush
@endsection
