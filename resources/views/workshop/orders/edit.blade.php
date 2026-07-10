@extends('layouts.app')
@section('title', 'Editar OT: ' . $order->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Editar OT</h1>
            <p class="text-muted mb-0 small">{{ $order->code }}</p>
        </div>
        <a href="{{ route('workshop.orders.show', $order) }}" class="btn btn-light border">
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

    <form action="{{ route('workshop.orders.update', $order) }}" method="POST" id="editOtForm">
        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Cliente y vehículo --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Cliente y vehículo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="client_id">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select id="client_id" name="client_id"
                                        class="form-select @error('client_id') is-invalid @enderror"
                                        required onchange="filterVehiclesByClient()">
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}"
                                            {{ old('client_id', $order->client_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="vehicle_id">
                                    Vehículo <span class="text-danger">*</span>
                                </label>
                                <select id="vehicle_id" name="vehicle_id"
                                        class="form-select @error('vehicle_id') is-invalid @enderror"
                                        required>
                                    <option value="">— Seleccionar vehículo —</option>
                                    @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}"
                                            data-client="{{ $v->client_id }}"
                                            {{ old('vehicle_id', $order->vehicle_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->display_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    ¿No aparece el vehículo?
                                    <a href="{{ route('vehicles.create') }}" target="_blank">Regístralo aquí</a>.
                                </div>
                            </div>

                            @if(isset($branches) && $branches->count() > 1)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="branch_id">Sucursal</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">— Seleccionar sucursal —</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}"
                                            {{ old('branch_id', $order->branch_id) == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- Datos de recepción --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2 text-muted"></i>Datos de recepción</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="reception_date">
                                    Fecha de recepción <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="reception_date" name="reception_date"
                                       class="form-control @error('reception_date') is-invalid @enderror"
                                       value="{{ old('reception_date', \Carbon\Carbon::parse($order->reception_date)->format('Y-m-d')) }}"
                                       required>
                                @error('reception_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="mileage">Kilometraje</label>
                                <div class="input-group">
                                    <input type="number" id="mileage" name="mileage" min="0"
                                           class="form-control @error('mileage') is-invalid @enderror"
                                           value="{{ old('mileage', $order->mileage) }}"
                                           placeholder="0">
                                    <span class="input-group-text bg-light">km</span>
                                </div>
                                @error('mileage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="fuel_level">Combustible</label>
                                <select id="fuel_level" name="fuel_level"
                                        class="form-select @error('fuel_level') is-invalid @enderror">
                                    <option value="">— Seleccionar —</option>
                                    @foreach(['vacio' => 'Vacío', '1/4' => '1/4', '1/2' => '1/2', '3/4' => '3/4', 'lleno' => 'Lleno'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('fuel_level', $order->fuel_level) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('fuel_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="reported_issue">Falla reportada</label>
                                <textarea id="reported_issue" name="reported_issue" rows="3"
                                          class="form-control @error('reported_issue') is-invalid @enderror"
                                          placeholder="Falla o síntomas reportados por el cliente...">{{ old('reported_issue', $order->reported_issue) }}</textarea>
                                @error('reported_issue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="received_items">Objetos / accesorios recibidos</label>
                                <textarea id="received_items" name="received_items" rows="2"
                                          class="form-control @error('received_items') is-invalid @enderror"
                                          placeholder="Ej: Llaves de repuesto, documentos...">{{ old('received_items', $order->received_items) }}</textarea>
                                @error('received_items')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas adicionales</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones internas...">{{ old('notes', $order->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Guardar cambios</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Guardar cambios
                            </button>
                            <a href="{{ route('workshop.orders.show', $order) }}" class="btn btn-light border w-100">
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
function filterVehiclesByClient() {
    const clientId = document.getElementById('client_id').value;
    const vehicleSelect = document.getElementById('vehicle_id');
    const options = vehicleSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) return;
        if (!clientId || opt.dataset.client === clientId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });

    const current = vehicleSelect.options[vehicleSelect.selectedIndex];
    if (current && current.value && clientId && current.dataset.client !== clientId) {
        vehicleSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    filterVehiclesByClient();
});
</script>
@endpush

@endsection
