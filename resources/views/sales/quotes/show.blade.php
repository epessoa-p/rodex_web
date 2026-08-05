@extends('layouts.app')
@section('title', 'Cotización: ' . $quote->code)
@section('page')
<div class="container-fluid">

    {{-- Print-only header --}}
    <div class="print-header d-none">
        <h3 class="fw-bold mb-1">{{ $quote->company?->name ?? config('brand.name') }}</h3>
        <p class="mb-0 text-muted small">Cotización</p>
    </div>

    {{-- ── HEADER CARD ─────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden no-print-shadow">
        <div style="height:6px;background:linear-gradient(90deg,#212529 0%,#495057 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $quote->code }}</h1>
                        <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }} border border-{{ $quote->status_color }}-subtle">
                            {{ $quote->status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-person me-1"></i>{{ $quote->client_name }}</span>
                        @if($quote->branch)
                        <span><i class="bi bi-building me-1"></i>{{ $quote->branch->name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>Fecha: {{ $quote->quote_date->format('d/m/Y') }}</span>
                        @if($quote->valid_until)
                        <span><i class="bi bi-calendar-check me-1"></i>Válida hasta: {{ $quote->valid_until->format('d/m/Y') }}</span>
                        @endif
                        @if($quote->createdBy)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $quote->createdBy->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <button onclick="window.print()" class="btn btn-light border btn-sm">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>

                    @if($quote->status !== 'converted')
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.edit', auth()->user()->getCurrentCompany()))

                        {{-- Cambiar estado --}}
                        <div class="dropdown">
                            <button class="btn btn-light border btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-arrow-repeat me-1"></i>Cambiar estado
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                @foreach([
                                    'sent'     => ['label' => 'Enviada',   'icon' => 'bi-send'],
                                    'accepted' => ['label' => 'Aceptada',  'icon' => 'bi-check-circle'],
                                    'rejected' => ['label' => 'Rechazada', 'icon' => 'bi-x-circle'],
                                    'expired'  => ['label' => 'Vencida',   'icon' => 'bi-clock-history'],
                                ] as $st => $info)
                                @if($quote->status !== $st)
                                <li>
                                    <form action="{{ route('quotes.status', $quote) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $st }}">
                                        <button type="submit" class="dropdown-item small">
                                            <i class="bi {{ $info['icon'] }} me-2 text-muted"></i>{{ $info['label'] }}
                                        </button>
                                    </form>
                                </li>
                                @endif
                                @endforeach
                            </ul>
                        </div>

                        {{-- Editar --}}
                        <a href="{{ route('quotes.edit', $quote) }}" class="btn btn-light border btn-sm">
                            <i class="bi bi-pencil me-1"></i>Editar
                        </a>

                        @endif

                        {{-- Convertir en venta --}}
                        @if($quote->isConvertible())
                        <a href="{{ route('quotes.convert', $quote) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i>Convertir en venta
                        </a>
                        @endif
                    @endif

                    <a href="{{ route('quotes.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Converted banner --}}
    @if($quote->status === 'converted' && $quote->convertedSale)
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-check-circle-fill fs-5"></i>
        <div>
            Cotización convertida en venta
            <a href="{{ route('sales.show', $quote->convertedSale) }}" class="alert-link fw-semibold">
                {{ $quote->convertedSale->code }}
            </a>
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- ── ITEMS + TOTALS ─────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Items table --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos cotizados</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted" style="font-size:.72rem;">Producto</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Cantidad</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Precio unit.</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.72rem;">Descuento</th>
                                    <th class="py-3 fw-semibold text-muted text-end pe-4" style="font-size:.72rem;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quote->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->product?->name ?: '—' }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="py-3 text-end small">{{ number_format($item->quantity, 0) }}</td>
                                    <td class="py-3 text-end small">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="py-3 text-end small text-muted">
                                        @if($item->discount > 0) ${{ number_format($item->discount, 2) }} @else — @endif
                                    </td>
                                    <td class="py-3 text-end fw-semibold small pe-4">
                                        ${{ number_format($item->quantity * $item->unit_price - ($item->discount ?? 0), 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Sin items.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top px-4 py-3">
                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            @php
                                $itemsSubtotal = $quote->items->sum(fn($i) => $i->quantity * $i->unit_price - ($i->discount ?? 0));
                            @endphp
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal</span>
                                <span>${{ number_format($itemsSubtotal, 2) }}</span>
                            </div>
                            @if($quote->discount)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Descuento global</span>
                                <span>-${{ number_format($quote->discount, 2) }}</span>
                            </div>
                            @endif
                            @if($quote->tax)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Impuesto</span>
                                <span>${{ number_format($quote->tax, 2) }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total</span>
                                <span>${{ number_format($quote->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            @if($quote->notes)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-chat-left-text me-2 text-muted"></i>Notas</h6>
                </div>
                <div class="card-body p-4">
                    <p class="mb-0 small text-muted" style="white-space:pre-wrap;">{{ $quote->notes }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- ── SIDEBAR INFO ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $quote->code }}</dd>
                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }} border border-{{ $quote->status_color }}-subtle" style="font-size:.72rem;">
                                {{ $quote->status_label }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted fw-normal">Cliente</dt>
                        <dd class="col-7 mb-0">{{ $quote->client_name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Sucursal</dt>
                        <dd class="col-7 mb-0">{{ $quote->branch?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $quote->quote_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Válida hasta</dt>
                        <dd class="col-7 mb-0">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Creado por</dt>
                        <dd class="col-7 mb-0">{{ $quote->createdBy?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Registrado</dt>
                        <dd class="col-7 mb-0">{{ $quote->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($quote->isConvertible() && $quote->status !== 'converted')
            <div class="card border-0 shadow-sm no-print" style="border-left:4px solid #212529 !important;">
                <div class="card-body p-4">
                    <div class="fw-semibold small mb-1">Lista para convertir</div>
                    <p class="text-muted small mb-3">Esta cotización está aceptada y puede convertirse en una venta formal.</p>
                    <a href="{{ route('quotes.convert', $quote) }}" class="btn btn-primary w-100 btn-sm">
                        <i class="bi bi-arrow-right-circle me-1"></i>Convertir en venta
                    </a>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>

@push('styles')
<style>
@media print {
    .no-print, .no-print * { display: none !important; }
    .app-sidebar, .app-topbar, .btn { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .print-header { display: block !important; margin-bottom: 1rem; }
    .no-print-shadow { box-shadow: none !important; }
}
</style>
@endpush

@endsection
