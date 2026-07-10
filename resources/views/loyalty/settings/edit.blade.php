@extends('layouts.app')
@section('title', 'Fidelización · Configuración')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gear me-2 text-danger"></i>Configuración de fidelización</h1>
            <p class="text-muted mb-0 small">Activa el programa y define cómo se acumulan los puntos.</p>
        </div>
        <a href="{{ route('loyalty.dashboard') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('loyalty.settings.update') }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-toggle-on me-2 text-muted"></i>Estado</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1"
                                   {{ old('enabled', $settings->enabled) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="enabled">Programa de fidelización activo</label>
                        </div>
                        <div class="text-muted small mt-1">Si está desactivado, las ventas no acumulan puntos.</div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-muted"></i>Reglas de acumulación</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Por cada (monto) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="earn_amount" class="form-control" min="0.01" step="0.01"
                                           value="{{ old('earn_amount', $settings->earn_amount) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Otorga (puntos) <span class="text-danger">*</span></label>
                                <input type="number" name="earn_points" class="form-control" min="1" step="1"
                                       value="{{ old('earn_points', $settings->earn_points) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Redondeo de bloques</label>
                                <select name="rounding" class="form-select" data-no-search>
                                    @foreach(['down' => 'Hacia abajo (floor)', 'nearest' => 'Al más cercano', 'up' => 'Hacia arriba (ceil)'] as $k => $lbl)
                                    <option value="{{ $k }}" {{ old('rounding', $settings->rounding) === $k ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Compra mínima para acumular</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" name="min_purchase" class="form-control" min="0" step="0.01"
                                           value="{{ old('min_purchase', $settings->min_purchase) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nombre de los puntos</label>
                                <input type="text" name="points_label" class="form-control" maxlength="50"
                                       value="{{ old('points_label', $settings->points_label) }}" placeholder="puntos">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Vencimiento <span class="text-muted">(meses; vacío = no vence)</span></label>
                                <div class="input-group">
                                    <input type="number" name="expiration_months" class="form-control" min="0" max="120" step="1"
                                           value="{{ old('expiration_months', $settings->expiration_months) }}" placeholder="No vence">
                                    <span class="input-group-text bg-light px-2">meses</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-left:4px solid var(--brand-red) !important;">
                    <div class="card-body p-4">
                        <div class="text-muted small mb-1">Regla actual</div>
                        <div class="fw-bold fs-5" id="rulePreview">—</div>
                        <hr>
                        <div class="text-muted small">Ejemplo: una venta de <strong>$100</strong> otorgaría
                            <strong id="exampleResult">—</strong>.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Guardar configuración</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const amt = document.querySelector('[name="earn_amount"]');
    const pts = document.querySelector('[name="earn_points"]');
    const rnd = document.querySelector('[name="rounding"]');
    const label = document.querySelector('[name="points_label"]');
    const rule = document.getElementById('rulePreview');
    const ex   = document.getElementById('exampleResult');

    function blocks(n, mode) {
        if (mode === 'up') return Math.ceil(n);
        if (mode === 'nearest') return Math.round(n);
        return Math.floor(n);
    }
    function update() {
        const a = parseFloat(amt.value) || 0;
        const p = parseInt(pts.value, 10) || 0;
        const lbl = (label.value || 'puntos').trim();
        if (a > 0 && p > 0) {
            rule.textContent = `Cada $${a.toFixed(2)} = ${p} ${lbl}`;
            ex.textContent = blocks(100 / a, rnd.value) * p + ' ' + lbl;
        } else {
            rule.textContent = '—'; ex.textContent = '—';
        }
    }
    [amt, pts, rnd, label].forEach(el => el && el.addEventListener('input', update));
    rnd && rnd.addEventListener('change', update);
    update();
})();
</script>
@endpush
@endsection
