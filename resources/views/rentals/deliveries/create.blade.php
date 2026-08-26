@extends('layouts.app')
@section('title', 'Entregar Moto · ' . $rental->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-up me-2 text-danger"></i>Entrega de moto</h1>
            <p class="text-muted mb-0 small">{{ $rental->code }} · {{ $rental->client?->full_name }} · {{ $rental->motoUnit?->display_name }}</p>
        </div>
        <a href="{{ route('rentals.show', $rental) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @include('rentals.partials.flash')

    <form action="{{ route('rentals.deliver.store', $rental) }}" method="POST" id="deliverForm" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2 text-muted"></i>Inspección de salida</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="delivery_mileage">Kilometraje de salida</label>
                                <input type="number" id="delivery_mileage" name="delivery_mileage" class="form-control" min="0" value="{{ old('delivery_mileage') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="delivery_fuel">Nivel de combustible</label>
                                <select id="delivery_fuel" name="delivery_fuel" class="form-select">
                                    <option value="">— Seleccionar —</option>
                                    @foreach(['Lleno','3/4','1/2','1/4','Reserva'] as $f)
                                    <option value="{{ $f }}" {{ old('delivery_fuel') === $f ? 'selected' : '' }}>{{ $f }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="delivery_notes">Notas de entrega</label>
                                <textarea id="delivery_notes" name="delivery_notes" rows="3" class="form-control" placeholder="Estado de la moto, accesorios entregados, observaciones...">{{ old('delivery_notes') }}</textarea>
                            </div>
                        </div>

                        @include('rentals.partials.inspection-fields', ['prefix' => 'salida'])
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Cobro</h6>
                    </div>
                    <div class="card-body p-4">
                        @if($rental->isRenta())
                        <div class="alert alert-info border-0 small py-2"><i class="bi bi-info-circle me-1"></i>Renta periódica: en la entrega solo se cobra el <strong>depósito</strong>. Las cuotas se cobran en <strong>Cobros</strong>.</div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Renta total ({{ $rental->billing_period_label }})</span><span class="text-muted">{{ money($rental->rental_total) }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Depósito</span><span class="fw-semibold text-info">{{ money($rental->deposit) }}</span></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3"><span>A cobrar ahora</span><span class="fs-5">{{ money($rental->deposit) }}</span></div>
                        @else
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Alquiler ({{ $rental->days }}d)</span><span class="fw-semibold">{{ money($rental->rental_total) }}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Depósito</span><span class="fw-semibold text-info">{{ money($rental->deposit) }}</span></div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3"><span>A cobrar ahora</span><span class="fs-5">{{ money($rental->rental_total + $rental->deposit) }}</span></div>
                        @endif

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="collect_now" name="collect_now" value="1" {{ old('collect_now', '1') ? 'checked' : '' }} onchange="document.getElementById('methodWrap').style.display=this.checked?'block':'none';">
                            <label class="form-check-label small" for="collect_now">Cobrar a caja en la entrega</label>
                        </div>
                        <div id="methodWrap" style="{{ old('collect_now', '1') ? '' : 'display:none;' }}">
                            <label class="form-label small fw-semibold" for="method">Método de pago</label>
                            <select id="method" name="method" class="form-select form-select-sm">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="qr">QR</option>
                            </select>
                            <p class="text-muted mt-2 mb-0" style="font-size:.72rem;"><i class="bi bi-info-circle me-1"></i>Requiere tu caja abierta.</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <button type="submit" class="btn btn-primary w-100" id="btnDeliver"><i class="bi bi-check-lg me-1"></i>Confirmar entrega</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
