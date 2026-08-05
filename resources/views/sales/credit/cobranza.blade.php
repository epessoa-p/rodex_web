@extends('layouts.app')
@section('title', 'Cobranza')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-coin me-2 text-danger"></i>Cobranza</h1>
            <p class="text-muted mb-0 small">Ventas a crédito con saldo pendiente de cobro.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('credit.morosos') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-exclamation-octagon me-1"></i>Morosos
            </a>
            <a href="{{ route('credit.cuotas') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-calendar2-check me-1"></i>Cuotas
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid var(--brand-red) !important;">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(230,57,70,.08);">
                        <i class="bi bi-hourglass-split fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total por cobrar</div>
                        <div class="fw-bold text-danger fs-4">${{ number_format($totalReceivable, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter by client --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                <div style="min-width:220px;">
                    <label class="form-label small fw-semibold mb-1">Filtrar por cliente</label>
                    <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if(request('client_id'))
                <a href="{{ route('credit.cobranza') }}" class="btn btn-light border btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Venta</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Pagado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Saldo</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $sale->code }}
                                </a>
                                @else
                                <span class="fw-semibold">{{ $sale->code }}</span>
                                @endif
                            </td>
                            <td class="py-2 small">{{ $sale->client_name }}</td>
                            <td class="py-2 small text-muted">{{ $sale->sale_date->format('d/m/Y') }}</td>
                            <td class="py-2 text-end small">${{ number_format($sale->total, 2) }}</td>
                            <td class="py-2 text-end small text-success">${{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="py-2 text-end fw-bold text-danger">${{ number_format($sale->balance, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany()))
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cobroModal{{ $sale->id }}">
                                    <i class="bi bi-cash-coin me-1"></i>Cobrar
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">Sin cuentas por cobrar. Todo al dia.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $sales->withQueryString()->links() }}</div>

</div>

{{-- ── COBRO MODALS (one per sale) ────────────────────────────────── --}}
@if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany()))
@foreach($sales as $sale)
    @include('sales.credit.partials.cobro-modal', ['sale' => $sale])
@endforeach
@include('sales.credit.partials.cobro-assets')
@endif

@if(session('cobro_receipt_url'))
@push('scripts')
<script>
// Auto-impresión del recibo de cobro recién registrado.
document.addEventListener('DOMContentLoaded', function () {
    const f = document.createElement('iframe');
    f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    f.src = @json(session('cobro_receipt_url'));
    document.body.appendChild(f);
});
</script>
@endpush
@endif

@endsection
