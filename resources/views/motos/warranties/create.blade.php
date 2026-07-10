@extends('layouts.app')
@section('title', 'Nueva Garantía')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-shield-plus me-2 text-danger"></i>Nueva Garantía</h1>
            <p class="text-muted mb-0 small">Registra una garantía para una moto vendida.</p>
        </div>
        <a href="{{ route('warranties.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('warranties.store') }}" method="POST">
        @csrf

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-check me-2 text-muted"></i>Datos de la garantía</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="moto_unit_id">
                                    Moto <span class="text-danger">*</span>
                                </label>
                                <select id="moto_unit_id" name="moto_unit_id"
                                        class="form-select @error('moto_unit_id') is-invalid @enderror"
                                        required onchange="onUnitSelect(this)">
                                    <option value="">— Seleccionar moto —</option>
                                    @foreach($units as $u)
                                    @php
                                        $clientName = $u->sale?->client?->full_name ?? null;
                                    @endphp
                                    <option value="{{ $u->id }}"
                                            data-client="{{ $clientName }}"
                                            {{ old('moto_unit_id', $selectedUnit?->id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->display_name }}{{ $clientName ? ' — ' . $clientName : '' }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('moto_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text text-muted small">Solo se muestran unidades vendidas o entregadas.</div>
                            </div>

                            {{-- Cliente auto-mostrado --}}
                            <div class="col-12" id="clientBox" style="display:none;">
                                <div class="d-flex align-items-center gap-2 p-2 rounded-2 border bg-light small text-muted">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Cliente: <strong id="clientNameDisplay">—</strong></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="start_date">
                                    Fecha inicio <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="start_date" name="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="months">
                                    Meses <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" id="months" name="months"
                                           class="form-control @error('months') is-invalid @enderror"
                                           value="{{ old('months', 12) }}"
                                           min="1" max="120" required>
                                    <span class="input-group-text bg-light text-muted">meses</span>
                                </div>
                                @error('months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="coverage">Cobertura</label>
                                <textarea id="coverage" name="coverage"
                                          class="form-control @error('coverage') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Describe qué cubre la garantía: motor, transmisión, partes eléctricas...">{{ old('coverage') }}</textarea>
                                @error('coverage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas adicionales</label>
                                <textarea id="notes" name="notes"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Condiciones especiales, exclusiones, observaciones...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Resumen</h6>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-0">
                            La garantía se registrará como <strong>Vigente</strong>.
                            Podrás cambiar el estado desde el detalle de la garantía.
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-shield-plus me-1"></i>Registrar garantía
                            </button>
                            <a href="{{ route('warranties.index') }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </form>

</div>

@push('scripts')
<script>
function onUnitSelect(sel) {
    const opt = sel.options[sel.selectedIndex];
    const client = opt.dataset.client || '';
    const box = document.getElementById('clientBox');
    if (client && sel.value) {
        document.getElementById('clientNameDisplay').textContent = client;
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('moto_unit_id');
    if (sel && sel.value) onUnitSelect(sel);
});
</script>
@endpush

@endsection
