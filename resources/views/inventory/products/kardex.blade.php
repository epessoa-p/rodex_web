@extends('layouts.app')
@section('title', 'Kardex: ' . $product->name)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-journal-text me-2 text-danger"></i>Kardex</h1>
            <p class="text-muted mb-0 small">
                <strong>{{ $product->name }}</strong>
                · SKU: {{ $product->sku }}
                @if($product->category)· <i class="bi bi-tag me-1"></i>{{ $product->category->name }}@endif
                @if($product->brand)· <i class="bi bi-award me-1"></i>{{ $product->brand->name }}@endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-light border" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Imprimir
            </button>
            <a href="{{ route('products.show', $product) }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Almacén</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">Todos los almacenes</option>
                        @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>
                            {{ $w->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Kardex table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2 text-muted"></i>Movimientos</h6>
            <span class="badge bg-light text-muted border fw-normal">{{ $movements->count() }} registro{{ $movements->count() !== 1 ? 's' : '' }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="kardexTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-semibold" style="width:40px">#</th>
                            <th class="py-3 text-muted small fw-semibold">Fecha</th>
                            <th class="py-3 text-muted small fw-semibold">Almacén</th>
                            <th class="py-3 text-muted small fw-semibold">Tipo</th>
                            <th class="py-3 text-muted small fw-semibold">Referencia</th>
                            <th class="py-3 text-muted small fw-semibold text-end text-success">Entrada</th>
                            <th class="py-3 text-muted small fw-semibold text-end text-danger">Salida</th>
                            <th class="py-3 text-muted small fw-semibold text-end">Saldo</th>
                            <th class="py-3 text-muted small fw-semibold">Usuario</th>
                            <th class="py-3 text-muted small fw-semibold pe-4">Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $i => $mov)
                        @php
                            $typeInfo = \App\Models\InventoryMovement::TYPES[$mov->type] ?? ['label' => $mov->type, 'color' => 'secondary'];
                        @endphp
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3 text-muted" style="font-size:.8rem">{{ $i + 1 }}</td>
                            <td class="py-3">
                                <div class="fw-semibold" style="font-size:.88rem">{{ $mov->movement_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $mov->movement_date->format('H:i') }}</small>
                            </td>
                            <td class="py-3 small">
                                {{ $mov->warehouse?->name ?: '—' }}
                                @if($mov->destinationWarehouse)
                                <div class="text-muted" style="font-size:.78rem">
                                    <i class="bi bi-arrow-right"></i> {{ $mov->destinationWarehouse->name }}
                                </div>
                                @endif
                            </td>
                            <td class="py-3">
                                <span class="badge bg-{{ $typeInfo['color'] }}-subtle text-{{ $typeInfo['color'] }} border border-{{ $typeInfo['color'] }}-subtle small">
                                    {{ $typeInfo['label'] }}
                                </span>
                            </td>
                            <td class="py-3 text-muted small">{{ $mov->reference ?: '—' }}</td>
                            <td class="py-3 text-end fw-semibold text-success">
                                {{ $mov->entry !== null ? number_format($mov->entry, 2) : '—' }}
                            </td>
                            <td class="py-3 text-end fw-semibold text-danger">
                                {{ $mov->exit !== null ? number_format($mov->exit, 2) : '—' }}
                            </td>
                            <td class="py-3 text-end fw-bold {{ $mov->balance < 0 ? 'text-danger' : '' }}">
                                {{ number_format($mov->balance, 2) }}
                            </td>
                            <td class="py-3 text-muted" style="font-size:.82rem">{{ $mov->user?->name ?: '—' }}</td>
                            <td class="py-3 text-muted pe-4" style="font-size:.8rem;max-width:180px">
                                {{ Str::limit($mov->notes, 40) ?: '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-journal fs-1 d-block mb-2 opacity-25"></i>
                                Sin movimientos para los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
@media print {
    .app-sidebar, .app-topbar, .btn, form, .card-header .btn { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    .app-shell { display: block !important; }
}
</style>
@endpush
@endsection
