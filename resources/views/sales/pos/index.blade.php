@extends('layouts.app')
@section('title', 'Punto de venta')
@section('page')

@if(!$session)
{{-- ─── EMPTY STATE: no open register ─────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-center" style="min-height:65vh;">
    <div class="text-center px-4" style="max-width:420px;">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
             style="width:90px;height:90px;background:rgba(230,57,70,.07);">
            <i class="bi bi-safe2 text-danger" style="font-size:2.4rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Caja cerrada</h4>
        <p class="text-muted mb-4">Debes abrir tu caja registradora antes de operar el punto de venta.</p>
        <a href="{{ route('cash-registers.index') }}" class="btn btn-primary px-5">
            <i class="bi bi-safe2 me-2"></i>Ir a Cajas
        </a>
    </div>
</div>

@else
{{-- ─── FULL POS LAYOUT ─────────────────────────────────────────────── --}}

@php
    $productsData = $products->map(function($p) use ($warehouseId, $activeStock) {
        $photos = [];
        foreach ($p->photos as $ph) {
            $photos[] = $ph->url;
        }
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'code'             => $p->code,
            'price'            => (float) $p->price,
            'cost'             => (float) $p->cost,
            'stock'            => $warehouseId ? (float) ($activeStock[$p->id] ?? 0) : (float) $p->current_stock,
            'category'         => $p->category?->name ?? '',
            'category_id'      => $p->category_id,
            'brand'            => $p->brand?->name ?? '',
            'description'      => $p->description ?? '',
            'compatible_models'=> $p->motoModels->pluck('display_name')->implode(', '),
            'model_ids'        => $p->motoModels->pluck('id')->values()->all(),
            'photo'            => count($photos) > 0 ? $photos[0] : null,
            'photos'           => $photos,
        ];
    })->values();

    $clientsJson = $clients->map(function($c) {
        return [
            'id'        => $c->id,
            'name'      => $c->full_name,
            'id_number' => $c->id_number ?? '',
        ];
    })->values();

    // Datos para el modal "stock en otros almacenes"
    $warehousesJson = $warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])->values();
@endphp

