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
                        <td class="py-2 text-end small">${{ number_format($part->unit_price, 2) }}</td>
                        <td class="py-2 text-end fw-semibold small">${{ number_format($part->quantity * $part->unit_price, 2) }}</td>
                        @if($editable && $canEdit)
                        <td class="py-2 pe-3">
                            <form action="{{ route('workshop.orders.parts.remove', [$order, $part]) }}" method="POST" class="d-inline wo-ajax-form">
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
        <form action="{{ route('workshop.orders.parts.add', $order) }}" method="POST" class="wo-ajax-form wo-add-form">
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
                        <span class="input-group-text bg-white">$</span>
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
    </div>
    @endif
</div>
