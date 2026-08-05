@extends('layouts.app')

@section('title', 'Suscripción · ' . $company->name)

@section('page')
@php
    $sub = $company->subscription;
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-credit-card"></i> {{ $company->name }}</h1>
            <p class="text-muted mb-0 small">
                {{ $sub ? 'Editar la suscripción de esta empresa.' : 'Esta empresa aún no tiene plan asignado: sin él no puede entrar al sistema.' }}
            </p>
        </div>
        <a href="{{ route('subscriptions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('subscriptions.update', $company) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Plan</label>
                            <select name="plan_id" class="form-select" required>
                                <option value="">— Selecciona un plan —</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        {{ old('plan_id', $sub?->plan_id) == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} — {{ number_format($plan->price, 2) }} / {{ $plan->billing_period_label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Define qué módulos y qué límites de uso tendrá la empresa.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="status" class="form-select" required>
                                @foreach(\App\Models\Subscription::STATUSES as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('status', $sub?->status ?? 'trial') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fin de la prueba</label>
                                <input type="date" name="trial_ends_at" class="form-control"
                                       value="{{ old('trial_ends_at', $sub?->trial_ends_at?->format('Y-m-d')) }}">
                                <div class="form-text">Si lo dejas vacío al crear en estado «Prueba», se usan los días del plan.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fin del periodo pagado</label>
                                <input type="date" name="current_period_end" class="form-control"
                                       value="{{ old('current_period_end', $sub?->current_period_end?->format('Y-m-d')) }}">
                                <div class="form-text">Para activar sin renovar: pon aquí la fecha y el estado «Activa».</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Días de gracia</label>
                            <input type="number" name="grace_days" class="form-control" style="max-width:140px;"
                                   min="0" max="60" value="{{ old('grace_days', $sub->grace_days ?? 3) }}" required>
                            <div class="form-text">
                                Tras vencer, la empresa puede <strong>consultar</strong> sus datos estos días, pero no registrar cambios.
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Notas internas</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Referencia de pago, acuerdos, etc.">{{ old('notes', $sub?->notes) }}</textarea>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Guardar suscripción
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-bar-chart me-2 text-muted"></i>Uso actual</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $plan = $sub?->plan;
                            $rows = [
                                ['Usuarios',  $company->usageFor('users'),    $plan?->max_users],
                                ['Sucursales',$company->usageFor('branches'), $plan?->max_branches],
                                ['Productos', $company->usageFor('products'), $plan?->max_products],
                            ];
                        @endphp

                        @foreach($rows as [$label, $used, $limit])
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-semibold">{{ $label }}</span>
                                    <span class="text-muted">{{ $used }} / {{ $limit ?? '∞' }}</span>
                                </div>
                                @if($limit)
                                    @php $pct = min(100, (int) round($used / max(1, $limit) * 100)); @endphp
                                    <div class="progress mt-1" style="height:6px;">
                                        <div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($plan)
                            <hr>
                            <div class="small fw-semibold mb-2">Módulos del plan</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(\App\Models\Plan::MODULES as $key => $label)
                                    <span class="badge {{ $plan->allows($key) ? 'bg-success' : 'bg-light text-muted border' }}">
                                        {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                @if($sub)
                    <div class="card mt-3 border-danger-subtle">
                        <div class="card-body">
                            <h6 class="text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i>Zona de riesgo</h6>
                            <p class="small text-muted">
                                Cancelar deja a la empresa sin acceso al sistema. Sus datos se conservan.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>

    @if($sub)
        {{-- Fuera del form principal: no se pueden anidar formularios en HTML. --}}
        <form action="{{ route('subscriptions.cancel', $company) }}" method="POST" class="mt-2"
              onsubmit="return confirm('¿Cancelar la suscripción de {{ $company->name }}? Perderá el acceso.')">
            @csrf
            <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-x-circle"></i> Cancelar suscripción
            </button>
        </form>
    @endif
</div>
@endsection
