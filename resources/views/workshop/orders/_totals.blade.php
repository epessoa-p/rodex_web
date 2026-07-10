<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-muted"></i>Totales</h6>
    </div>
    <div class="card-body p-4">
        <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Subtotal servicios</span>
            <span class="fw-semibold">${{ number_format($order->subtotal_services, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Subtotal repuestos</span>
            <span class="fw-semibold">${{ number_format($order->subtotal_parts, 2) }}</span>
        </div>
        @if($order->discount)
        <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Descuento</span>
            <span class="fw-semibold text-danger">-${{ number_format($order->discount, 2) }}</span>
        </div>
        @endif
        @if($order->tax)
        <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Impuesto</span>
            <span class="fw-semibold">${{ number_format($order->tax, 2) }}</span>
        </div>
        @endif
        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-2">
            <span>Total</span>
            <span class="fs-6">${{ number_format($order->total, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between mb-2 small text-success">
            <span>Pagado</span>
            <span class="fw-semibold">${{ number_format($order->paid_amount, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between fw-bold border-top pt-2 {{ $order->balance > 0 ? 'text-danger' : 'text-muted' }}">
            <span>Saldo</span>
            <span>${{ number_format($order->balance, 2) }}</span>
        </div>
    </div>
</div>