<div class="pos-wrapper">

    {{-- ── TOP BAR ────────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-shrink-0 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h1 class="mb-0 fw-bold fs-5"><i class="bi bi-cart3 me-2 text-danger"></i>Punto de Venta</h1>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1 px-3 py-2">
                <span class="rounded-circle bg-success" style="width:7px;height:7px;display:inline-block;"></span>
                {{ $session->cashRegister->branch->name ?? 'Sucursal' }}
                &nbsp;&middot;&nbsp;
                {{ $session->cashRegister->name ?? 'Caja' }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#stockModal">
                <i class="bi bi-boxes me-1"></i>Stock por almacén
            </button>
            @if(!empty($loyaltyEnabled) && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty.redeem', auth()->user()->getCurrentCompany())))
            <button type="button" class="btn btn-warning text-dark btn-sm fw-semibold" onclick="openRedeemModal()">
                <i class="bi bi-gift me-1"></i>Canjear puntos
            </button>
            @endif
            <a href="{{ route('sales.index') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-list-ul me-1"></i>Ventas
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show flex-shrink-0" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-3">

        {{-- ── LEFT: PRODUCT GRID ──────────────────────────────────────── --}}
        <div class="col-lg-8">
            {{-- Search --}}
            <div class="mb-2 d-flex gap-2">
                <div class="input-group input-group-sm shadow-sm flex-grow-1">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="productSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar producto por nombre, código o categoría..."
                           autocomplete="off">
                    <button type="button" class="btn btn-light border" id="clearSearch" style="display:none;" onclick="clearSearch()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-warning btn-sm text-dark fw-semibold flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#quickItemModal" title="Vender un producto que no está en la lista">
                    <i class="bi bi-lightning-charge-fill me-1"></i>Venta rápida
                </button>
            </div>
            {{-- Category filter bar (multiselección) --}}
            <div class="d-flex align-items-center gap-2 mb-2 flex-nowrap">
                <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-tags me-1"></i>Categoría</span>
                <div class="cat-filter-bar flex-grow-1" id="catBar" style="min-width:0;">
                    <button type="button" class="cat-pill active" data-cat="">Todas</button>
                    @foreach($categories as $cat)
                    <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                    @endforeach
                </div>
            </div>
            {{-- Category filter bar por CÓDIGO (mismo filtro, muestra el code) --}}
            <div class="d-flex align-items-center gap-2 mb-2 flex-nowrap">
                <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-upc me-1"></i>Código</span>
                <div class="cat-filter-bar flex-grow-1" id="catCodeBar" style="min-width:0;">
                    <button type="button" class="cat-pill active" data-cat="">Todas</button>
                    @foreach($categories as $cat)
                    <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">{{ $cat->code ?: '—' }}</button>
                    @endforeach
                </div>
            </div>
            {{-- Model filter bar (multiselección) --}}
            @if($motoModels->count())
            <div class="d-flex align-items-center gap-2 mb-3 flex-nowrap">
                <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-bicycle me-1"></i>Modelo</span>
                <div class="cat-filter-bar flex-grow-1" id="modelBar" style="min-width:0;">
                    <button type="button" class="cat-pill model-pill active" data-model="">Todos</button>
                    @foreach($motoModels as $m)
                    <button type="button" class="cat-pill model-pill" data-model="{{ $m->id }}">{{ $m->display_name }}</button>
                    @endforeach
                </div>
            </div>
            @endif
            {{-- Grid --}}
            <div id="productGrid" class="row g-2 align-content-start pb-2">
                {{-- Filled by JS --}}
            </div>
            <div id="noProductsMsg" class="text-center py-5 text-muted" style="display:none;">
                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                Sin resultados para esa búsqueda.
            </div>
        </div>

        {{-- ── RIGHT: CART ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <form action="{{ route('pos.store') }}" method="POST" id="posForm"
                  style="position:sticky;top:1rem;">
                @csrf
                <input type="hidden" name="sale_type" id="saleTypeInput" value="cash">
                <input type="hidden" name="cash_register_session_id" value="{{ $session->id }}">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-semibold"><i class="bi bi-cart3 me-2 text-muted"></i>Carrito</h6>
                            <span class="badge bg-light border text-muted" id="cartCount">0 items</span>
                        </div>
                    </div>

                    {{-- Cart rows --}}
                    <div class="overflow-auto" style="max-height:42vh;">
                        <div id="cartEmpty" class="text-center py-5 text-muted">
                            <i class="bi bi-cart d-block fs-2 mb-2 opacity-25"></i>
                            <small>Haz clic en un producto para agregarlo</small>
                        </div>
                        <table class="table table-sm align-middle mb-0" id="cartTable" style="display:none;font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 py-2" style="font-size:.7rem;" class="text-muted text-uppercase">Producto</th>
                                    <th class="py-2 text-center" style="width:80px;font-size:.7rem;">Cant.</th>
                                    <th class="py-2 text-end" style="width:70px;font-size:.7rem;">P.Unit</th>
                                    <th class="py-2 text-end" style="width:75px;font-size:.7rem;">Sub.</th>
                                    <th class="py-2 pe-2" style="width:32px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody"></tbody>
                        </table>
                    </div>

                    {{-- Footer: client + discount + total + actions --}}
                    <div class="border-top">
                        {{-- Client selector (botón → modal de búsqueda) --}}
                        <div class="p-3 border-bottom">
                            <label class="form-label small fw-semibold mb-1">Cliente</label>
                            <input type="hidden" name="client_id" id="client_id" value="">
                            <button type="button" class="btn btn-light border w-100 d-flex align-items-center justify-content-between"
                                    data-bs-toggle="modal" data-bs-target="#clientModal">
                                <span class="d-flex align-items-center gap-2 text-truncate">
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span id="selectedClientLabel" class="text-truncate">Cliente general</span>
                                </span>
                                <i class="bi bi-search text-muted small"></i>
                            </button>
                        </div>
                        {{-- Discount --}}
                        <div class="px-3 pt-2 pb-1">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted">Subtotal</span>
                                <span id="cartSubtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <label class="text-muted small mb-0" for="discountPct">Descuento <span class="text-muted" style="font-size:.72rem;">(% s/ ganancia)</span></label>
                                <div class="input-group input-group-sm" style="width:92px;">
                                    <input type="number" id="discountPct" name="discount_pct"
                                           class="form-control text-end" min="0" max="100" step="1" inputmode="numeric"
                                           value="0" placeholder="0"
                                           oninput="recalcCart()">
                                    <span class="input-group-text bg-light px-2">%</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small mb-1" id="discountAmountRow" style="display:none;">
                                <span class="text-muted">Descuento aplicado</span>
                                <span class="text-danger" id="cartDiscount">-$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1">
                                <span>TOTAL</span>
                                <span id="cartTotal" class="fs-5 text-dark">$0.00</span>
                            </div>
                        </div>
                        {{-- Action buttons --}}
                        <div class="p-3 d-flex flex-column gap-2">
                            <button type="button" class="btn btn-primary w-100 py-2"
                                    id="btnCash" onclick="submitCash()">
                                <i class="bi bi-cash me-2"></i>Cobrar (Contado)
                            </button>
                            <button type="button" class="btn btn-light border w-100 py-2"
                                    id="btnCredit" onclick="openCreditModal()">
                                <i class="bi bi-calendar2-check me-2"></i>A Crédito
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Hidden cart inputs built by JS --}}
                <div id="cartInputs"></div>

            </form>
        </div>

    </div>
</div>

{{-- ─── CLIENT SEARCH MODAL ───────────────────────────────────────────── --}}
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="clientModalLabel">
                    <i class="bi bi-person-circle me-2 text-muted"></i>Seleccionar cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="clientSearch" class="form-control border-start-0 ps-0"
                           placeholder="Buscar por nombre o documento..." autocomplete="off">
                </div>
                <div id="clientList" class="list-group" style="max-height:42vh;overflow-y:auto;">
                    {{-- Filled by JS --}}
                </div>
                <div id="noClientsMsg" class="text-center py-4 text-muted" style="display:none;">
                    <i class="bi bi-person-x d-block fs-2 mb-2 opacity-25"></i>
                    Sin coincidencias.
                </div>

                {{-- Alta rápida --}}
                <button type="button" class="btn btn-light border w-100 mt-3" onclick="toggleNewClient()">
                    <i class="bi bi-person-plus me-1"></i>Registrar nuevo cliente
                </button>
                <div id="newClientForm" class="mt-3 p-3 rounded-3 border bg-light" style="display:none;">
                    <div id="ncAlert" class="alert alert-danger d-none py-2 small"></div>
                    <div class="row g-2">
                        <div class="col-12"><input type="text" id="nc_full_name" class="form-control form-control-sm" placeholder="Nombre completo *"></div>
                        <div class="col-6"><input type="text" id="nc_id_number" class="form-control form-control-sm" placeholder="Documento"></div>
                        <div class="col-6"><input type="text" id="nc_phone" class="form-control form-control-sm" placeholder="Teléfono"></div>
                        <div class="col-12"><input type="email" id="nc_email" class="form-control form-control-sm" placeholder="Email"></div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="nc_save" onclick="saveNewClientPos()">
                        <i class="bi bi-check-lg me-1"></i>Guardar y seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── CREDIT MODAL ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="creditModal" tabindex="-1" aria-labelledby="creditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header credit-modal-header">
                <h5 class="modal-title fw-semibold" id="creditModalLabel">
                    <i class="bi bi-calendar2-check me-2"></i>Venta a crédito — Cronograma de cuotas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">N° cuotas</label>
                        <input type="number" id="cm_cuotas" class="form-control" min="1" value="3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Cada (días)</label>
                        <input type="number" id="cm_dias" class="form-control" min="1" value="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Primera fecha</label>
                        <input type="date" id="cm_fecha" class="form-control"
                               value="{{ now()->addDays(30)->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Pago inicial</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light px-2">$</span>
                            <input type="number" id="cm_downpayment" class="form-control" min="0" step="0.01" value="0" placeholder="0.00"
                                   oninput="updateBalanceIndicator()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">% adicional (recargo)</label>
                        <div class="input-group">
                            <input type="number" id="cm_pct" class="form-control" min="0" step="0.01" value="0" placeholder="0.00"
                                   oninput="updateBalanceIndicator()">
                            <span class="input-group-text bg-light px-2">%</span>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100 fw-semibold" onclick="generateInstallments()">
                            <i class="bi bi-lightning-charge-fill me-1"></i>Generar cuotas
                        </button>
                    </div>
                </div>

                {{-- Installments table --}}
                <div id="installmentsWrap" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" style="font-size:.83rem;" id="installmentsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2" style="width:40px;">#</th>
                                    <th class="py-2">Vencimiento</th>
                                    <th class="py-2">Monto</th>
                                    <th class="py-2" style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="installmentsBody"></tbody>
                        </table>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-add-cuota" onclick="addInstallmentRow()">
                            <i class="bi bi-plus-circle-fill me-1"></i>Agregar cuota
                        </button>
                        <div id="cm_balance_indicator" class="small fw-semibold"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitCredit()">
                    <i class="bi bi-check-lg me-1"></i>Confirmar venta a crédito
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── PRODUCT DETAIL MODAL ──────────────────────────────────────── --}}
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-image me-2 text-muted"></i>Imágenes del producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="pmGallery"></div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="pmFichaBtn" target="_blank" class="btn btn-primary"
                   data-base-url="{{ route('products.show', '__ID__') }}">
                    <i class="bi bi-eye me-1"></i>Ver ficha completa
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ─── VENTA RÁPIDA (producto no listado) ────────────────────────── --}}
<div class="modal fade" id="quickItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">
                    <i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Venta rápida
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Agrega un producto que no está en la lista o no tienes en stock. Escribe el nombre y el precio.
                </p>
                <div id="qiAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="qi_name">Nombre del producto <span class="text-danger">*</span></label>
                    <input type="text" id="qi_name" class="form-control" placeholder="Ej. Empaque de motor genérico" autocomplete="off">
                </div>
                <div class="row g-3">
                    <div class="col-7">
                        <label class="form-label small fw-semibold" for="qi_price">Precio unitario <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light px-2">$</span>
                            <input type="number" id="qi_price" class="form-control" min="0" step="0.01" value="" placeholder="0.00" inputmode="decimal">
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-semibold" for="qi_qty">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" id="qi_qty" class="form-control text-center" min="1" step="1" value="1" inputmode="numeric">
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 py-2" style="font-size:.78rem;">
                    <i class="bi bi-info-circle me-1 text-muted"></i>
                    Si el nombre coincide con un producto existente, se descontará su stock automáticamente.
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning text-dark fw-semibold px-4" onclick="confirmQuickItem()">
                    <i class="bi bi-cart-plus me-1"></i>Agregar al carrito
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── STOCK POR ALMACÉN (revisión) ──────────────────────────────── --}}
<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-boxes me-2 text-muted"></i>Stock por almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="stockSearch" class="form-control border-start-0 ps-0" placeholder="Buscar producto por nombre o código..." autocomplete="off">
                </div>
                {{-- Filtro categoría (multiselección) --}}
                <div class="d-flex align-items-center gap-2 mb-2 flex-nowrap">
                    <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-tags me-1"></i>Categoría</span>
                    <div class="cat-filter-bar flex-grow-1" id="stockCatBar" style="min-width:0;">
                        <button type="button" class="cat-pill active" data-cat="">Todas</button>
                        @foreach($categories as $cat)
                        <button type="button" class="cat-pill" data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                        @endforeach
                    </div>
                </div>
                {{-- Filtro modelo (multiselección) --}}
                @if($motoModels->count())
                <div class="d-flex align-items-center gap-2 mb-3 flex-nowrap">
                    <span class="text-muted flex-shrink-0" style="font-size:.7rem;"><i class="bi bi-bicycle me-1"></i>Modelo</span>
                    <div class="cat-filter-bar flex-grow-1" id="stockModelBar" style="min-width:0;">
                        <button type="button" class="cat-pill model-pill active" data-model="">Todos</button>
                        @foreach($motoModels as $m)
                        <button type="button" class="cat-pill model-pill" data-model="{{ $m->id }}">{{ $m->display_name }}</button>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:.8rem;">
                        <thead class="table-light"><tr id="stockHead"></tr></thead>
                        <tbody id="stockBody"></tbody>
                    </table>
                </div>
                <div class="text-muted mt-2" style="font-size:.72rem;"><i class="bi bi-info-circle me-1"></i>Solo lectura — stock calculado por almacén.</div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@if(!empty($loyaltyEnabled) && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty.redeem', auth()->user()->getCurrentCompany())))
    @include('loyalty.partials.pos-redeem-modal')
@endif

@push('styles')
<style>
.product-card {
    cursor: pointer;
    border: 1.5px solid #e8e8e8;
    border-radius: 9px;
    padding: 7px;
    background: #fff;
    transition: all .15s ease;
    user-select: none;
    position: relative;
    height: 100%;
}
.product-card:hover { border-color: var(--brand-black,#22242e); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.product-card.disabled-card { opacity: .45; cursor: not-allowed; }
.product-card.in-cart { border-color: #22242e; background: #fafafa; }
.product-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 6px; background:#f5f5f5; }
.product-thumb-placeholder {
    width: 100%; aspect-ratio: 1; border-radius: 6px; background:#f0f0f0;
    display: flex; align-items: center; justify-content: center; color:#ccc; font-size:1.6rem;
}
.cart-qty-input { width:56px; text-align:center; }

/* Category filter bar */
.cat-filter-bar {
    display: flex;
    gap: .4rem;
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: .25rem;
    scrollbar-width: thin;
}
.cat-filter-bar::-webkit-scrollbar { height: 4px; }
.cat-filter-bar::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
.cat-pill {
    display: inline-flex;
    align-items: center;
    padding: .2rem .7rem;
    border-radius: 50rem;
    font-size: .73rem;
    font-weight: 500;
    border: 1.5px solid #dee2e6;
    background: #fff;
    color: #495057;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
    flex-shrink: 0;
}
.cat-pill:hover { border-color: #22242e; color: #22242e; }
.cat-pill.active { background: #22242e; border-color: #22242e; color: #fff; }

/* Product info btn */
.product-info-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: rgba(255,255,255,.9);
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    color: #666;
    cursor: pointer;
    z-index: 2;
    transition: all .15s;
    padding: 0;
    line-height: 1;
}
.product-info-btn:hover { background: #fff; border-color: #22242e; color: #22242e; }

/* Product detail modal gallery */
#productModal .modal-content { max-height: 90vh; }
#productModal .modal-body { overflow-y: auto; }
.pm-gallery-main {
    width: 100%;
    height: auto;
    max-height: 64vh;
    object-fit: contain;
    border-radius: 10px;
    background: #f5f5f5;
}
.pm-gallery-placeholder {
    width: 100%;
    height: 40vh;
    border-radius: 10px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #ccc;
}
.pm-thumb {
    width: 56px;
    height: 56px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border-color .15s;
}
.pm-thumb.active, .pm-thumb:hover { border-color: #22242e; }

/* Flechas de navegación de la galería */
.pm-stage { position: relative; display: flex; align-items: center; justify-content: center; }
.pm-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 40px; height: 40px; border-radius: 50%; border: 0;
    background: rgba(0,0,0,.45); color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; cursor: pointer; transition: background .15s; z-index: 2;
}
.pm-nav:hover { background: rgba(0,0,0,.72); }
.pm-nav-prev { left: 8px; }
.pm-nav-next { right: 8px; }
.pm-counter {
    position: absolute; bottom: 8px; right: 10px;
    background: rgba(0,0,0,.55); color: #fff; font-size: .72rem; padding: 2px 9px; border-radius: 999px;
}

/* Credit modal — encabezado con color de marca */
.credit-modal-header {
    background: linear-gradient(135deg, #e63946 0%, #a30400 100%);
    border-bottom: 0;
}
.credit-modal-header .modal-title,
.credit-modal-header .modal-title i { color: #fff; }

/* Botón "Agregar cuota" */
.btn-add-cuota {
    border: 1.5px dashed #16a34a;
    color: #16a34a;
    background: #f0fdf4;
    font-weight: 600;
    border-radius: 50rem;
    transition: all .15s ease;
}
.btn-add-cuota:hover {
    background: #16a34a;
    border-color: #16a34a;
    color: #fff;
    box-shadow: 0 3px 10px rgba(22,163,74,.25);
}

@media print {
    .app-sidebar, .app-topbar, #posForm .card-header, .pos-wrapper > .d-flex:first-child { display: none !important; }
}
</style>
@endpush

@push('scripts')
<script>
const PRODUCTS = @json($productsData);
const CLIENTS  = @json($clientsJson);
const WAREHOUSES = @json($warehousesJson);
const WH_STOCK   = @json($productWhStock);   // { product_id: [ {wid, qty} ] }
let cart = {};
let installmentCount = 0;
let directItemSeq = 0;   // secuencia para ítems de "venta rápida" (sin product_id)

// Escapes para insertar texto del usuario en HTML / atributos
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }
const selectedCats   = new Set();
const selectedModels = new Set();
const stockCats      = new Set();
const stockModels    = new Set();

// ── Notificación emergente (toast) ──────────────────────────────────
function showToast(msg, type) {
    type = type || 'info';
    let cont = document.getElementById('posToastContainer');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'posToastContainer';
        cont.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
        document.body.appendChild(cont);
    }
    const colors = { warning: '#f59e0b', danger: '#e11d48', success: '#16a34a', info: '#22242e' };
    const icons  = { warning: 'bi-exclamation-triangle', danger: 'bi-x-octagon', success: 'bi-check-circle', info: 'bi-info-circle' };
    const el = document.createElement('div');
    el.style.cssText = 'background:#fff;border-left:4px solid ' + (colors[type] || colors.info) + ';box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:340px;display:flex;align-items:flex-start;gap:.5rem;';
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + '" style="color:' + (colors[type] || colors.info) + ';margin-top:1px;"></i><span>' + msg + '</span>';
    cont.appendChild(el);
    setTimeout(function () { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3800);
}

// ── Abrir modal de crédito validando cliente real ───────────────────
function openCreditModal() {
    if (Object.keys(cart).length === 0) { showToast('Agrega al menos un producto al carrito.', 'warning'); return; }
    if (!document.getElementById('client_id').value) {
        showToast('Para vender a crédito debes seleccionar un cliente registrado (no «Cliente general»).', 'warning');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('clientModal')).show();
        return;
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('creditModal')).show();
}

// ── Stock por almacén (modal de revisión) ───────────────────────────
function renderStockTable(filter) {
    const head = document.getElementById('stockHead');
    const body = document.getElementById('stockBody');
    const q = (filter || '').toLowerCase().trim();

    head.innerHTML = '<th class="ps-2">Producto</th>' + WAREHOUSES.map(w => `<th class="text-end">${w.name}</th>`).join('');

    const rows = PRODUCTS.filter(p => {
        if (!WH_STOCK[p.id]) return false; // sin stock en ningún almacén
        if (q && !(p.name.toLowerCase().includes(q) || (p.code && p.code.toLowerCase().includes(q)) || p.sku.toLowerCase().includes(q))) return false;
        if (stockCats.size && !stockCats.has(String(p.category_id))) return false;
        if (stockModels.size && !(p.model_ids || []).some(id => stockModels.has(String(id)))) return false;
        return true;
    });

    if (!rows.length) {
        body.innerHTML = '<tr><td colspan="' + (WAREHOUSES.length + 1) + '" class="text-center text-muted py-4">Sin stock en almacenes' + (q ? ' para esa búsqueda' : '') + '.</td></tr>';
        return;
    }

    body.innerHTML = rows.map(p => {
        const map = {};
        (WH_STOCK[p.id] || []).forEach(s => { map[s.wid] = s.qty; });
        const cells = WAREHOUSES.map(w => {
            const qv = map[w.id] || 0;
            return `<td class="text-end ${qv > 0 ? 'fw-semibold' : 'text-muted'}">${qv > 0 ? qv : '—'}</td>`;
        }).join('');
        return `<tr><td class="ps-2"><div class="fw-semibold lh-sm">${p.name}</div><div class="text-muted" style="font-size:.7rem;">${p.code ? p.code : 'Sin código'}</div></td>${cells}</tr>`;
    }).join('');
}

// ── CLIENT SEARCH MODAL ─────────────────────────────────────────────
function renderClientList(filter) {
    const listEl = document.getElementById('clientList');
    const noMsg  = document.getElementById('noClientsMsg');
    const q = (filter || '').toLowerCase().trim();
    const matches = q
        ? CLIENTS.filter(c =>
            c.name.toLowerCase().includes(q) ||
            (c.id_number && c.id_number.toLowerCase().includes(q)))
        : CLIENTS;

    let html = `
        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                onclick="selectClient('', 'Cliente general')">
            <i class="bi bi-people text-muted"></i>
            <span class="fw-semibold">Cliente general</span>
        </button>`;

    if (matches.length === 0 && q) {
        noMsg.style.display = '';
    } else {
        noMsg.style.display = 'none';
        html += matches.map(c => `
            <button type="button" class="list-group-item list-group-item-action"
                    onclick="selectClient(${c.id}, '${(c.name + (c.id_number ? ' (' + c.id_number + ')' : '')).replace(/'/g, "\\'")}')">
                <div class="fw-semibold">${c.name}</div>
                ${c.id_number ? `<small class="text-muted"><i class="bi bi-card-text me-1"></i>${c.id_number}</small>` : ''}
            </button>`).join('');
    }
    listEl.innerHTML = html;
}

function selectClient(id, label) {
    document.getElementById('client_id').value = id;
    document.getElementById('selectedClientLabel').textContent = label;
    bootstrap.Modal.getInstance(document.getElementById('clientModal'))?.hide();
}

function toggleNewClient() {
    const f = document.getElementById('newClientForm');
    f.style.display = f.style.display === 'none' ? '' : 'none';
    if (f.style.display !== 'none') document.getElementById('nc_full_name').focus();
}

function saveNewClientPos() {
    const btn = document.getElementById('nc_save');
    const alertBox = document.getElementById('ncAlert');
    const name = document.getElementById('nc_full_name').value.trim();
    alertBox.classList.add('d-none');
    if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

    fetch('{{ route('clients.quick-store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            full_name: name,
            id_number: document.getElementById('nc_id_number').value.trim(),
            phone:     document.getElementById('nc_phone').value.trim(),
            email:     document.getElementById('nc_email').value.trim(),
        }),
    })
    .then(r => r.json().then(d => ({ ok: r.ok, d })))
    .then(({ ok, d }) => {
        if (!ok || !d.ok) { alertBox.textContent = d.message || 'No se pudo guardar.'; alertBox.classList.remove('d-none'); return; }
        CLIENTS.push({ id: d.client.id, name: d.client.full_name, id_number: d.client.id_number || '' });
        ['nc_full_name','nc_id_number','nc_phone','nc_email'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('newClientForm').style.display = 'none';
        selectClient(d.client.id, d.client.full_name + (d.client.id_number ? ' (' + d.client.id_number + ')' : ''));
    })
    .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Guardar y seleccionar'; });
}

// ── PRODUCT DETAIL MODAL ────────────────────────────────────────────
function openProductModal(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p) return;
    const modal = document.getElementById('productModal');

    // Gallery
    pmPhotos = p.photos || [];
    pmIndex  = 0;
    let galleryHtml = '';
    if (pmPhotos.length > 0) {
        const arrows = pmPhotos.length > 1 ? `
            <button type="button" class="pm-nav pm-nav-prev" onclick="pmNav(-1)" title="Anterior"><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="pm-nav pm-nav-next" onclick="pmNav(1)" title="Siguiente"><i class="bi bi-chevron-right"></i></button>
            <span class="pm-counter"><span id="pmCounter">1</span>/${pmPhotos.length}</span>` : '';
        galleryHtml = `
            <div class="pm-stage">
                <img id="pmMainImg" src="${pmPhotos[0]}" class="pm-gallery-main" alt="">
                ${arrows}
            </div>
            ${pmPhotos.length > 1 ? `<div class="d-flex gap-2 flex-wrap mt-2 justify-content-center">
                ${pmPhotos.map((ph, i) => `<img src="${ph}" class="pm-thumb ${i===0?'active':''}" onclick="pmGo(${i})" alt="">`).join('')}
            </div>` : ''}`;
    } else {
        galleryHtml = `<div class="pm-gallery-placeholder"><i class="bi bi-box-seam"></i></div>`;
    }

    modal.querySelector('#pmGallery').innerHTML = galleryHtml;

    const fichaBtn = modal.querySelector('#pmFichaBtn');
    fichaBtn.href = fichaBtn.dataset.baseUrl.replace('__ID__', p.id);

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

let pmPhotos = [];
let pmIndex  = 0;
function pmGo(i) {
    if (!pmPhotos.length) return;
    pmIndex = (i + pmPhotos.length) % pmPhotos.length;
    const img = document.getElementById('pmMainImg');
    if (img) img.src = pmPhotos[pmIndex];
    document.querySelectorAll('#productModal .pm-thumb').forEach((t, idx) => t.classList.toggle('active', idx === pmIndex));
    const c = document.getElementById('pmCounter');
    if (c) c.textContent = pmIndex + 1;
}
function pmNav(dir) { pmGo(pmIndex + dir); }

// ── RENDER PRODUCT GRID ─────────────────────────────────────────────
function renderGrid(filter) {
    const grid = document.getElementById('productGrid');
    const noMsg = document.getElementById('noProductsMsg');
    const q = (filter || '').toLowerCase().trim();

    let list = q
        ? PRODUCTS.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.code && p.code.toLowerCase().includes(q)) ||
            p.sku.toLowerCase().includes(q) ||
            p.category.toLowerCase().includes(q))
        : PRODUCTS.slice();

    if (selectedCats.size) {
        list = list.filter(p => selectedCats.has(String(p.category_id)));
    }
    if (selectedModels.size) {
        list = list.filter(p => (p.model_ids || []).some(id => selectedModels.has(String(id))));
    }

    if (list.length === 0) {
        grid.innerHTML = '';
        noMsg.style.display = '';
        return;
    }
    noMsg.style.display = 'none';

    grid.innerHTML = list.map(p => {
        const disabled = p.stock <= 0 ? 'disabled-card' : '';
        const inCart   = cart[p.id] ? 'in-cart' : '';
        const stockBadge = p.stock > 0
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;">${p.stock} en stock</span>`
            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem;">Sin stock</span>`;
        const img = p.photo
            ? `<img src="${p.photo}" class="product-thumb mb-2" alt="${p.name}">`
            : `<div class="product-thumb-placeholder mb-2"><i class="bi bi-box-seam"></i></div>`;
        const meta = [p.brand, p.compatible_models].filter(Boolean).join(' · ') || '—';
        const metaEsc = meta.replace(/"/g, '&quot;');
        const codeHtml = p.code
            ? `<span class="badge bg-light text-dark border" style="font-size:.6rem;"><i class="bi bi-upc me-1"></i>${p.code}</span>`
            : `<span class="text-muted fst-italic" style="font-size:.62rem;">Sin código</span>`;
        return `
        <div class="col-6 col-md-4 col-xl-3">
            <div class="product-card ${disabled} ${inCart}" onclick="addToCart(${p.id})" data-pid="${p.id}">
                <button type="button" class="product-info-btn" onclick="event.stopPropagation();openProductModal(${p.id})" title="Ver detalle">
                    <i class="bi bi-eye"></i>
                </button>
                ${img}
                <div class="fw-semibold lh-sm mb-1" style="font-size:.78rem;">${p.name}</div>
                <div class="mb-1">${codeHtml}</div>
                <div class="text-muted mb-1 text-truncate" style="font-size:.66rem;" title="${metaEsc}">${meta}</div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <span class="fw-bold" style="font-size:.85rem;">$${p.price.toFixed(2)}</span>
                    ${stockBadge}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── VENTA RÁPIDA (ítem libre, sin producto registrado) ──────────────
function confirmQuickItem() {
    const name  = document.getElementById('qi_name').value.trim();
    const price = parseFloat(document.getElementById('qi_price').value);
    const qty   = Math.max(1, Math.floor(parseInt(document.getElementById('qi_qty').value, 10) || 1));
    const alertBox = document.getElementById('qiAlert');
    alertBox.classList.add('d-none');

    if (!name)                    { qiError('Escribe el nombre del producto.'); return; }
    if (isNaN(price) || price < 0) { qiError('Ingresa un precio válido.'); return; }

    addDirectItem(name, price, qty);

    document.getElementById('qi_name').value  = '';
    document.getElementById('qi_price').value = '';
    document.getElementById('qi_qty').value   = '1';
    bootstrap.Modal.getInstance(document.getElementById('quickItemModal'))?.hide();
    showToast('«' + name + '» agregado al carrito.', 'success');
}

function qiError(msg) {
    const a = document.getElementById('qiAlert');
    a.textContent = msg;
    a.classList.remove('d-none');
}

function addDirectItem(name, price, qty) {
    const key = 'd_' + (++directItemSeq);
    cart[key] = {
        product: { id: null, name: name, price: price, cost: 0, code: null, stock: Infinity, direct: true },
        qty: qty,
    };
    renderCart();
}

// ── CART LOGIC ──────────────────────────────────────────────────────
function addToCart(pid) {
    const p = PRODUCTS.find(x => x.id === pid);
    if (!p || p.stock <= 0) return;
    if (cart[pid]) {
        if (cart[pid].qty < p.stock) cart[pid].qty++;
    } else {
        cart[pid] = { product: p, qty: 1 };
    }
    renderCart();
    highlightProductCard(pid);
}

function removeFromCart(pid) {
    delete cart[pid];
    renderCart();
    highlightProductCard(pid);
}

function highlightProductCard(pid) {
    document.querySelectorAll(`[data-pid="${pid}"]`).forEach(el => {
        el.classList.toggle('in-cart', !!cart[pid]);
    });
}

function renderCart() {
    const body    = document.getElementById('cartBody');
    const empty   = document.getElementById('cartEmpty');
    const table   = document.getElementById('cartTable');
    const count   = document.getElementById('cartCount');
    const inputs  = document.getElementById('cartInputs');

    const entries = Object.entries(cart);
    count.textContent = entries.length + (entries.length === 1 ? ' item' : ' items');

    if (entries.length === 0) {
        empty.style.display = '';
        table.style.display = 'none';
        inputs.innerHTML = '';
        recalcCart();
        return;
    }
    empty.style.display = 'none';
    table.style.display = '';

    body.innerHTML = entries.map(([key, it]) => {
        const sub      = (it.qty * it.product.price).toFixed(2);
        const isDirect = it.product.direct;
        const subLine  = isDirect
            ? `<span class="badge bg-warning text-dark" style="font-size:.6rem;"><i class="bi bi-lightning-charge-fill me-1"></i>Venta rápida</span>`
            : `<div class="text-muted" style="font-size:.68rem;">${it.product.code ? escHtml(it.product.code) : 'Sin código'}</div>`;
        const maxAttr  = isDirect ? '' : `max="${it.product.stock}"`;
        return `
        <tr>
            <td class="ps-3 py-2">
                <div class="fw-semibold lh-sm" style="font-size:.78rem;">${escHtml(it.product.name)}</div>
                ${subLine}
            </td>
            <td class="text-center py-2">
                <input type="number" class="form-control form-control-sm cart-qty-input"
                       min="1" step="1" inputmode="numeric" ${maxAttr}
                       value="${it.qty}"
                       oninput="updateQty('${key}', this.value)">
            </td>
            <td class="text-end py-2">$${it.product.price.toFixed(2)}</td>
            <td class="text-end py-2 fw-semibold">$${sub}</td>
            <td class="pe-2 py-2">
                <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                        onclick="removeFromCart('${key}')" title="Quitar">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        </tr>`;
    }).join('');

    inputs.innerHTML = entries.map(([key, it], i) => {
        if (it.product.direct) {
            return `
            <input type="hidden" name="items[${i}][name]"       value="${escAttr(it.product.name)}">
            <input type="hidden" name="items[${i}][direct]"     value="1">
            <input type="hidden" name="items[${i}][quantity]"   value="${it.qty}">
            <input type="hidden" name="items[${i}][unit_price]" value="${it.product.price}">`;
        }
        return `
            <input type="hidden" name="items[${i}][product_id]" value="${it.product.id}">
            <input type="hidden" name="items[${i}][quantity]"   value="${it.qty}">
            <input type="hidden" name="items[${i}][unit_price]" value="${it.product.price}">`;
    }).join('');

    recalcCart();
}

function updateQty(pid, val) {
    if (!cart[pid]) return;
    const qty = Math.max(1, Math.min(Math.floor(parseInt(val, 10) || 1), cart[pid].product.stock));
    cart[pid].qty = qty;
    renderCart();
}

function recalcCart() {
    const items  = Object.values(cart);
    const sub    = items.reduce((s, it) => s + it.qty * it.product.price, 0);
    const profit = items.reduce((s, it) => s + it.qty * (it.product.price - (it.product.cost || 0)), 0);
    let pct = parseInt(document.getElementById('discountPct').value, 10) || 0;
    pct = Math.min(100, Math.max(0, pct));
    // El descuento solo afecta a la ganancia (precio − costo)
    const disc  = Math.max(0, Math.round(Math.max(0, profit) * pct / 100 * 100) / 100);
    const total = Math.max(0, sub - disc);

    document.getElementById('cartSubtotal').textContent = '$' + sub.toFixed(2);
    const dRow = document.getElementById('discountAmountRow');
    if (disc > 0) {
        dRow.style.display = '';
        document.getElementById('cartDiscount').textContent = '-$' + disc.toFixed(2);
    } else {
        dRow.style.display = 'none';
    }
    document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);
    return { sub, disc, total, pct, profit };
}

// ── SUBMIT ──────────────────────────────────────────────────────────
function lockPosButtons(active) {
    ['btnCash', 'btnCredit'].forEach(function (id) {
        const b = document.getElementById(id);
        if (b) b.disabled = true;
    });
    if (active) {
        active.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Procesando…';
    }
}

function submitCash() {
    if (Object.keys(cart).length === 0) { alert('Agrega al menos un producto.'); return; }
    document.getElementById('saleTypeInput').value = 'cash';
    lockPosButtons(document.getElementById('btnCash'));
    document.getElementById('posForm').requestSubmit();
}

function submitCredit() {
    if (Object.keys(cart).length === 0) { showToast('Agrega al menos un producto.', 'warning'); return; }
    if (!document.getElementById('client_id').value) {
        showToast('Para vender a crédito debes seleccionar un cliente registrado.', 'warning');
        return;
    }
    const rows = document.querySelectorAll('#installmentsBody tr');
    if (rows.length === 0) { alert('Genera al menos una cuota antes de confirmar.'); return; }
    document.getElementById('saleTypeInput').value = 'credit';
    const posForm = document.getElementById('posForm');
    document.querySelectorAll('.inst-hidden').forEach(el => el.remove());

    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const { total } = recalcCart();
    const recargo = total * pct / 100;

    if (dp > 0) {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'down_payment'; inp.value = dp.toFixed(2); inp.className = 'inst-hidden';
        posForm.appendChild(inp);
    }

    // Append interest (recargo)
    const intInp = document.createElement('input');
    intInp.type = 'hidden'; intInp.name = 'interest'; intInp.value = recargo.toFixed(2); intInp.className = 'inst-hidden';
    posForm.appendChild(intInp);

    rows.forEach((row, i) => {
        const dd = row.querySelector('.inst-date').value;
        const am = row.querySelector('.inst-amount').value;
        [['installments['+i+'][due_date]', dd], ['installments['+i+'][amount]', am]].forEach(([n,v]) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = n; inp.value = v; inp.className = 'inst-hidden';
            posForm.appendChild(inp);
        });
    });
    bootstrap.Modal.getInstance(document.getElementById('creditModal'))?.hide();
    lockPosButtons(document.getElementById('btnCash'));
    posForm.requestSubmit();
}

// ── INSTALLMENTS GENERATOR ──────────────────────────────────────────
function generateInstallments() {
    const n    = parseInt(document.getElementById('cm_cuotas').value) || 3;
    const days = parseInt(document.getElementById('cm_dias').value) || 30;
    const fd   = document.getElementById('cm_fecha').value;
    if (!fd) { alert('Ingresa la primera fecha de vencimiento.'); return; }

    const { total } = recalcCart();
    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const recargo = total * pct / 100;
    const rem = Math.max(0, (total + recargo) - dp);

    const base = Math.floor((rem / n) * 100) / 100;
    const last = (rem - base * (n - 1)).toFixed(2);

    const body = document.getElementById('installmentsBody');
    body.innerHTML = '';
    installmentCount = 0;

    let d = new Date(fd + 'T00:00:00');
    for (let i = 0; i < n; i++) {
        const dateStr = d.toISOString().slice(0,10);
        const amt = (i === n - 1) ? last : base.toFixed(2);
        addInstallmentRow(dateStr, amt);
        d.setDate(d.getDate() + days);
    }
    document.getElementById('installmentsWrap').style.display = '';
    updateBalanceIndicator();
}

function addInstallmentRow(dateVal, amtVal) {
    const i = installmentCount++;
    const tr = document.createElement('tr');
    tr.dataset.row = i;
    tr.innerHTML = `
        <td class="py-1">${i+1}</td>
        <td class="py-1">
            <input type="date" class="form-control form-control-sm inst-date"
                   value="${dateVal || ''}" oninput="updateBalanceIndicator()">
        </td>
        <td class="py-1">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light px-2">$</span>
                <input type="number" class="form-control inst-amount text-end"
                       step="0.01" min="0" value="${amtVal || ''}"
                       oninput="updateBalanceIndicator()">
            </div>
        </td>
        <td class="py-1">
            <button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1"
                    onclick="this.closest('tr').remove(); renumberInstallments(); updateBalanceIndicator();">
                <i class="bi bi-x"></i>
            </button>
        </td>`;
    document.getElementById('installmentsBody').appendChild(tr);
    updateBalanceIndicator();
}

function renumberInstallments() {
    document.querySelectorAll('#installmentsBody tr').forEach((tr, i) => {
        tr.cells[0].textContent = i + 1;
    });
}

function updateBalanceIndicator() {
    const { total } = recalcCart();
    const dp  = parseFloat(document.getElementById('cm_downpayment').value) || 0;
    const pct = parseFloat(document.getElementById('cm_pct').value) || 0;
    const recargo = total * pct / 100;
    const totalConRecargo = total + recargo;
    const rem = Math.max(0, totalConRecargo - dp);

    const instSum = Array.from(document.querySelectorAll('.inst-amount'))
        .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
    const diff = (instSum - rem).toFixed(2);
    const ind  = document.getElementById('cm_balance_indicator');
    const ok   = Math.abs(parseFloat(diff)) < 0.02;

    let recargoLine = pct > 0
        ? `<div class="text-muted small mb-1">
               Recargo ${pct}%: <strong>$${recargo.toFixed(2)}</strong>
               &nbsp;&middot;&nbsp;
               Total con recargo: <strong>$${totalConRecargo.toFixed(2)}</strong>
           </div>`
        : '';

    ind.innerHTML = recargoLine + (ok
        ? `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Cuotas OK ($${instSum.toFixed(2)})</span>`
        : `<span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Suma cuotas: $${instSum.toFixed(2)} / Requerido: $${rem.toFixed(2)} (diff: $${diff})</span>`);
}

// ── SEARCH ──────────────────────────────────────────────────────────
function clearSearch() {
    document.getElementById('productSearch').value = '';
    document.getElementById('clearSearch').style.display = 'none';
    renderGrid('');
}

document.addEventListener('DOMContentLoaded', function () {
    renderGrid('');

    // Barras de filtro multiselección. Acepta varias barras DOM para el mismo Set
    // (p. ej. categoría por nombre y por código), manteniéndolas sincronizadas.
    function setupMultiBar(barIds, dataKey, set, onChange) {
        const bars = (Array.isArray(barIds) ? barIds : [barIds])
            .map(id => document.getElementById(id)).filter(Boolean);
        if (!bars.length) return;
        const cb = onChange || function () { renderGrid(document.getElementById('productSearch').value); };

        function syncActive() {
            bars.forEach(function (bar) {
                bar.querySelectorAll('.cat-pill').forEach(function (btn) {
                    const val = btn.dataset[dataKey] || '';
                    btn.classList.toggle('active', val === '' ? set.size === 0 : set.has(val));
                });
            });
        }
        bars.forEach(function (bar) {
            bar.querySelectorAll('.cat-pill').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const val = this.dataset[dataKey] || '';
                    if (val === '') { set.clear(); }
                    else if (set.has(val)) { set.delete(val); }
                    else { set.add(val); }
                    syncActive();
                    cb();
                });
            });
        });
        syncActive();
    }
    // Categoría: dos barras (nombre + código) sincronizadas sobre el mismo filtro
    setupMultiBar(['catBar', 'catCodeBar'], 'cat', selectedCats);
    setupMultiBar('modelBar', 'model', selectedModels);
    // Barras de filtro del modal de stock
    const stockOnChange = function () { renderStockTable(document.getElementById('stockSearch').value); };
    setupMultiBar('stockCatBar', 'cat', stockCats, stockOnChange);
    setupMultiBar('stockModelBar', 'model', stockModels, stockOnChange);

    const searchInput = document.getElementById('productSearch');
    searchInput.addEventListener('input', function () {
        document.getElementById('clearSearch').style.display = this.value ? '' : 'none';
        renderGrid(this.value);
    });

    document.getElementById('discountPct').addEventListener('input', updateBalanceIndicator);

    document.getElementById('creditModal').addEventListener('show.bs.modal', function () {
        updateBalanceIndicator();
    });

    renderClientList('');
    const clientSearch = document.getElementById('clientSearch');
    clientSearch.addEventListener('input', function () {
        renderClientList(this.value);
    });
    document.getElementById('clientModal').addEventListener('shown.bs.modal', function () {
        clientSearch.value = '';
        renderClientList('');
        clientSearch.focus();
    });

    // Navegación de la galería con flechas del teclado
    document.addEventListener('keydown', function (e) {
        const pm = document.getElementById('productModal');
        if (!pm.classList.contains('show') || pmPhotos.length < 2) return;
        if (e.key === 'ArrowLeft')  { e.preventDefault(); pmNav(-1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); pmNav(1); }
    });

    // ── Modal de stock por almacén ──────────────────────────────────
    const stockModal  = document.getElementById('stockModal');
    const stockSearch = document.getElementById('stockSearch');
    stockModal.addEventListener('show.bs.modal', function () {
        stockSearch.value = '';
        renderStockTable('');
    });
    stockSearch.addEventListener('input', function () { renderStockTable(this.value); });

    @if(session('print_receipt_id'))
    // Imprimir el recibo térmico de la venta recién registrada (sin salir del POS)
    (function () {
        const f = document.createElement('iframe');
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        f.src = '{{ route('sales.receipt', session('print_receipt_id')) }}';
        document.body.appendChild(f);
    })();
    @endif
});
</script>
@endpush
@endif

@endsection
