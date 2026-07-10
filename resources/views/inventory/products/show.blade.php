@extends('layouts.app')
@section('title', $product->name)
@section('page')
<div class="container-fluid">

    {{-- ── Hero card ────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-top:3px solid var(--brand-red) !important">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                {{-- Photo + info --}}
                <div class="d-flex gap-4 flex-wrap">
                    {{-- Main photo --}}
                    @php $mainPhoto = $product->mainPhoto(); @endphp
                    <div class="flex-shrink-0">
                        @if($mainPhoto)
                        <img src="{{ $mainPhoto->url }}" alt="{{ $product->name }}"
                             class="rounded-3 border object-fit-cover shadow-sm"
                             style="width:120px;height:120px;object-fit:cover">
                        @else
                        <div class="rounded-3 bg-light border d-flex align-items-center justify-content-center shadow-sm"
                             style="width:120px;height:120px;font-size:2.5rem;font-weight:800;color:#aaa">
                            {{ strtoupper(substr($product->name, 0, 1)) }}
                        </div>
                        @endif

                        {{-- Thumbnail strip --}}
                        @if($product->photos->count() > 1)
                        <div class="d-flex gap-1 mt-2 flex-wrap" style="max-width:120px">
                            @foreach($product->photos->take(5) as $photo)
                            <img src="{{ $photo->url }}" alt=""
                                 class="rounded border object-fit-cover {{ $photo->is_main ? 'border-danger border-2' : '' }}"
                                 style="width:32px;height:32px;object-fit:cover;cursor:pointer"
                                 onclick="document.querySelector('.hero-main-img').src='{{ $photo->url }}'">
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Product info --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="fw-bold mb-0 fs-4">{{ $product->name }}</h2>
                            @if($product->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                            <span class="text-muted small"><i class="bi bi-upc-scan me-1"></i>SKU: <strong>{{ $product->sku }}</strong></span>
                            @if($product->barcode)
                            <span class="text-muted small"><i class="bi bi-barcode me-1"></i>{{ $product->barcode }}</span>
                            @endif
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            @if($product->category)
                            <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                                <i class="bi bi-tag me-1"></i>{{ $product->category->name }}
                            </span>
                            @endif
                            @if($product->brand)
                            <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                                <i class="bi bi-award me-1"></i>{{ $product->brand->name }}
                            </span>
                            @endif
                            <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                                <i class="bi bi-rulers me-1"></i>{{ $product->unit }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex gap-2 align-items-start">
                    <a href="{{ route('products.kardex', $product) }}" class="btn btn-light border">
                        <i class="bi bi-journal-text me-1"></i>Kardex
                    </a>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('products.index') }}" class="btn btn-light border">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>

            </div>
        </div>
    </div>

    {{-- ── KPI row ──────────────────────────────────────────────────── --}}
    @php
        $margin = $product->price > 0 ? (($product->price - $product->cost) / $product->price * 100) : 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold text-uppercase" style="letter-spacing:.04em">Precio venta</p>
                    <p class="fw-bold fs-5 mb-0 text-success">${{ number_format($product->price, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold text-uppercase" style="letter-spacing:.04em">Precio compra</p>
                    <p class="fw-bold fs-5 mb-0 text-muted">${{ number_format($product->cost, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold text-uppercase" style="letter-spacing:.04em">Margen</p>
                    <p class="fw-bold fs-5 mb-0 {{ $margin >= 0 ? 'text-primary' : 'text-danger' }}">
                        {{ number_format($margin, 1) }}%
                    </p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold text-uppercase" style="letter-spacing:.04em">Stock global</p>
                    @php $lowGlobal = ($product->min_stock ?? 0) > 0 && $globalStock <= $product->min_stock; @endphp
                    <p class="fw-bold fs-5 mb-0 {{ $lowGlobal ? 'text-danger' : 'text-dark' }}">
                        {{ number_format($globalStock, 2) }}
                        @if($lowGlobal)<i class="bi bi-exclamation-triangle-fill text-warning ms-1 fs-6"></i>@endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- ── Stock por almacén ──────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-buildings me-2 text-muted"></i>Stock por almacén</h6>
                </div>
                <div class="card-body p-0">
                    @if($stockByWarehouse->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                        No hay almacenes configurados.
                    </div>
                    @else
                    <ul class="nav nav-tabs px-4 pt-3 border-0" id="stockTabs">
                        <li class="nav-item">
                            <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-global">
                                <i class="bi bi-globe me-1"></i>Global
                            </button>
                        </li>
                        @foreach($stockByWarehouse as $wid => $ws)
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-wh-{{ $wid }}">
                                {{ $ws['warehouse']->name }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                    <div class="tab-content px-4 pb-4 pt-3">
                        <div class="tab-pane fade show active" id="tab-global">
                            <div class="d-flex align-items-center gap-3">
                                <div class="display-6 fw-bold">{{ number_format($globalStock, 2) }}</div>
                                <div>
                                    <p class="mb-0 text-muted">unidades en total</p>
                                    @if(($product->min_stock ?? 0) > 0)
                                    <p class="mb-0 small text-muted">Stock mínimo: {{ $product->min_stock }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                @foreach($stockByWarehouse as $wid => $ws)
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-3 bg-light border">
                                        <p class="fw-semibold mb-1 small">{{ $ws['warehouse']->name }}</p>
                                        <p class="mb-0 fw-bold {{ $ws['stock'] < 0 ? 'text-danger' : '' }}">
                                            {{ number_format($ws['stock'], 2) }} <span class="text-muted fw-normal small">{{ $product->unit }}</span>
                                        </p>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @foreach($stockByWarehouse as $wid => $ws)
                        <div class="tab-pane fade" id="tab-wh-{{ $wid }}">
                            <div class="d-flex align-items-center gap-3">
                                <div class="display-6 fw-bold {{ $ws['stock'] < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($ws['stock'], 2) }}
                                </div>
                                <div>
                                    <p class="mb-0 text-muted">{{ $product->unit }} en {{ $ws['warehouse']->name }}</p>
                                    @if($ws['warehouse']->location)
                                    <p class="mb-0 small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $ws['warehouse']->location }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── Recent movements ──────────────────────────────── --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Últimos movimientos</h6>
                    <a href="{{ route('products.kardex', $product) }}" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-journal-text me-1"></i>Ver Kardex completo
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($recentMovements->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>Sin movimientos registrados.
                    </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-2 small fw-semibold text-muted">Fecha</th>
                                    <th class="py-2 small fw-semibold text-muted">Almacén</th>
                                    <th class="py-2 small fw-semibold text-muted">Tipo</th>
                                    <th class="py-2 small fw-semibold text-muted text-end">Cantidad</th>
                                    <th class="py-2 small fw-semibold text-muted pe-4">Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMovements as $mov)
                                @php $typeInfo = \App\Models\InventoryMovement::TYPES[$mov->type] ?? ['label' => $mov->type, 'color' => 'secondary']; @endphp
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2 small">{{ $mov->movement_date?->format('d/m/Y H:i') }}</td>
                                    <td class="py-2 small">{{ $mov->warehouse?->name ?: '—' }}</td>
                                    <td class="py-2">
                                        <span class="badge bg-{{ $typeInfo['color'] }}-subtle text-{{ $typeInfo['color'] }} border border-{{ $typeInfo['color'] }}-subtle small">
                                            {{ $typeInfo['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-end small fw-semibold">{{ number_format($mov->quantity, 0) }}</td>
                                    <td class="py-2 small text-muted pe-4">{{ $mov->user?->name ?: '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

        </div>
        <div class="col-lg-4">

            {{-- ── Details card ────────────────────────────────────── --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-card-text me-2 text-muted"></i>Detalles</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 mb-0" style="font-size:.9rem">
                        <dt class="col-5 text-muted fw-normal">Unidad</dt>
                        <dd class="col-7 fw-semibold mb-0">{{ $product->unit }}</dd>

                        @if($product->barcode)
                        <dt class="col-5 text-muted fw-normal">Cód. barras</dt>
                        <dd class="col-7 fw-semibold mb-0">{{ $product->barcode }}</dd>
                        @endif

                        <dt class="col-5 text-muted fw-normal">Stock mín.</dt>
                        <dd class="col-7 fw-semibold mb-0">{{ $product->min_stock ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Categoría</dt>
                        <dd class="col-7 mb-0">{{ $product->category?->name ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Marca</dt>
                        <dd class="col-7 mb-0">{{ $product->brand?->name ?: '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Creado</dt>
                        <dd class="col-7 text-muted mb-0 small">{{ $product->created_at->format('d/m/Y') }}</dd>
                    </dl>
                </div>
            </div>

            {{-- ── Description ────────────────────────────────────── --}}
            @if($product->description)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-text-paragraph me-2 text-muted"></i>Descripción</h6>
                </div>
                <div class="card-body p-4">
                    <p class="mb-0 text-muted small" style="line-height:1.7">{{ $product->description }}</p>
                </div>
            </div>
            @endif

            {{-- ── Modelos compatibles ────────────────────────────── --}}
            @if($product->motoModels->count())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-bicycle me-2 text-muted"></i>Modelos compatibles</h6>
                </div>
                <div class="card-body p-4 d-flex flex-wrap gap-2">
                    @foreach($product->motoModels as $m)
                    <span class="badge bg-light text-dark border" style="font-size:.8rem;">
                        <i class="bi bi-bicycle me-1 text-muted"></i>{{ $m->display_name }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
