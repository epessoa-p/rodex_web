@extends('layouts.app')
@section('title', 'Recepcionar mercadería — ' . $order->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-in-down me-2 text-danger"></i>Recepcionar mercadería</h1>
            <p class="text-muted mb-0 small">
                Orden <strong>{{ $order->code }}</strong> &mdash; {{ $order->supplier->name }}
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">
                    <i class="bi bi-receipt me-1"></i>Registra la compra automáticamente
                </span>
            </p>
        </div>
        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver a OC
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

    <form action="{{ route('goods-receipts.store', $order) }}" method="POST">
        @csrf

        <div class="row g-4">

            <div class="col-lg-8">

                {{-- Datos de recepción --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de recepción</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="warehouse_id">
                                    Almacén destino <span class="text-danger">*</span>
                                </label>
                                <select id="warehouse_id" name="warehouse_id"
                                        class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                    <option value="">— Seleccionar almacén —</option>
                                    @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" {{ old('warehouse_id') == $w->id ? 'selected' : '' }}>
                                        {{ $w->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="receipt_date">
                                    Fecha de recepción <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="receipt_date" name="receipt_date"
                                       class="form-control @error('receipt_date') is-invalid @enderror"
                                       value="{{ old('receipt_date', now()->format('Y-m-d')) }}"
                                       required>
                                @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="invoice_number">N° de factura</label>
                                <input type="text" id="invoice_number" name="invoice_number"
                                       class="form-control @error('invoice_number') is-invalid @enderror"
                                       value="{{ old('invoice_number') }}" maxlength="100"
                                       placeholder="N° de la factura del proveedor (opcional)">
                                @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="tax">Impuesto</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                    <input type="number" id="tax" name="tax" step="0.01" min="0"
                                           class="form-control @error('tax') is-invalid @enderror"
                                           value="{{ old('tax', 0) }}" placeholder="0.00">
                                </div>
                                @error('tax')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones de la recepción...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items a recepcionar --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-list-check me-2 text-muted"></i>Productos a recepcionar</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 small fw-semibold text-muted">Producto</th>
                                        <th class="py-3 small fw-semibold text-muted text-end">Ordenado</th>
                                        <th class="py-3 small fw-semibold text-muted text-end">Recibido</th>
                                        <th class="py-3 small fw-semibold text-muted text-end">Pendiente</th>
                                        <th class="py-3 small fw-semibold text-muted text-end pe-4">Cant. a recibir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $i => $item)
                                    @php
                                        $received = $item->received_quantity ?? 0;
                                        $pending  = $item->quantity - $received;
                                    @endphp
                                    @if($pending > 0)
                                    <tr class="border-bottom border-light">
                                        <input type="hidden" name="items[{{ $i }}][po_item_id]" value="{{ $item->id }}">
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                            @if($item->product)
                                            <small class="text-muted">{{ $item->product->sku }}</small>
                                            @endif
                                        </td>
                                        <td class="py-3 text-end small text-muted">{{ number_format($item->quantity, 0) }}</td>
                                        <td class="py-3 text-end small text-success">{{ number_format($received, 0) }}</td>
                                        <td class="py-3 text-end small text-warning fw-semibold">{{ number_format($pending, 0) }}</td>
                                        <td class="py-3 text-end pe-4">
                                            <input type="number" name="items[{{ $i }}][quantity]"
                                                   class="form-control form-control-sm text-end"
                                                   style="width:110px;display:inline-block"
                                                   step="1" min="1" inputmode="numeric" max="{{ $pending }}"
                                                   value="{{ old("items.{$i}.quantity", $pending) }}"
                                                   required>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Summary sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top mb-4" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-text me-2 text-muted"></i>Orden de compra</h6>
                    </div>
                    <div class="card-body p-4">
                        <dl class="row g-2 small mb-0">
                            <dt class="col-5 text-muted fw-normal">Código</dt>
                            <dd class="col-7 mb-0 fw-semibold">{{ $order->code }}</dd>
                            <dt class="col-5 text-muted fw-normal">Proveedor</dt>
                            <dd class="col-7 mb-0">{{ $order->supplier->name }}</dd>
                            <dt class="col-5 text-muted fw-normal">Fecha OC</dt>
                            <dd class="col-7 mb-0">{{ $order->order_date->format('d/m/Y') }}</dd>
                            <dt class="col-5 text-muted fw-normal">Total OC</dt>
                            <dd class="col-7 mb-0 fw-semibold">{{ money($order->total, null, 2) }}</dd>
                            <dt class="col-5 text-muted fw-normal">Estado</dt>
                            <dd class="col-7 mb-0">
                                <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle">
                                    {{ $order->status_label }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i>Confirmar recepción y registrar compra
                    </button>
                    <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-light border w-100">
                        <i class="bi bi-x-lg me-1"></i>Cancelar
                    </a>
                </div>
            </div>

        </div>
    </form>

</div>
@endsection
