@extends('layouts.app')

@section('title', $company->name)

@section('page')
@php
    $st = $usage['status'];
    $ob = session('onboarding');
@endphp

@if(is_array($ob) && $ob)
{{-- Resumen del alta "lista para usar": se muestra UNA vez (flash) para copiar y entregar al cliente. --}}
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #16a34a !important;">
    <div class="card-body">
        <h6 class="fw-bold mb-2"><i class="bi bi-rocket-takeoff me-2 text-success"></i>Empresa lista para usar</h6>
        <div class="row g-3">
            <div class="col-lg-6">
                <ul class="list-unstyled mb-0 small">
                    @if(!empty($ob['plan']))<li><i class="bi bi-credit-card me-2 text-muted"></i>Plan: <strong>{{ $ob['plan'] }}</strong></li>@endif
                    @if(!empty($ob['branch']))<li><i class="bi bi-shop me-2 text-muted"></i>Sucursal: <strong>{{ $ob['branch'] }}</strong> · almacén {{ $ob['warehouse'] ?? '' }}</li>@endif
                    @if(!empty($ob['cargo']))<li><i class="bi bi-person-badge me-2 text-muted"></i>Cargo: <strong>{{ $ob['cargo'] }}</strong> (todos los permisos)</li>@endif
                    @if(!empty($ob['personal']))<li><i class="bi bi-person-vcard me-2 text-muted"></i>Personal: <strong>{{ $ob['personal'] }}</strong></li>@endif
                    @if(!empty($ob['register']))<li><i class="bi bi-safe2 me-2 text-muted"></i>Caja: <strong>{{ $ob['register'] }}</strong></li>@endif
                </ul>
            </div>
            @if(!empty($ob['email']))
            <div class="col-lg-6">
                <div class="rounded-3 p-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <div class="small fw-semibold mb-1"><i class="bi bi-key me-1"></i>Credenciales para el cliente</div>
                    <pre class="mb-2 small" id="obCreds" style="white-space:pre-wrap;">Sistema: {{ config('app.url') }}
