@extends('layouts.app')
@section('title', 'Pago a mecánicos')
@section('page')
@php
    $u = auth()->user(); $cc = $u->getCurrentCompany();
    $canView = $u->is_super_admin || $u->hasPermissionInCompany('mechanic-payments.view', $cc);
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-coin me-2 text-success"></i>Pago a mecánicos</h1>
            <p class="text-muted mb-0 small">Comisión sobre la mano de obra de las OTs entregadas. Se liquida por OT.</p>
        </div>
        <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border btn-sm"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Mecánico</th>
                        <th class="text-center">Comisión</th>
                        <th class="text-center">OTs pendientes</th>
                        <th class="text-end">Pendiente</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mechanics as $m)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $m['name'] }}@unless($m['active'])<span class="badge bg-secondary-subtle text-secondary-emphasis ms-2">inactivo</span>@endunless</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($m['commission_rate'], 2), '0'), '.') }}%</td>
                        <td class="text-center">{{ $m['pending_count'] }}</td>
                        <td class="text-end fw-bold {{ $m['pending'] > 0 ? 'text-danger' : 'text-success' }}">{{ money($m['pending']) }}</td>
                        <td class="text-end text-muted">{{ money($m['paid']) }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('workshop.mechanic-payments.show', $m['id']) }}" class="btn btn-sm btn-outline-success">
                                Ver / Pagar <i class="bi bi-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-person-gear fs-3 d-block mb-2 opacity-50"></i>No hay mecánicos con comisiones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
