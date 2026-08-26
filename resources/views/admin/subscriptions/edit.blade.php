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
                            <select name="plan_id" id="plan_id" class="form-select" required>
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

                {{-- ── Ajustes personalizados para ESTA empresa ────────────── --}}
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-sliders me-2 text-muted"></i>Ajustes personalizados para esta empresa</h6>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">
                            Amplía o reduce los cupos de esta empresa sin cambiarla de plan.
                            Deja un campo <strong>vacío</strong> para usar el valor del plan.
                        </p>

                        <div class="row g-3">
                            @foreach([['branches','Sucursales'],['users','Usuarios'],['products','Productos']] as [$key, $label])
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small">{{ $label }}</label>
                                    <input type="number" min="1" name="max_{{ $key }}_override"
                                           class="form-control ovr-input" data-key="{{ $key }}"
                                           value="{{ old('max_'.$key.'_override', $sub?->{'max_'.$key.'_override'}) }}"
                                           placeholder="Plan">
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-3">
                        @php $featOverride = $sub?->features_override; @endphp
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="customize_features" value="1"
                                   id="customize_features" {{ old('customize_features', $featOverride !== null ? '1' : '') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="customize_features">
                                Personalizar módulos de esta empresa
                            </label>
                        </div>
                        <p class="small text-muted mb-2">Si está apagado, la empresa usa los módulos de su plan.</p>

                        @php $effFeatures = old('features_override', $featOverride ?? []); @endphp
                        <div id="features-override-box" class="row g-2">
                            @foreach(\App\Models\Plan::MODULES as $key => $label)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input feat-ovr" type="checkbox" name="features_override[]"
                                               value="{{ $key }}" id="ovr_{{ $key }}"
                                               {{ in_array($key, $effFeatures, true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ovr_{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0"><i class="bi bi-box-seam me-2 text-muted"></i>Detalles del plan</h6>
                        <span class="badge bg-primary-subtle text-primary" id="plan-price">—</span>
                    </div>
                    <div class="card-body">
                        {{-- Mensaje cuando aún no hay plan elegido --}}
                        <div id="plan-empty" class="text-muted small">
                            Selecciona un plan para ver sus detalles.
                        </div>

                        {{-- Detalles del plan seleccionado (poblados por JS) --}}
                        <div id="plan-details" class="d-none">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Facturación</span>
                                <span class="fw-semibold" id="plan-period">—</span>
                            </div>
                            <div class="d-flex justify-content-between small mb-3">
                                <span class="text-muted">Periodo de prueba</span>
                                <span class="fw-semibold" id="plan-trial">—</span>
                            </div>

                            <div class="small fw-semibold mb-2">Uso actual vs. límites del plan</div>
                            @foreach([['users','Usuarios'],['branches','Sucursales'],['products','Productos']] as [$key, $label])
                                <div class="mb-3" data-usage="{{ $key }}">
                                    <div class="d-flex justify-content-between small">
                                        <span class="fw-semibold">{{ $label }}</span>
                                        <span class="text-muted">
                                            <span class="u-used">{{ $company->usageFor($key) }}</span> /
                                            <span class="u-limit">∞</span>
                                        </span>
                                    </div>
                                    <div class="progress mt-1" style="height:6px;">
                                        <div class="progress-bar bg-success" style="width:0"></div>
                                    </div>
                                </div>
                            @endforeach

                            <hr>
                            <div class="small fw-semibold mb-2">Módulos incluidos</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach(\App\Models\Plan::MODULES as $key => $label)
                                    <span class="badge bg-light text-muted border" data-module="{{ $key }}">{{ $label }}</span>
                                @endforeach
                            </div>
                        </div>
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

@php
    $plansJson = $plans->mapWithKeys(fn ($p) => [$p->id => [
        'price'        => money($p->price) . ' / ' . $p->billing_period_label,
        'period'       => $p->billing_period_label,
        'trial'        => $p->trial_days > 0 ? ($p->trial_days . ' días') : 'Sin prueba',
        'max_users'    => $p->max_users,
        'max_branches' => $p->max_branches,
        'max_products' => $p->max_products,
        'features'     => array_values($p->features ?? []),
    ]])->toArray();
@endphp

@push('scripts')
<script>
(function () {
    const plans  = @json($plansJson);
    const sel    = document.getElementById('plan_id');
    const empty  = document.getElementById('plan-empty');
    const detail = document.getElementById('plan-details');
    if (!sel) return;

    const custom    = document.getElementById('customize_features');
    const featBox   = document.getElementById('features-override-box');
    const ovrInputs = Array.from(document.querySelectorAll('.ovr-input'));

    function planLimit(p, key) {
        return { branches: p.max_branches, users: p.max_users, products: p.max_products }[key];
    }

    // Límite efectivo: el override si el campo tiene valor, si no el del plan.
    function effectiveLimit(p, key) {
        const inp = document.querySelector('.ovr-input[data-key="' + key + '"]');
        if (inp && inp.value.trim() !== '') {
            const v = parseInt(inp.value, 10);
            if (!isNaN(v)) return v;
        }
        return planLimit(p, key);
    }

    function renderUsage(p) {
        document.querySelectorAll('[data-usage]').forEach(function (row) {
            const key   = row.dataset.usage;
            const used  = parseInt(row.querySelector('.u-used').textContent, 10) || 0;
            const limit = effectiveLimit(p, key);
            const bar   = row.querySelector('.progress-bar');
            row.querySelector('.u-limit').textContent = (limit === null || limit === undefined) ? '∞' : limit;

            if (limit === null || limit === undefined) {
                bar.style.width = '0';
                bar.className = 'progress-bar bg-success';
            } else {
                const pct = Math.min(100, Math.round(used / Math.max(1, limit) * 100));
                bar.style.width = pct + '%';
                bar.className = 'progress-bar ' + (pct >= 100 ? 'bg-danger' : (pct >= 80 ? 'bg-warning' : 'bg-success'));
            }
        });
    }

    function updatePlaceholders(p) {
        ovrInputs.forEach(function (inp) {
            const lim = planLimit(p, inp.dataset.key);
            inp.placeholder = 'Plan: ' + ((lim === null || lim === undefined) ? 'Ilimitado' : lim);
        });
    }

    function syncFeatureBox(p) {
        if (!custom || !featBox) return;
        const on = custom.checked;
        featBox.style.opacity = on ? '1' : '.5';
        featBox.querySelectorAll('.feat-ovr').forEach(function (chk) { chk.disabled = !on; });
        // Con el switch apagado, refleja los módulos del plan (al guardar => hereda del plan).
        if (!on && p) {
            const feats = p.features || [];
            featBox.querySelectorAll('.feat-ovr').forEach(function (chk) {
                chk.checked = feats.indexOf(chk.value) >= 0;
            });
        }
    }

    function currentPlan() { return plans[sel.value]; }

    function render(id) {
        const p = plans[id];
        if (!p) {
            empty.classList.remove('d-none');
            detail.classList.add('d-none');
            document.getElementById('plan-price').textContent = '—';
            return;
        }
        empty.classList.add('d-none');
        detail.classList.remove('d-none');
        document.getElementById('plan-price').textContent  = p.price;
        document.getElementById('plan-period').textContent = p.period;
        document.getElementById('plan-trial').textContent  = p.trial;

        updatePlaceholders(p);
        renderUsage(p);

        const feats = p.features || [];
        document.querySelectorAll('[data-module]').forEach(function (badge) {
            const on = feats.indexOf(badge.dataset.module) >= 0;
            badge.className = 'badge ' + (on ? 'bg-success' : 'bg-light text-muted border');
        });
        syncFeatureBox(p);
    }

    // select2 emite el evento vía jQuery; escuchamos con jQuery si está disponible.
    if (window.jQuery) {
        jQuery('#plan_id').on('change', function () { render(this.value); });
    } else {
        sel.addEventListener('change', function () { render(this.value); });
    }

    // El panel "uso vs. límites" refleja los overrides en vivo.
    ovrInputs.forEach(function (inp) {
        inp.addEventListener('input', function () { const p = currentPlan(); if (p) renderUsage(p); });
    });
    if (custom) custom.addEventListener('change', function () { syncFeatureBox(currentPlan()); });

    render(sel.value); // estado inicial (plan guardado)
})();
</script>
@endpush
@endsection
