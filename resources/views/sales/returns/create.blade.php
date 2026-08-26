@extends('layouts.app')
@section('title', 'Registrar devolución — ' . $sale->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-arrow-return-left me-2 text-dark"></i>Registrar devolución</h1>
            <p class="text-muted mb-0 small">
                Venta <strong>{{ $sale->code }}</strong>
                @if($sale->client) &mdash; {{ $sale->client->full_name }} @endif
            </p>
        </div>
        <a href="{{ route('sales.show', $sale) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver a venta
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

    @php
        $returnableItems = $sale->items->filter(function($item) use ($returnedByItem) {
            return ($item->quantity - ($returnedByItem[$item->id] ?? 0)) > 0;
        });
    @endphp

    @if($returnableItems->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 text-center py-5">
            <i class="bi bi-check-circle-fill fs-1 d-block mb-3 text-success opacity-75"></i>
            <h5 class="fw-semibold mb-2">Esta venta ya fue devuelta por completo</h5>
            <p class="text-muted small mb-4">Todos los productos de esta venta ya han sido devueltos.</p>
            <a href="{{ route('sales.show', $sale) }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver a la venta
            </a>
        </div>
    </div>
    @else

    <form action="{{ route('sale-returns.store', $sale) }}" method="POST" id="returnForm">
        @csrf

        <div class="row g-4">

            {{-- ── MAIN (left) ──────────────────────────────────────── --}}
            <div class="col-lg-8">

                {{-- Header card: venta de origen --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt me-2 text-muted"></i>Venta de origen</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 small">
                            <div class="col-sm-3">
                                <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Código</div>
                                <div class="fw-semibold">{{ $sale->code }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Cliente</div>
                                <div>{{ $sale->client?->full_name ?? 'Cliente general' }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Fecha</div>
                                <div>{{ $sale->sale_date->format('d/m/Y') }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="text-muted mb-1" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Total</div>
                                <div class="fw-semibold">{{ money($sale->total, null, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datos de la devolución --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la devolución</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="return_date">
                                    Fecha de devolución <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="return_date" name="return_date"
                                       class="form-control @error('return_date') is-invalid @enderror"
                                       value="{{ old('return_date', now()->format('Y-m-d')) }}" required>
                                @error('return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="refund_method">
                                    Método de reembolso <span class="text-danger">*</span>
                                </label>
                                <select id="refund_method" name="refund_method"
                                        class="form-select @error('refund_method') is-invalid @enderror"
                                        required onchange="onRefundMethodChange()">
                                    <option value="cash" {{ old('refund_method', 'cash') === 'cash' ? 'selected' : '' }}>
                                        Efectivo (egreso de caja)
                                    </option>
                                    <option value="credit_note" {{ old('refund_method') === 'credit_note' ? 'selected' : '' }}>
                                        Nota de crédito
                                    </option>
                                </select>
                                @error('refund_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12" id="cashAlert">
                                <div class="alert alert-warning border-0 mb-0 py-2 px-3 small d-flex align-items-center gap-2">
                                    <i class="bi bi-cash-coin flex-shrink-0"></i>
                                    El efectivo sale de tu caja abierta.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="reason">Motivo</label>
                                <input type="text" id="reason" name="reason"
                                       class="form-control @error('reason') is-invalid @enderror"
                                       value="{{ old('reason') }}"
                                       placeholder="Ej: Producto defectuoso, error en pedido...">
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones adicionales...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Items a devolver --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check me-2 text-muted"></i>Productos a devolver</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 fw-semibold text-muted" style="font-size:.72rem;">Producto</th>
                                        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Vendido</th>
                                        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Ya devuelto</th>
                                        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Devolvible</th>
                                        <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Precio</th>
                                        <th class="py-3 fw-semibold text-muted text-end pe-4" style="font-size:.72rem;">Cant. a devolver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $i => $item)
                                    @php
                                        $alreadyReturned = $returnedByItem[$item->id] ?? 0;
                                        $returnable = $item->quantity - $alreadyReturned;
                                    @endphp
                                    @if($returnable > 0)
                                    <tr class="border-bottom border-light">
                                        <input type="hidden" name="items[{{ $i }}][sale_item_id]" value="{{ $item->id }}">
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                            @if($item->product)
                                            <small class="text-muted">{{ $item->product->sku }}</small>
                                            @endif
                                        </td>
                                        <td class="py-3 text-end small text-muted">{{ number_format($item->quantity, 0) }}</td>
                                        <td class="py-3 text-end small {{ $alreadyReturned > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                                            {{ number_format($alreadyReturned, 2) }}
                                        </td>
                                        <td class="py-3 text-end small text-success fw-semibold">{{ number_format($returnable, 0) }}</td>
                                        <td class="py-3 text-end small">{{ money($item->unit_price, null, 2) }}</td>
                                        <td class="py-3 text-end pe-4">
                                            <input type="number"
                                                   name="items[{{ $i }}][quantity]"
                                                   class="form-control form-control-sm text-end return-qty"
                                                   style="width:110px;display:inline-block"
                                                   step="1" min="0" inputmode="numeric" max="{{ $returnable }}"
                                                   value="{{ old("items.{$i}.quantity", 0) }}"
                                                   data-price="{{ $item->unit_price }}"
                                                   oninput="recalcReturn()">
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top px-4 py-3">
                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                    <span>Total a reembolsar</span>
                                    <span id="displayReturnTotal" class="text-danger">{{ currency_symbol() }} 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── RIGHT SIDEBAR ────────────────────────────────────── --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-check2-square me-2 text-muted"></i>Resumen</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Productos a devolver</span>
                            <span class="fw-semibold" id="summaryItems">0</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-3 mt-2">
                            <span>Total reembolso</span>
                            <span id="summaryTotal" class="fs-5 text-danger">{{ currency_symbol() }} 0.00</span>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Registrar devolución
                            </button>
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

    @endif

</div>

@push('scripts')
<script>
function onRefundMethodChange() {
    const method = document.getElementById('refund_method').value;
    const alert  = document.getElementById('cashAlert');
    if (alert) alert.style.display = method === 'cash' ? 'block' : 'none';
}

function recalcReturn() {
    let total = 0;
    let count = 0;
    document.querySelectorAll('.return-qty').forEach(input => {
        const qty   = parseFloat(input.value) || 0;
        const price = parseFloat(input.dataset.price) || 0;
        if (qty > 0) count++;
        total += qty * price;
    });
    const fmt = v => money(v, 2);
    document.getElementById('displayReturnTotal').textContent = fmt(total);
    document.getElementById('summaryItems').textContent       = count;
    document.getElementById('summaryTotal').textContent       = fmt(total);
}

document.addEventListener('DOMContentLoaded', function () {
    onRefundMethodChange();
    recalcReturn();
});
</script>
@endpush

@endsection
