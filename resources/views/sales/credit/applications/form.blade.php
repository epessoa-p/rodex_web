@php
    $isEdit = isset($application);
    $action = $isEdit ? route('credit-applications.update', $application) : route('credit-applications.store');
@endphp

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

        {{-- Main column --}}
        <div class="col-lg-8">

            {{-- Datos principales --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Datos de la solicitud</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="client_id">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <select id="client_id" name="client_id"
                                    class="form-select @error('client_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar cliente —</option>
                                @foreach($clients as $c)
                                <option value="{{ $c->id }}" {{ old('client_id', $isEdit ? $application->client_id : '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="requested_amount">
                                Monto solicitado <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" id="requested_amount" name="requested_amount"
                                       class="form-control @error('requested_amount') is-invalid @enderror"
                                       value="{{ old('requested_amount', $isEdit ? $application->requested_amount : '') }}"
                                       required min="0.01" step="0.01"
                                       placeholder="0.00">
                            </div>
                            @error('requested_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="down_payment">Pago inicial</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" id="down_payment" name="down_payment"
                                       class="form-control @error('down_payment') is-invalid @enderror"
                                       value="{{ old('down_payment', $isEdit ? $application->down_payment : '0') }}"
                                       min="0" step="0.01"
                                       placeholder="0.00">
                            </div>
                            @error('down_payment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="payment_plan_id">Plan de pago</label>
                            <select id="payment_plan_id" name="payment_plan_id"
                                    class="form-select @error('payment_plan_id') is-invalid @enderror"
                                    onchange="onPlanChange(this)">
                                <option value="">— Sin plan —</option>
                                @foreach($plans as $p)
                                <option value="{{ $p->id }}"
                                        data-n="{{ $p->number_of_installments }}"
                                        data-freq="{{ $p->frequency_days }}"
                                        {{ old('payment_plan_id', $isEdit ? $application->payment_plan_id : '') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->number_of_installments }} cuotas c/{{ $p->frequency_days }}d)
                                </option>
                                @endforeach
                            </select>
                            @error('payment_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="installments_count">N° cuotas (manual)</label>
                            <input type="number" id="installments_count" name="installments_count"
                                   class="form-control @error('installments_count') is-invalid @enderror"
                                   value="{{ old('installments_count', $isEdit ? $application->installments_count : '') }}"
                                   min="1"
                                   placeholder="Se toma del plan si está seleccionado">
                            @error('installments_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="frequency_days">Frecuencia días (manual)</label>
                            <input type="number" id="frequency_days" name="frequency_days"
                                   class="form-control @error('frequency_days') is-invalid @enderror"
                                   value="{{ old('frequency_days', $isEdit ? $application->frequency_days : '') }}"
                                   min="1"
                                   placeholder="Se toma del plan si está seleccionado">
                            @error('frequency_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas de la solicitud</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Observaciones del cliente, motivo del crédito, etc.">{{ old('notes', $isEdit ? $application->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Garante --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-check me-2 text-muted"></i>Información del garante <span class="text-muted fw-normal fs-6">(opcional)</span></h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="guarantor_name">Nombre del garante</label>
                            <input type="text" id="guarantor_name" name="guarantor_name"
                                   class="form-control @error('guarantor_name') is-invalid @enderror"
                                   value="{{ old('guarantor_name', $isEdit ? $application->guarantor_name : '') }}"
                                   maxlength="255"
                                   placeholder="Nombre completo del garante">
                            @error('guarantor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="guarantor_phone">Teléfono del garante</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" id="guarantor_phone" name="guarantor_phone"
                                       class="form-control @error('guarantor_phone') is-invalid @enderror"
                                       value="{{ old('guarantor_phone', $isEdit ? $application->guarantor_phone : '') }}"
                                       maxlength="50"
                                       placeholder="+591 700 00000">
                            </div>
                            @error('guarantor_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="guarantor_notes">Notas del garante</label>
                            <textarea id="guarantor_notes" name="guarantor_notes" rows="2"
                                      class="form-control @error('guarantor_notes') is-invalid @enderror"
                                      placeholder="Relación con el cliente, bienes como garantía, etc.">{{ old('guarantor_notes', $isEdit ? $application->guarantor_notes : '') }}</textarea>
                            @error('guarantor_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- Right sidebar --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Resumen</h6>
                </div>
                <div class="card-body p-4">
                    <div class="rounded-3 border p-3 mb-4" style="background:#fafafa;">
                        <div class="text-muted small mb-1">Al seleccionar un plan, se pre-rellenan las cuotas y frecuencia.</div>
                        <div id="planSummary" class="mt-2 small d-none">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Cuotas</span>
                                <span class="fw-semibold" id="sumN">—</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Frecuencia</span>
                                <span class="fw-semibold" id="sumFreq">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $isEdit ? 'Guardar cambios' : 'Crear solicitud' }}
                        </button>
                        <a href="{{ route('credit-applications.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-x-lg me-1"></i>Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script>
function onPlanChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const n = opt.dataset.n;
    const freq = opt.dataset.freq;
    const summary = document.getElementById('planSummary');
    if (n && freq) {
        document.getElementById('installments_count').value = n;
        document.getElementById('frequency_days').value = freq;
        document.getElementById('sumN').textContent = n + ' cuotas';
        document.getElementById('sumFreq').textContent = freq + ' días';
        summary.classList.remove('d-none');
    } else {
        summary.classList.add('d-none');
    }
}
// Trigger on load if editing with a plan selected
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('payment_plan_id');
    if (sel && sel.value) onPlanChange(sel);
});
</script>
@endpush
