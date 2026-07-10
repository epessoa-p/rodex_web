@extends('layouts.app')
@section('title', $warehouse->name)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4">
                <i class="bi bi-building me-2 text-danger"></i>{{ $warehouse->name }}
            </h1>
            <p class="text-muted mb-0 small">
                Código: <strong>{{ $warehouse->code }}</strong>
                @if($warehouse->location)
                · <i class="bi bi-geo-alt me-1"></i>{{ $warehouse->location }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.edit', auth()->user()->getCurrentCompany()))
            <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
            @endif
            <a href="{{ route('warehouses.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── Left: Movement form ─────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Info card --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 mb-0" style="font-size:.9rem">
                        <dt class="col-5 text-muted fw-normal">Empresa</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $warehouse->company?->name ?: '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Sucursal</dt>
                        <dd class="col-7 mb-0">{{ $warehouse->primaryBranch?->name ?: 'No asignada' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0"><code class="bg-light px-2 py-1 rounded border small">{{ $warehouse->code }}</code></dd>
                        @if($warehouse->description)
                        <dt class="col-5 text-muted fw-normal">Descripción</dt>
                        <dd class="col-7 mb-0 small">{{ $warehouse->description }}</dd>
                        @endif
                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7 mb-0">
                            @if($warehouse->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Movement form --}}
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.edit', auth()->user()->getCurrentCompany()))
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-plus-slash-minus me-2 text-muted"></i>Nuevo movimiento</h6>
                </div>
                <div class="card-body p-4">

                    @if($errors->any())
                    <div class="alert alert-danger border-0 alert-dismissible fade show py-2" role="alert">
                        <small><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('warehouses.movements.store', $warehouse) }}" method="POST" id="movementForm">
                        @csrf
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold small" for="product_id">Producto <span class="text-danger">*</span></label>
                                <select name="product_id" id="product_id" class="form-select form-select-sm @error('product_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar producto —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} (Stock: {{ number_format($p->current_stock, 0) }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small">Tipo de movimiento <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    @foreach(\App\Models\InventoryMovement::TYPES as $typeKey => $typeData)
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="type" id="type_{{ $typeKey }}"
                                               value="{{ $typeKey }}"
                                               {{ old('type', 'in') === $typeKey ? 'checked' : '' }}
                                               onchange="handleTypeChange('{{ $typeKey }}')">
                                        <label class="btn btn-outline-{{ $typeData['color'] }} btn-sm w-100 py-2" for="type_{{ $typeKey }}">
                                            @if($typeKey === 'in')<i class="bi bi-arrow-down-circle me-1"></i>
                                            @elseif($typeKey === 'out')<i class="bi bi-arrow-up-circle me-1"></i>
                                            @elseif($typeKey === 'transfer')<i class="bi bi-arrow-left-right me-1"></i>
                                            @else<i class="bi bi-sliders me-1"></i>@endif
                                            {{ $typeData['label'] }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Transfer: destination warehouse --}}
                            <div class="col-12 d-none" id="fieldDestWarehouse">
                                <label class="form-label fw-semibold small" for="destination_warehouse_id">
                                    Almacén destino <span class="text-danger">*</span>
                                </label>
                                <select name="destination_warehouse_id" id="destination_warehouse_id"
                                        class="form-select form-select-sm @error('destination_warehouse_id') is-invalid @enderror">
                                    <option value="">— Seleccionar destino —</option>
                                    @foreach($otherWarehouses as $ow)
                                    <option value="{{ $ow->id }}" {{ old('destination_warehouse_id') == $ow->id ? 'selected' : '' }}>
                                        {{ $ow->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('destination_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Adjustment: direction + reason --}}
                            <div class="col-12 d-none" id="fieldAdjustment">
                                <label class="form-label fw-semibold small">Dirección del ajuste</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="adjustment_direction" id="adj_increase" value="increase" checked>
                                        <label class="form-check-label small" for="adj_increase">
                                            <i class="bi bi-plus-circle text-success me-1"></i>Incremento
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="adjustment_direction" id="adj_decrease" value="decrease">
                                        <label class="form-check-label small" for="adj_decrease">
                                            <i class="bi bi-dash-circle text-danger me-1"></i>Reducción
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-none" id="fieldAdjustmentReason">
                                <label class="form-label fw-semibold small" for="adjustment_reason">Razón del ajuste</label>
                                <input type="text" id="adjustment_reason" name="adjustment_reason"
                                       class="form-control form-control-sm @error('adjustment_reason') is-invalid @enderror"
                                       value="{{ old('adjustment_reason') }}"
                                       maxlength="255"
                                       placeholder="Merma, daño, inventario físico...">
                                @error('adjustment_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="quantity">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" inputmode="numeric" id="quantity" name="quantity"
                                       class="form-control form-control-sm @error('quantity') is-invalid @enderror"
                                       value="{{ old('quantity') }}" required placeholder="0">
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="unit_cost">Costo unitario</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">$</span>
                                    <input type="number" step="0.01" min="0" id="unit_cost" name="unit_cost"
                                           class="form-control @error('unit_cost') is-invalid @enderror"
                                           value="{{ old('unit_cost') }}" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small" for="movement_date">Fecha y hora <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="movement_date" name="movement_date"
                                       class="form-control form-control-sm @error('movement_date') is-invalid @enderror"
                                       value="{{ old('movement_date', now()->format('Y-m-d\TH:i')) }}" required>
                                @error('movement_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small" for="reference">Referencia</label>
                                <input type="text" id="reference" name="reference"
                                       class="form-control form-control-sm @error('reference') is-invalid @enderror"
                                       value="{{ old('reference') }}"
                                       maxlength="100"
                                       placeholder="Nro. factura, orden, etc.">
                                @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold small" for="notes">Notas</label>
                                <textarea id="notes" name="notes"
                                          class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="bi bi-check-lg me-1"></i>Guardar movimiento
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Right: Movement history ──────────────────────────── --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Historial de movimientos</h6>
                    <span class="badge bg-light text-muted border fw-normal">
                        {{ $warehouse->inventoryMovements->count() }} movimiento{{ $warehouse->inventoryMovements->count() !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($warehouse->inventoryMovements->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                        <p class="mb-0">Sin movimientos registrados.</p>
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 small fw-semibold text-muted">Fecha</th>
                                    <th class="py-3 small fw-semibold text-muted">Producto</th>
                                    <th class="py-3 small fw-semibold text-muted">Tipo</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Cantidad</th>
                                    <th class="py-3 small fw-semibold text-muted text-end">Costo unit.</th>
                                    <th class="py-3 small fw-semibold text-muted pe-4">Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouse->inventoryMovements->sortByDesc('movement_date') as $movement)
                                @php $typeInfo = \App\Models\InventoryMovement::TYPES[$movement->type] ?? ['label' => $movement->type, 'color' => 'secondary']; @endphp
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="small">{{ $movement->movement_date?->format('d/m/Y') }}</div>
                                        <small class="text-muted">{{ $movement->movement_date?->format('H:i') }}</small>
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-semibold small">{{ $movement->product?->name ?: '—' }}</div>
                                        @if($movement->product)
                                        <small class="text-muted">{{ $movement->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-{{ $typeInfo['color'] }}-subtle text-{{ $typeInfo['color'] }} border border-{{ $typeInfo['color'] }}-subtle small">
                                            {{ $typeInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-end fw-semibold small">{{ number_format($movement->quantity, 0) }}</td>
                                    <td class="py-3 text-end small text-muted">${{ number_format($movement->unit_cost ?? 0, 2) }}</td>
                                    <td class="py-3 small text-muted pe-4">{{ $movement->user?->name ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function handleTypeChange(type) {
    const fieldDest       = document.getElementById('fieldDestWarehouse');
    const fieldAdj        = document.getElementById('fieldAdjustment');
    const fieldAdjReason  = document.getElementById('fieldAdjustmentReason');
    const destInput       = document.getElementById('destination_warehouse_id');

    fieldDest.classList.add('d-none');
    fieldAdj.classList.add('d-none');
    fieldAdjReason.classList.add('d-none');
    if (destInput) destInput.required = false;

    if (type === 'transfer') {
        fieldDest.classList.remove('d-none');
        if (destInput) destInput.required = true;
    } else if (type === 'adjustment') {
        fieldAdj.classList.remove('d-none');
        fieldAdjReason.classList.remove('d-none');
    }
}

// Init with current selection
document.addEventListener('DOMContentLoaded', function () {
    const checked = document.querySelector('input[name="type"]:checked');
    if (checked) handleTypeChange(checked.value);
});
</script>
@endpush

@endsection
