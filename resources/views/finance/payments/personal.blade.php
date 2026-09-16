@extends('layouts.app')
@section('title', 'Pagos · Personal')
@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-coin me-2 text-danger"></i>Pagos</h1>
            <p class="text-muted mb-0 small">Personal: sueldos, jornales, quincenas y bonos. Salen de tu caja abierta o de tesorería.</p>
        </div>
        <div class="text-end">
            <div class="small text-muted">Pagado a personal este mes</div>
            <div class="fs-4 fw-bold text-danger">{{ money($overview['payroll_month_total']) }}</div>
        </div>
    </div>

    @include('finance.payments._tabs')

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-people me-2 text-primary"></i>Personal activo</h6>
            <a href="{{ route('personal.index') }}" class="small">Administrar personal</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Nombre</th>
                        <th>Cargo</th>
                        <th>Último pago</th>
                        <th class="text-end pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($overview['personal'] as $p)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $p['name'] }}</td>
                        <td class="small text-muted">{{ $p['cargo'] ?? '—' }}</td>
                        <td class="small">
                            @if($p['last_payment'])
                                <span class="fw-semibold">{{ money($p['last_payment']['amount']) }}</span>
                                <span class="text-muted">· {{ \Illuminate\Support\Carbon::parse($p['last_payment']['date'])->format('d/m/Y') }}</span>
                                @if($p['last_payment']['period'])<span class="badge bg-light text-dark border ms-1">{{ $p['last_payment']['period'] }}</span>@endif
                            @else
                                <span class="text-muted">Sin pagos registrados</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($canPay)
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="openPay({kind:'payroll', title:'Pagar a {{ addslashes($p['name']) }}', subtitle:'{{ addslashes($p['cargo'] ?? '') }}', personalId:{{ $p['id'] }}, period:true, amount:{{ $p['last_payment']['amount'] ?? 0 }}})">
                                <i class="bi bi-cash me-1"></i>Pagar
                            </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">No hay personal activo. <a href="{{ route('personal.index') }}">Regístralo</a> primero.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(! $session && $accounts->isEmpty() && $canPay)
    <div class="alert alert-warning border-0 shadow-sm mt-3 small mb-0">
        <i class="bi bi-info-circle me-1"></i>Para pagar necesitas tu caja abierta o una cuenta de tesorería con saldo.
    </div>
    @endif
</div>

@if($canPay)
    @include('finance.payments._pay_modal')
@endif
@endsection
