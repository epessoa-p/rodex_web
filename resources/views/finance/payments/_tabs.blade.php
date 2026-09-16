{{-- Barra de tabs del hub "Pagos" (Finanzas). Se incluye arriba de cada tab:
     Mecánicos (pago a mecánicos), Proveedores (cuentas por pagar), Personal y Gastos. --}}
@php $paymentsTabs = \App\Support\PaymentsTabs::visible(auth()->user()); @endphp
@if(count($paymentsTabs) > 1)
<ul class="nav nav-pills gap-1 mb-4 payments-tabs">
    @foreach($paymentsTabs as $t)
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs($t['pattern']) ? 'active' : '' }}" href="{{ route($t['route']) }}">
            <i class="bi {{ $t['icon'] }} me-1"></i>{{ $t['label'] }}
        </a>
    </li>
    @endforeach
</ul>
@push('styles')
<style>
    .payments-tabs .nav-link { background:#fff; border:1px solid #e5e7eb; color:#374151; font-weight:600; }
    .payments-tabs .nav-link.active { background:var(--brand-red,#e63946); border-color:var(--brand-red,#e63946); color:#fff; }
</style>
@endpush
@endif
