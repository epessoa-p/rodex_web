@extends('layouts.app')
@section('title', 'Pagos · Gastos')
@section('page')
@php
    $canManageServices = auth()->user()->is_super_admin
        || auth()->user()->hasPermissionInCompany('expense-services.manage', auth()->user()->getCurrentCompany());
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-coin me-2 text-danger"></i>Pagos</h1>
            <p class="text-muted mb-0 small">Gastos: servicios recurrentes (luz, agua, internet…), transporte y otros gastos. Salen de tu caja abierta o de tesorería.</p>
        </div>
        <div class="text-end">
            <div class="small text-muted">Gastos este mes</div>
            <div class="fs-4 fw-bold text-danger">{{ money($overview['month_total']) }}</div>
        </div>
    </div>

    @include('finance.payments._tabs')

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($canPay)
    <div class="d-flex gap-2 flex-wrap mb-3">
        <button type="button" class="btn btn-danger" onclick="openPay({kind:'other', title:'Otro gasto', subtitle:'Gasto operativo sin catálogo', period:false})">
            <i class="bi bi-plus-lg me-1"></i>Otro gasto
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="openPay({kind:'transport', title:'Transporte / envío', subtitle:'Fletes, envíos, mensajería', concept:'envío', period:false})">
            <i class="bi bi-truck me-1"></i>Transporte
        </button>
        @if($canManageServices)
        <a href="{{ route('expense-services.create') }}" class="btn btn-light border ms-auto"><i class="bi bi-receipt-cutoff me-1"></i>Nuevo servicio</a>
        @endif
    </div>
    @endif

    <div class="row g-4">
        {{-- ── Servicios recurrentes ─────────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-receipt-cutoff me-2 text-primary"></i>Servicios recurrentes</h6>
                    <span class="small text-muted">pagado este mes</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">Servicio</th><th>Tipo</th><th>Este mes</th><th class="text-end pe-4"></th></tr></thead>
                        <tbody>
                        @forelse($overview['services'] as $s)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $s['name'] }}</div>
                                    @if($s['default_amount'] > 0)<div class="small text-muted">ref. {{ money($s['default_amount']) }}</div>@endif
                                </td>
                                <td class="small text-muted">{{ $s['type_label'] }}</td>
                                <td class="small">
                                    @if($s['paid_this_month'])
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2 me-1"></i>{{ money($s['paid_this_month']['amount']) }} · {{ \Illuminate\Support\Carbon::parse($s['paid_this_month']['date'])->format('d/m') }}</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pendiente</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($canPay)
                                    <button type="button" class="btn btn-sm {{ $s['paid_this_month'] ? 'btn-light border' : 'btn-danger' }}"
                                            onclick="openPay({kind:'service', title:'Pagar {{ addslashes($s['name']) }}', subtitle:'{{ addslashes($s['type_label']) }}', serviceId:{{ $s['id'] }}, period:true, amount:{{ $s['default_amount'] }}})">
                                        <i class="bi bi-cash me-1"></i>Pagar
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5">Sin servicios en el catálogo.
                                @if($canManageServices)<a href="{{ route('expense-services.create') }}">Crea el primero</a>.@endif</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Últimos egresos ───────────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Últimos egresos</h6>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($overview['recent'] as $m)
                    <div class="list-group-item d-flex justify-content-between align-items-start py-2">
                        <div class="lh-sm me-2">
                            <div class="small fw-semibold">{{ $m['description'] }}</div>
                            <div class="small text-muted">{{ \Illuminate\Support\Carbon::parse($m['date'])->format('d/m/Y') }} · {{ $m['source'] }}</div>
                        </div>
                        <span class="fw-bold text-danger text-nowrap">- {{ money($m['amount']) }}</span>
                    </div>
                    @empty
                    <div class="list-group-item text-muted small text-center py-4">Aún no hay gastos registrados.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if(! $session && $accounts->isEmpty() && $canPay)
    <div class="alert alert-warning border-0 shadow-sm mt-3 small mb-0">
        <i class="bi bi-info-circle me-1"></i>Para registrar un gasto necesitas tu caja abierta o una cuenta de tesorería con saldo.
    </div>
    @endif
</div>

@if($canPay)
    @include('finance.payments._pay_modal')
@endif
@endsection
