@php
    $editable = !in_array($order->status, ['entregada', 'anulada']);
    $canEdit  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.edit', auth()->user()->getCurrentCompany());
    $colspan  = $editable && $canEdit ? 5 : 4;
@endphp
<div class="card border-0 shadow-sm mb-4 wo-card-parts" style="border-left:4px solid #f59e0b !important;">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2" style="color:#f59e0b;"></i>Repuestos</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Producto</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;">Cant.</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Precio</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Subtotal</th>
                        @if($editable && $canEdit)
                        <th class="py-3 pe-3" style="width:44px;"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->parts as $part)
                    <tr class="border-bottom border-light">
                        <td class="ps-4 py-2 small fw-semibold">{{ $part->product?->name ?? '—' }}</td>
                        <td class="py-2 text-center small">{{ $part->quantity }}</td>
                        <td class="py-2 text-end small">{{ money($part->unit_price, null, 2) }}</td>
                        <td class="py-2 text-end fw-semibold small">{{ money($part->quantity * $part->unit_price, null, 2) }}</td>
                        @if($editable && $canEdit)
                        <td class="py-2 pe-3">
                            <form action="{{ route('workshop.orders.parts.remove', [$order, $part]) }}" method="POST" class="d-inline wo-ajax-form" data-no-spinner>
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light border text-danger py-0 px-1" title="Quitar repuesto">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="text-center py-4 text-muted">
                            <i class="bi bi-box-seam d-block fs-3 mb-1 opacity-25"></i>
                            Sin repuestos agregados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($editable && $canEdit)
    <div class="card-footer bg-light border-top p-4">
        <p class="small fw-semibold mb-1"><i class="bi bi-plus-circle me-1" style="color:#f59e0b;"></i>Agregar repuesto</p>
        <p class="text-muted small mb-3">El stock se descuenta al entregar la OT.</p>
        <form action="{{ route('workshop.orders.parts.add', $order) }}" method="POST" class="wo-ajax-form wo-add-form" data-no-spinner>
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label form-label-sm fw-semibold mb-1">Producto</label>
                    <select name="product_id" class="form-select form-select-sm wo-part-select" required>
                        <option value="">— Seleccionar producto —</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}" data-price="{{ $p->price }}" data-stock="{{ $p->current_stock }}">
                            {{ $p->name }} (stock: {{ $p->current_stock }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Cant.</label>
                    <input type="number" name="quantity" class="form-control form-control-sm"
                           min="1" step="1" inputmode="numeric" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">Precio unit.</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">{{ currency_symbol() }}</span>
                        <input type="number" name="unit_price" class="form-control wo-part-price"
                               step="0.01" min="0" required placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Agregar
                    </button>
                </div>
            </div>
        </form>

        {{-- Acceso a compra directa: para el repuesto que no está en inventario o sin stock --}}
        <div class="text-center mt-3 pt-3 border-top">
            <span class="text-muted small">¿No está en inventario o se compró al momento?</span>
            <button type="button" class="btn btn-link btn-sm text-decoration-none fw-semibold p-0 ms-1"
                    style="color:#f59e0b;" data-bs-toggle="modal" data-bs-target="#woPurchaseModal">
                <i class="bi bi-cart-plus me-1"></i>Compra directa
            </button>
        </div>
    </div>

    {{-- ── MODAL: Compra directa de repuesto ─────────────────────────── --}}
    <div class="modal fade" id="woPurchaseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-cart-plus me-2" style="color:#f59e0b;"></i>Compra directa de repuesto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('workshop.orders.parts.purchase', $order) }}" method="POST" class="wo-ajax-form" data-no-spinner>
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-light border small d-flex gap-2 mb-3">
                            <i class="bi bi-info-circle mt-1" style="color:#f59e0b;"></i>
                            <span>Se registra el <strong>ingreso al stock</strong> (con su costo) y se agrega el
                                repuesto a esta OT. Si el producto no existe, se crea.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">Producto</label>
                            <input type="text" name="product_name" class="form-control form-control-sm wo-purchase-name"
                                   list="wpProducts" autocomplete="off" required
                                   placeholder="Escribe o elige un producto…">
                            <datalist id="wpProducts">
                                @foreach($products as $p)
                                <option value="{{ $p->name }}" data-price="{{ $p->price }}"
                                        data-cost="{{ $p->cost }}" data-stock="{{ $p->current_stock }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text" style="font-size:.7rem;">Si no existe, se crea automáticamente.</div>
                        </div>

                        <div class="row g-2">
                            <div class="col-4">
                                <label class="form-label form-label-sm fw-semibold mb-1">Cantidad</label>
                                <input type="number" name="quantity" class="form-control form-control-sm"
                                       min="1" step="1" inputmode="numeric" value="1" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label form-label-sm fw-semibold mb-1">Costo unit.</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">{{ currency_symbol() }}</span>
                                    <input type="number" name="cost" class="form-control wo-purchase-cost"
                                           step="0.01" min="0" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-4">
                                <label class="form-label form-label-sm fw-semibold mb-1">Precio venta</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">{{ currency_symbol() }}</span>
                                    <input type="number" name="unit_price" class="form-control wo-purchase-price"
                                           step="0.01" min="0" required placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-check-lg me-1"></i>Comprar y agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
