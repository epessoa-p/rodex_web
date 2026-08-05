@extends('layouts.app')
@section('title', 'Tesorería')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bank me-2 text-danger"></i>Tesorería</h1>
            <p class="text-muted mb-0 small">Cuentas de efectivo y bancarias para pagos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.manage', auth()->user()->getCurrentCompany()))
        <a href="{{ route('treasury.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva cuenta
        </a>
        @endif
    </div>

    {{-- Balance total --}}
    <div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,var(--brand-black) 0%,#2a2a2a 100%)">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="text-white-50 small mb-1">Balance total en tesorería</div>
                    <div class="text-white fw-bold" style="font-size:2rem">${{ number_format($totalBalance, 2) }}</div>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:60px;height:60px;background:rgba(230,57,70,.2)">
                    <i class="bi bi-safe2 fs-3 text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($accounts as $account)
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div style="height:4px;background:{{ $account->type === 'bank' ? '#0d6efd' : '#198754' }};border-radius:8px 8px 0 0"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center"
                                 style="width:44px;height:44px;background:{{ $account->type === 'bank' ? 'rgba(13,110,253,.08)' : 'rgba(25,135,84,.08)' }}">
                                <i class="bi {{ $account->type === 'bank' ? 'bi-bank fs-5 text-primary' : 'bi-cash-coin fs-5 text-success' }}"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">{{ $account->name }}</div>
                                <div class="text-muted small">{{ $account->type_label }}</div>
                            </div>
                        </div>
                        @if($account->active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactiva</span>
                        @endif
                    </div>

                    @if($account->bank_name)
                    <div class="text-muted small mb-1"><i class="bi bi-building me-1"></i>{{ $account->bank_name }}</div>
                    @endif
                    @if($account->account_number)
                    <div class="text-muted small mb-3"><i class="bi bi-hash me-1"></i>{{ $account->account_number }}</div>
                    @endif

                    <div class="border-top pt-3 mt-3">
                        <div class="text-muted small mb-1">Saldo disponible</div>
                        <div class="fw-bold fs-5 {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($account->balance, 2) }}
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ route('treasury.show', $account) }}" class="btn btn-sm btn-light border flex-grow-1">
                            <i class="bi bi-eye me-1"></i>Ver movimientos
                        </a>
                        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.manage', auth()->user()->getCurrentCompany()))
                        <a href="{{ route('treasury.edit', $account) }}" class="btn btn-sm btn-light border" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-bank fs-1 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">No hay cuentas configuradas.</p>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.manage', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('treasury.create') }}" class="btn btn-sm btn-primary mt-3">
                        <i class="bi bi-plus-lg me-1"></i>Crear primera cuenta
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforelse
    </div>

</div>
@endsection
