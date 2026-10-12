@extends('layouts.app')

@section('title', 'Kardex — Movimientos de inventario')

@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1"><i class="bi bi-journal-text"></i> Kardex</h1>
            <p class="text-muted mb-0">Historial completo de movimientos de inventario.</p>
        </div>
        <button class="btn btn-light border" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Producto</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">Todos los productos</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Almacén</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Tipo</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach(\App\Models\InventoryMovement::TYPES as $key => $info)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>
                                {{ $info['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
            </form>
            @if(request()->hasAny(['product_id','warehouse_id','type','date_from','date_to']))
                <div class="mt-2">
                    <a href="{{ route('inventory.kardex') }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-x me-1"></i>Limpiar filtros
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table me-2 text-muted"></i>Movimientos</h6>
            <span class="badge bg-light text-muted border">{{ $movements->total() }} registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Almacén</th>
                            <th>Tipo</th>
                            <th class="text-end text-success">Entrada</th>
                            <th class="text-end text-danger">Salida</th>
                            <th>Referencia</th>
                            <th>Usuario</th>
                            <th>Notas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $mov)
                        @php
                            $typeInfo = \App\Models\InventoryMovement::TYPES[$mov->type] ?? ['label' => $mov->type, 'color' => 'secondary'];
                            $delta = \App\Services\Inventory\KardexMath::delta($mov, $warehouseId ?? null);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold" style="font-size:.85rem;">{{ $mov->movement_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $mov->movement_date->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size:.85rem;">{{ $mov->product?->name ?: '—' }}</div>
                                <small class="text-muted">{{ $mov->product?->sku }}</small>
                            </td>
                            <td style="font-size:.85rem;">
                                {{ $mov->warehouse?->name ?: '—' }}
                                @if($mov->type === 'transfer' && $mov->destinationWarehouse)
                                    <span class="text-muted">→ {{ $mov->destinationWarehouse->name }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $typeInfo['color'] }}-subtle text-{{ $typeInfo['color'] }} border border-{{ $typeInfo['color'] }}-subtle">
                                    {{ $typeInfo['label'] }}
                                </span>
                            </td>
                            <td class="text-end text-success fw-semibold">
                                {{ $delta > 0 ? \App\Services\Inventory\KardexMath::qty($delta) : '—' }}
                            </td>
                            <td class="text-end text-danger fw-semibold">
                                @if($delta < 0){{ \App\Services\Inventory\KardexMath::qty(-$delta) }}@elseif($mov->type === 'transfer')<span class="text-muted fw-normal" title="Transferencia entre almacenes: no cambia el total"><i class="bi bi-arrow-left-right"></i> {{ \App\Services\Inventory\KardexMath::qty((float) $mov->quantity) }}</span>@else—@endif
                            </td>
                            <td class="text-muted" style="font-size:.82rem;">{{ $mov->reference ?: '—' }}</td>
                            <td style="font-size:.82rem;">{{ $mov->user?->name ?: '—' }}</td>
                            <td class="text-muted" style="font-size:.8rem;">{{ Str::limit($mov->notes, 35) ?: '—' }}</td>
                            <td>
                                @if($mov->product)
                                <a href="{{ route('products.kardex', $mov->product) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Kardex del producto">
                                    <i class="bi bi-journal-bookmark" style="font-size:.75rem;"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-journal fs-1 d-block mb-2 opacity-25"></i>
                                Sin movimientos con los filtros aplicados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($totals['count'] > 0)
                    @php $K = \App\Services\Inventory\KardexMath::class; @endphp
                    {{-- Totales de TODO lo filtrado (todas las páginas) --}}
                    <tfoot class="table-light">
                        <tr class="fw-semibold">
                            <td colspan="4">
                                Totales del filtro
                                <div class="text-muted fw-normal small">
                                    {{ $totals['count'] }} {{ $totals['count'] === 1 ? 'movimiento' : 'movimientos' }}{{ $movements->lastPage() > 1 ? ' · todas las páginas' : '' }}
                                </div>
                            </td>
                            <td class="text-end text-success">{{ $K::qty($totals['entries']) }}</td>
                            <td class="text-end text-danger">{{ $K::qty($totals['exits']) }}</td>
                            <td colspan="4">
                                @if($totals['single_product'])
                                    Neto: <span class="{{ $totals['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ $totals['net'] > 0 ? '+' : '' }}{{ $K::qty($totals['net']) }}</span>
                                    <span class="text-muted fw-normal small">(entradas − salidas)</span>
                                @else
                                    <span class="text-muted fw-normal small"><i class="bi bi-info-circle me-1"></i>Unidades de varios productos: elige un producto para ver el neto.</span>
                                @endif
                                @if($totals['transfers'] > 0)
                                    <div class="text-muted fw-normal small"><i class="bi bi-arrow-left-right me-1"></i>{{ $K::qty($totals['transfers']) }} transferidas entre almacenes (no cambian el total; elige un almacén para verlas como entrada o salida).</div>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $movements->links() }}</div>

</div>

@push('styles')
<style>
@media print {
    .app-sidebar, .app-topbar, form, .btn, nav { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
@endpush

@endsection
