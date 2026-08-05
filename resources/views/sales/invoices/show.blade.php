@extends('layouts.app')
@section('title', 'Venta: ' . $sale->code)
@section('page')
<div class="container-fluid">

    {{-- Print-only top (hidden in screen) --}}
    <div class="print-header d-none">
        <h3 class="fw-bold mb-1">{{ $sale->company?->name ?? config('brand.name') }}</h3>
        <p class="mb-0 text-muted small">Comprobante de venta</p>
    </div>

    {{-- ── HEADER CARD ──────────────────────────────────────────────── --}}
    <div class="card card-soft mb-4 overflow-hidden no-print-shadow">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $sale->code }}</h1>
                        <span class="badge bg-{{ $sale->sale_type_color }}-subtle text-{{ $sale->sale_type_color }} border border-{{ $sale->sale_type_color }}-subtle">
                            {{ $sale->sale_type_label }}
                        </span>
                        <span class="badge bg-{{ $sale->payment_status_color }}-subtle text-{{ $sale->payment_status_color }} border border-{{ $sale->payment_status_color }}-subtle">
                            {{ $sale->payment_status_label }}
                        </span>
                        @if($sale->status === 'cancelled')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Anulada</span>
                        @endif
                        @if($fullyReturned)
                        <span class="badge bg-dark text-white"><i class="bi bi-arrow-return-left me-1"></i>Devuelta</span>
                        @elseif($hasReturns)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-arrow-return-left me-1"></i>Devolución parcial</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-person me-1"></i>{{ $sale->client_name }}</span>
                        @if($sale->client?->id_number)
                        <span><i class="bi bi-card-text me-1"></i>{{ $sale->client->id_number }}</span>
                        @endif
                        @if($sale->client?->phone)
                        <span><i class="bi bi-telephone me-1"></i>{{ $sale->client->phone }}</span>
                        @endif
                        @if($sale->branch)
                        <span><i class="bi bi-building me-1"></i>{{ $sale->branch->name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $sale->sale_date->format('d/m/Y') }}</span>
                        @if($sale->createdBy)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $sale->createdBy->name }}</span>
                        @endif
                        @if($sale->session?->cashRegister)
                        <span><i class="bi bi-safe2 me-1"></i>{{ $sale->session->cashRegister->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <button onclick="window.print()" class="btn btn-light border btn-sm">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </button>
                    <button type="button" onclick="printThermalReceipt()" class="btn btn-light border btn-sm">
                        <i class="bi bi-receipt me-1"></i>Recibo térmico
                    </button>
                    @if($sale->status === 'completed' && !$fullyReturned && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sale-returns.create', auth()->user()->getCurrentCompany())))
                    <a href="{{ route('sale-returns.create', $sale) }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-return-left me-1"></i>Devolver
                    </a>
                    @endif
                    @if($sale->status === 'completed' && $sale->payments->isEmpty())
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.delete', auth()->user()->getCurrentCompany()))
                        <form action="{{ route('sales.cancel', $sale) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Anular la venta {{ addslashes($sale->code) }}? Esta acción no se puede deshacer.')">
                            @csrf
                            <button class="btn btn-sm btn-light border text-danger">
                                <i class="bi bi-x-circle me-1"></i>Anular
                            </button>
                        </form>
                        @endif
                    @endif
                    @if($sale->sale_type === 'credit' && $sale->balance > 0)
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany()))
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cobroModal{{ $sale->id }}">
                            <i class="bi bi-cash-coin me-1"></i>Cobrar
                        </button>
                        @endif
                    @endif
                    <a href="{{ route('sales.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── ITEMS + TOTALS + INSTALLMENTS + PAYMENTS ──────────────── --}}
        <div class="col-lg-8">

            {{-- Items table --}}
            <div class="card card-soft cs-indigo mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Productos vendidos</h6>
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
                                @forelse($sale->items as $item)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-semibold small">{{ $item->display_name }}</div>
                                        @if($item->product)
                                        <small class="text-muted">{{ $item->product->sku }}</small>
                                        @else
                                        <small class="text-warning"><i class="bi bi-lightning-charge-fill me-1"></i>Venta rápida</small>
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
                                $itemsSubtotal = $sale->items->sum(fn($i) => $i->quantity * $i->unit_price - ($i->discount ?? 0));
                            @endphp
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Subtotal</span>
                                <span>${{ number_format($itemsSubtotal, 2) }}</span>
                            </div>
                            @if($sale->discount)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Descuento global</span>
                                <span>-${{ number_format($sale->discount, 2) }}</span>
                            </div>
                            @endif
                            @if($sale->tax)
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Impuesto</span>
                                <span>${{ number_format($sale->tax, 2) }}</span>
                            </div>
                            @endif
                            @if($hasReturns)
                            <div class="d-flex justify-content-between mb-1 small text-danger">
                                <span><i class="bi bi-arrow-return-left me-1"></i>Devoluciones</span>
                                <span>−${{ number_format($totalReturned, 2) }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Total {{ $hasReturns ? 'neto' : '' }}</span>
                                <span>${{ number_format($sale->total, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small text-success mt-1">
                                <span>Pagado</span>
                                <span>${{ number_format($sale->paid_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold border-top pt-2 {{ $sale->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                <span>Saldo</span>
                                <span>${{ number_format($sale->balance, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Installments (credit only) --}}
            @if($sale->sale_type === 'credit' && $sale->installments->isNotEmpty())
            <div class="card card-soft cs-teal mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-check me-2 text-muted"></i>Cronograma de cuotas</h6>
                    <span class="badge bg-light text-muted border">{{ $sale->installments->count() }} cuotas</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" style="font-size:.83rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted" style="font-size:.7rem;">#</th>
                                    <th class="py-3 fw-semibold text-muted" style="font-size:.7rem;">Vencimiento</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.7rem;">Monto</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.7rem;">Pagado</th>
                                    <th class="py-3 fw-semibold text-muted text-end" style="font-size:.7rem;">Saldo</th>
                                    <th class="py-3 fw-semibold text-muted pe-4" style="font-size:.7rem;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->installments as $inst)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2 small">{{ $inst->number }}</td>
                                    <td class="py-2 small {{ $inst->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                        {{ $inst->due_date->format('d/m/Y') }}
                                        @if($inst->is_overdue)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:.65rem;">VENCIDA</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-end small">${{ number_format($inst->amount, 2) }}</td>
                                    <td class="py-2 text-end small text-success">${{ number_format($inst->paid_amount, 2) }}</td>
                                    <td class="py-2 text-end fw-semibold small {{ $inst->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                        ${{ number_format($inst->balance, 2) }}
                                    </td>
                                    <td class="py-2 pe-4">
                                        <span class="badge bg-{{ $inst->status_color }}-subtle text-{{ $inst->status_color }} border border-{{ $inst->status_color }}-subtle" style="font-size:.68rem;">
                                            {{ $inst->status_label }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Payments history --}}
            <div class="card card-soft cs-green">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Pagos registrados</h6>
                    <span class="badge bg-light text-muted border">{{ $sale->payments->count() }}</span>
                </div>
                <div class="card-body p-0">
                    @forelse($sale->payments as $payment)
                    <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                        <div>
                            <div class="fw-semibold small">${{ number_format($payment->amount, 2) }}</div>
                            <div class="text-muted" style="font-size:.78rem;">
                                {{ $payment->payment_date->format('d/m/Y') }}
                                @if($payment->method) &middot; {{ ucfirst($payment->method) }} @endif
                                @if($payment->reference) &middot; Ref: {{ $payment->reference }} @endif
                            </div>
                        </div>
                        <div class="text-muted small">{{ $payment->user?->name ?: '—' }}</div>
                    </div>
                    @empty
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-cash-coin fs-2 d-block mb-2 opacity-25"></i>
                        Sin pagos registrados aún.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Devoluciones --}}
            @if($hasReturns)
            <div class="card card-soft cs-amber mt-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-arrow-return-left me-2 text-muted"></i>Devoluciones</h6>
                    <span class="badge bg-dark text-white">−${{ number_format($totalReturned, 2) }}</span>
                </div>
                <div class="card-body p-0">
                    @foreach($sale->returns as $ret)
                    <a href="{{ route('sale-returns.show', $ret) }}"
                       class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light text-decoration-none text-dark">
                        <div>
                            <div class="fw-semibold small">{{ $ret->code }}</div>
                            <div class="text-muted" style="font-size:.78rem;">
                                {{ $ret->return_date->format('d/m/Y') }} &middot; {{ $ret->refund_method_label }}
                            </div>
                        </div>
                        <div class="fw-semibold small text-danger">−${{ number_format($ret->total, 2) }}</div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- ── SIDEBAR INFO ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card card-soft cs-sky mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Información</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $sale->code }}</dd>
                        <dt class="col-5 text-muted fw-normal">Tipo</dt>
                        <dd class="col-7 mb-0">{{ $sale->sale_type_label }}</dd>
                        <dt class="col-5 text-muted fw-normal">Cliente</dt>
                        <dd class="col-7 mb-0">{{ $sale->client_name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Sucursal</dt>
                        <dd class="col-7 mb-0">{{ $sale->branch?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Fecha</dt>
                        <dd class="col-7 mb-0">{{ $sale->sale_date->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted fw-normal">Creado por</dt>
                        <dd class="col-7 mb-0">{{ $sale->createdBy?->name ?? '—' }}</dd>
                        @if($sale->session?->cashRegister)
                        <dt class="col-5 text-muted fw-normal">Caja</dt>
                        <dd class="col-7 mb-0">{{ $sale->session->cashRegister->name }}</dd>
                        @endif
                        <dt class="col-5 text-muted fw-normal">Registrado</dt>
                        <dd class="col-7 mb-0">{{ $sale->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            @if($sale->balance > 0 && $sale->status !== 'cancelled')
            <div class="card card-soft cs-rose">
                <div class="card-body p-4">
                    <div class="text-muted small mb-1">Saldo pendiente</div>
                    <div class="fw-bold text-danger fs-4">${{ number_format($sale->balance, 2) }}</div>
                    @if($sale->sale_type === 'credit')
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany()))
                        <button type="button" class="btn btn-primary btn-sm w-100 mt-3 no-print" data-bs-toggle="modal" data-bs-target="#cobroModal{{ $sale->id }}">
                            <i class="bi bi-cash-coin me-1"></i>Registrar cobro
                        </button>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            {{-- Datos del cliente --}}
            @if($sale->client)
            <div class="card card-soft cs-violet mt-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2 text-muted"></i>Datos del cliente</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-2 small mb-3">
                        <dt class="col-5 text-muted fw-normal">Nombre</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $sale->client->full_name }}</dd>
                        @if($sale->client->id_number)
                        <dt class="col-5 text-muted fw-normal">Documento</dt>
                        <dd class="col-7 mb-0">{{ $sale->client->id_number }}</dd>
                        @endif
                        @if($sale->client->phone)
                        <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                        <dd class="col-7 mb-0">
                            <a href="tel:{{ $sale->client->phone }}" class="text-decoration-none">{{ $sale->client->phone }}</a>
                        </dd>
                        @endif
                        @if($sale->client->email)
                        <dt class="col-5 text-muted fw-normal">Email</dt>
                        <dd class="col-7 mb-0 text-truncate">
                            <a href="mailto:{{ $sale->client->email }}" class="text-decoration-none">{{ $sale->client->email }}</a>
                        </dd>
                        @endif
                        @if($sale->client->address)
                        <dt class="col-5 text-muted fw-normal">Dirección</dt>
                        <dd class="col-7 mb-0">{{ $sale->client->address }}</dd>
                        @endif
                    </dl>
                    <a href="{{ route('clients.show', $sale->client) }}" class="btn btn-light border btn-sm w-100 no-print">
                        <i class="bi bi-person-lines-fill me-1"></i>Ver ficha del cliente
                    </a>
                </div>
            </div>
            @else
            <div class="card card-soft mt-4">
                <div class="card-body p-4 text-center text-muted small">
                    <i class="bi bi-person d-block fs-3 mb-2 opacity-25"></i>
                    Cliente general
                </div>
            </div>
            @endif

        </div>

    </div>

</div>

{{-- Modal de cobro (venta a crédito con saldo) --}}
@if($sale->sale_type === 'credit' && $sale->balance > 0 && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany())))
    @include('sales.credit.partials.cobro-modal', ['sale' => $sale])
    @include('sales.credit.partials.cobro-assets')
@endif

@push('styles')
<style>
/* Cards con estilo outline suave y acento de color amigable por sección */
.card-soft {
    border: 1.5px solid var(--cs-border, #e6e9f0) !important;
    border-radius: 14px !important;
    box-shadow: 0 1px 2px rgba(16,24,40,.04) !important;
    transition: box-shadow .18s ease, transform .18s ease;
}
.card-soft:hover {
    box-shadow: 0 8px 24px rgba(16,24,40,.08) !important;
    transform: translateY(-1px);
}
.card-soft > .card-header,
.card-soft > .card-footer {
    background: var(--cs-tint, #fff) !important;
    border-color: var(--cs-border, #eef1f6) !important;
}
.card-soft > .card-header h6 i { color: var(--cs-accent, #6c757d) !important; }

/* Variantes de acento (pastel) */
.cs-indigo { --cs-border:#c7d2fe; --cs-tint:#eef2ff; --cs-accent:#4f46e5; }
.cs-teal   { --cs-border:#99f6e4; --cs-tint:#f0fdfa; --cs-accent:#0d9488; }
.cs-green  { --cs-border:#bbf7d0; --cs-tint:#f0fdf4; --cs-accent:#16a34a; }
.cs-amber  { --cs-border:#fde68a; --cs-tint:#fffbeb; --cs-accent:#d97706; }
.cs-sky    { --cs-border:#bae6fd; --cs-tint:#f0f9ff; --cs-accent:#0284c7; }
.cs-violet { --cs-border:#ddd6fe; --cs-tint:#f5f3ff; --cs-accent:#7c3aed; }
.cs-rose   { --cs-border:#fecdd3; --cs-tint:#fff1f2; --cs-accent:#e11d48; }

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

@push('scripts')
<script>
    const RECEIPT_URL = @json(route('sales.receipt', $sale));

    // Botón "Recibo térmico": abre el recibo 80mm en ventana emergente (auto-imprime).
    // La ventana se abre ancha para que el diálogo de impresión muestre la vista previa
    // (con una ventana angosta Chrome solo muestra el panel de configuración).
    function printThermalReceipt() {
        const W = 980, H = 720;
        const left = Math.max(0, (window.screen.width  - W) / 2);
        const top  = Math.max(0, (window.screen.height - H) / 2);
        const w = window.open(RECEIPT_URL, 'recibo_termico',
            `width=${W},height=${H},left=${left},top=${top}`);
        if (!w) {
            // Pop-up bloqueado: caer a impresión vía iframe oculto.
            printReceiptInIframe();
        }
    }

    // Impresión silenciosa mediante iframe oculto (no requiere pop-ups).
    function printReceiptInIframe() {
        let frame = document.getElementById('thermalReceiptFrame');
        if (!frame) {
            frame = document.createElement('iframe');
            frame.id = 'thermalReceiptFrame';
            frame.style.position = 'fixed';
            frame.style.right = '0';
            frame.style.bottom = '0';
            frame.style.width = '0';
            frame.style.height = '0';
            frame.style.border = '0';
            document.body.appendChild(frame);
        }
        frame.src = RECEIPT_URL; // la propia vista llama a window.print() al cargar
    }

    @if(session('print_receipt'))
    // Auto-impresión tras registrar la venta en el POS.
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(printReceiptInIframe, 400);
    });
    @endif

    @if(session('cobro_receipt_url'))
    // Auto-impresión del recibo de cobro recién registrado.
    document.addEventListener('DOMContentLoaded', function () {
        const f = document.createElement('iframe');
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        f.src = @json(session('cobro_receipt_url'));
        document.body.appendChild(f);
    });
    @endif
</script>
@endpush

@endsection
