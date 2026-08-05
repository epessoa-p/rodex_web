@extends('layouts.app')

@section('title', 'Suscripciones')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-credit-card"></i> Suscripciones</h1>
            <p class="text-muted mb-0 small">Estado de cada empresa-cliente. La activación es manual.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Empresa</th>
                            <th>Plan</th>
                            <th>Estado</th>
                            <th>Vence</th>
                            <th>Uso (usuarios / sucursales / productos)</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            @php
                                $sub    = $company->subscription;
                                $status = $sub?->effectiveStatus();
                                $badge  = match($status) {
                                    'active'    => 'bg-success',
                                    'trial'     => 'bg-info',
                                    'past_due'  => 'bg-warning text-dark',
                                    'suspended' => 'bg-danger',
                                    'cancelled' => 'bg-secondary',
                                    default     => 'bg-light text-dark border',
                                };
                                $plan = $sub?->plan;
                            @endphp
                            <tr>
                                <td class="ps-3"><strong>{{ $company->name }}</strong></td>

                                <td>{{ $plan?->name ?? '—' }}</td>

                                <td>
                                    <span class="badge {{ $badge }}">
                                        {{ $sub ? $sub->status_label : 'Sin suscripción' }}
                                    </span>
                                </td>

                                <td>
                                    @if($sub?->endsAt())
                                        {{ $sub->endsAt()->format('d/m/Y') }}
                                        @if($sub->inGrace())
                                            <div class="small text-warning">
                                                gracia hasta {{ $sub->graceEndsAt()->format('d/m/Y') }}
                                            </div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="small text-muted">
                                    @php
                                        $fmt = fn ($used, $limit) => $used . ' / ' . ($limit ?? '∞');
                                    @endphp
                                    {{ $fmt($company->usageFor('users'), $plan?->max_users) }} ·
                                    {{ $fmt($company->usageFor('branches'), $plan?->max_branches) }} ·
                                    {{ $fmt($company->usageFor('products'), $plan?->max_products) }}
                                </td>

                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('subscriptions.edit', $company) }}"
                                           class="btn btn-sm btn-outline-primary" title="Asignar plan / editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($sub)
                                            <form action="{{ route('subscriptions.renew', $company) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                <input type="number" name="months" value="1" min="1" max="36"
                                                       class="form-control form-control-sm" style="width:70px;">
                                                <button class="btn btn-sm btn-success" title="Renovar">
                                                    <i class="bi bi-arrow-clockwise"></i> Renovar
                                                </button>
                                            </form>

                                            @if($sub->status !== 'suspended')
                                                <form action="{{ route('subscriptions.suspend', $company) }}" method="POST"
                                                      onsubmit="return confirm('¿Suspender el acceso de {{ $company->name }}?')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger" title="Suspender">
                                                        <i class="bi bi-pause-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay empresas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $companies->links() }}
    </div>
</div>
@endsection
