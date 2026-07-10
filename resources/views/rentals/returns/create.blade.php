@extends('layouts.app')
@section('title', 'Devolución · ' . $rental->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-in-down me-2 text-danger"></i>Devolución de moto</h1>
            <p class="text-muted mb-0 small">{{ $rental->code }} · {{ $rental->client?->full_name }} · {{ $rental->motoUnit?->display_name }}</p>
        </div>
        <a href="{{ route('rentals.show', $rental) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @include('rentals.partials.flash')

    @if($extraDays > 0)
    <div class="alert alert-warning border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle me-2"></i>El alquiler venció hace <strong>{{ $extraDays }}</strong> día(s). Mora sugerida: <strong>Bs. {{ number_format($suggestedLateFee, 2) }}</strong>.
    </div>
    @endif

    <form action="{{ route('rentals.return.store', $rental) }}" method="POST" id="returnForm" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2 text-muted"></i>Inspección de entrada</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="return_mileage">Kilometraje de retorno</label>
                                <input type="number" id="return_mileage" name="return_mileage" class="form-control" min="0" value="{{ old('return_mileage', $rental->delivery_mileage) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="return_fuel">Nivel de combustible</label>
                                <select id="return_fuel" name="return_fuel" class="form-select">
                                    <option value="">— Seleccionar —</option>
                                    @foreach(['Lleno','3/4','1/2','1/4','Reserva'] as $f)
                                    <option value="{{ $f }}" {{ old('return_fuel') === $f ? 'selected' : '' }}>{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="return_notes">Notas / daños observados</label>
                                <textarea id="return_notes" name="return_notes" rows="3" class="form-control" placeholder="Estado de la moto, daños, faltantes...">{{ old('return_notes') }}</textarea>
                            </div>
                        </div>

                        @include('rentals.partials.inspection-fields', ['prefix' => 'entrada'])
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-muted"></i>Cargos / Penalizaciones</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="late_fee">Mora por días extra</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Bs.</span>
                                    <input type="number" id="late_fee" name="late_fee" class="form-control" min="0" step="0.01" value="{{ old('late_fee', number_format($suggestedLateFee, 2, '.', '')) }}" oninput="recalc()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="damage_fee">Daños / otros cargos</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Bs.</span>
                                    <input type="number" id="damage_fee" name="damage_fee" class="form-control" min="0" step="0.01" value="{{ old('damage_fee', '0.00') }}" oninput="recalc()">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="needs_maintenance" name="needs_maintenance" value="1" {{ old('needs_maintenance') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="needs_maintenance"><i class="bi bi-tools me-1"></i>Requiere mantenimiento (crea Orden de Taller)</label>
                        </div>
                        <p class="text-muted small ms-4 mb-0">Si se marca, la moto pasa a <strong>mantenimiento</strong> y se genera una OT. Si no, vuelve a <strong>disponible</strong>.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-safe2 me-2 text-muted"></i>Liquidación de depósito</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Depósito retenido</span><span class="fw-semibold">Bs. {{ number_format($rental->deposit, 2) }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Penalizaciones previas</span><span class="fw-semibold">Bs. {{ number_format($rental->penalties_total, 2) }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Cargos en esta devolución</span><span class="fw-semibold text-danger" id="sumNewFees">Bs. 0.00</span></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2"><span>A reembolsar</span><span class="fs-5" id="sumRefund">Bs. 0.00</span></div>

                        <div class="form-check form-switch mt-3 mb-3">
                            <input class="form-check-input" type="checkbox" id="refund_deposit" name="refund_deposit" value="1" {{ old('refund_deposit', '1') ? 'checked' : '' }} onchange="recalc()">
                            <label class="form-check-label small" for="refund_deposit">Reembolsar depósito a caja</label>
                        </div>
                        <label class="form-label small fw-semibold" for="method">Método</label>
                        <select id="method" name="method" class="form-select form-select-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="qr">QR</option>
                        </select>
                        <p class="text-muted mt-2 mb-0" style="font-size:.72rem;"><i class="bi bi-info-circle me-1"></i>El reembolso y el cobro de penalizaciones requieren tu caja abierta.</p>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <button type="submit" class="btn btn-primary w-100" id="btnReturn"><i class="bi bi-check-lg me-1"></i>Registrar devolución</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const DEPOSIT = {{ (float) $rental->deposit }};
const PREV_PENALTIES = {{ (float) $rental->penalties_total }};
function recalc() {
    const late = parseFloat(document.getElementById('late_fee').value) || 0;
    const dmg  = parseFloat(document.getElementById('damage_fee').value) || 0;
    const refund = document.getElementById('refund_deposit').checked;
    const newFees = late + dmg;
    document.getElementById('sumNewFees').textContent = 'Bs. ' + newFees.toFixed(2);
    const totalPen = PREV_PENALTIES + newFees;
    let refundAmt = refund ? Math.max(0, DEPOSIT - totalPen) : 0;
    document.getElementById('sumRefund').textContent = 'Bs. ' + refundAmt.toFixed(2);
}
document.addEventListener('DOMContentLoaded', recalc);
</script>
@endpush
@endsection