Usuario: {{ $ob['username'] }}
Email: {{ $ob['email'] }}
Contraseña: {{ $ob['password'] }}</pre>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="navigator.clipboard.writeText(document.getElementById('obCreds').innerText).then(()=>this.innerText='Copiado ✓')">
                        <i class="bi bi-clipboard me-1"></i>Copiar
                    </button>
                    <span class="small text-muted ms-2">Se muestra solo esta vez.</span>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-building me-2 text-primary"></i>{{ $company->name }}</h1>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-{{ $st['color'] }}-subtle text-{{ $st['color'] }}-emphasis border border-{{ $st['color'] }}-subtle fs-6">
                <i class="bi bi-activity me-1"></i>{{ $st['label'] }}
            </span>
            @if($company->subscription?->plan)
                <span class="text-muted small">
                    Plan <strong>{{ $company->subscription->plan->name }}</strong>
                    · {{ \App\Models\Subscription::STATUSES[$company->subscription->status] ?? $company->subscription->status }}
                </span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('companies.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left"></i> Volver</a>
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Editar</a>
    </div>
</div>

{{-- ── Uso: indicadores principales ── --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Último registro creado</div>
                <div class="fs-5 fw-bold">
                    {{ $usage['last_activity_at']?->diffForHumans() ?? 'Nunca' }}
                </div>
                @if($usage['last_activity_at'])
                    <div class="text-muted small">{{ $usage['last_activity_at']->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Último acceso de un usuario</div>
                <div class="fs-5 fw-bold">
                    {{ $usage['last_seen_at']?->diffForHumans() ?? 'Sin datos' }}
                </div>
                @if($usage['last_seen_at'])
                    <div class="text-muted small">{{ $usage['last_seen_at']->format('d/m/Y H:i') }}</div>
                @else
                    <div class="text-muted small">Se registra desde que se activó el monitoreo.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Usuarios activos (últimos {{ $usage['recent_days'] }} días)</div>
                <div class="fs-5 fw-bold">{{ $usage['active_users'] }} <span class="text-muted fw-normal fs-6">de {{ $usage['total_users'] }}</span></div>
                <div class="text-muted small">Entraron a la empresa en el período.</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Balance y ganancia por período ── --}}
@if(!empty($balance))
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-stack me-2"></i>Balance y ganancia</h6>
        <span class="text-muted small">Movimientos reales de caja + tesorería (base efectivo)</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Período</th>
                    <th class="text-end text-success">Ingresos</th>
                    <th class="text-end text-danger">Egresos</th>
                    <th class="text-end pe-4">Ganancia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance as $key => $b)
                    @php
                        $positive = $b['net'] >= 0;
                        // Variación vs. el período anterior equivalente (semana↔semana, mes↔mes).
                        $prevKey = ['this_week' => 'last_week', 'this_month' => 'last_month'][$key] ?? null;
                        $prev    = $prevKey ? ($balance[$prevKey]['net'] ?? null) : null;
                        $delta   = ($prev !== null && abs($prev) > 0) ? (($b['net'] - $prev) / abs($prev)) * 100 : null;
                    @endphp
                    <tr class="{{ str_starts_with($key, 'this_') ? 'fw-semibold' : 'text-muted' }}">
                        <td class="ps-4">
                            {{ $b['label'] }}
                            <div class="small fw-normal text-muted">{{ $b['from']->format('d/m') }} – {{ $b['to']->format('d/m/Y') }}</div>
                        </td>
                        <td class="text-end">{{ money($b['income']) }}</td>
                        <td class="text-end">{{ money($b['expense']) }}</td>
                        <td class="text-end pe-4 text-nowrap">
                            <span class="{{ $positive ? 'text-success' : 'text-danger' }} fw-bold">{{ money($b['net']) }}</span>
                            @if($delta !== null)
                                <span class="badge ms-1 {{ $delta >= 0 ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' }}"
                                      title="vs. {{ $balance[$prevKey]['label'] }}">
                                    {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 0) }}%
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="row g-3">
    {{-- ── Uso por módulo ── --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-2"></i>Actividad por módulo</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Módulo</th>
                            <th class="text-center">Últimos {{ $usage['recent_days'] }} días</th>
                            <th class="text-center">Total</th>
                            <th class="pe-4">Último registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usage['modules'] as $m)
                            <tr class="{{ $m['total'] === 0 ? 'text-muted' : '' }}">
                                <td class="ps-4"><i class="bi {{ $m['icon'] }} me-2 text-muted"></i>{{ $m['label'] }}</td>
                                <td class="text-center">
                                    @if($m['recent'] > 0)
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ $m['recent'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $m['total'] }}</td>
                                <td class="pe-4">
                                    @if($m['last_at'])
                                        <span title="{{ $m['last_at']->format('d/m/Y H:i') }}">{{ $m['last_at']->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Información ── --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-4 text-muted fw-normal">RUC</dt><dd class="col-8">{{ $company->ruc ?? '—' }}</dd>
                    <dt class="col-4 text-muted fw-normal">Email</dt><dd class="col-8">{{ $company->email ?? '—' }}</dd>
                    <dt class="col-4 text-muted fw-normal">Teléfono</dt><dd class="col-8">{{ $company->phone ?? '—' }}</dd>
                    <dt class="col-4 text-muted fw-normal">Dirección</dt><dd class="col-8">{{ $company->address ?? '—' }}</dd>
                    <dt class="col-4 text-muted fw-normal">Estado</dt>
                    <dd class="col-8"><span class="badge {{ $company->active ? 'bg-success' : 'bg-danger' }}">{{ $company->active ? 'Activo' : 'Inactivo' }}</span></dd>
                    <dt class="col-4 text-muted fw-normal">Creada</dt><dd class="col-8 mb-0">{{ $company->created_at?->format('d/m/Y') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2"></i>Usuarios</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Usuario</th>
                            <th>Rol</th>
                            <th class="pe-4">Último acceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php $seen = $user->pivot->last_seen_at ?? null; @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold small">{{ $user->name }}</div>
                                    <div class="text-muted small">{{ $user->email }}</div>
                                </td>
                                <td class="small">{{ $user->pivot->role_id ? \App\Models\Role::find($user->pivot->role_id)?->name : '—' }}</td>
                                <td class="pe-4 small">
                                    @if($seen)
                                        <span title="{{ \Illuminate\Support\Carbon::parse($seen)->format('d/m/Y H:i') }}">{{ \Illuminate\Support\Carbon::parse($seen)->diffForHumans() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No hay usuarios en esta empresa</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer bg-white border-0 d-flex justify-content-center">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="mt-4">
    <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline"
          onsubmit="return confirm('¿Eliminar la empresa «{{ $company->name }}»? Esta acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Eliminar empresa</button>
    </form>
</div>
@endsection
