@extends('layouts.app')
@section('title', 'Clientes morosos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Clientes morosos</h1>
            <p class="text-muted mb-0 small">Clientes con cuotas vencidas sin pagar.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', auth()->user()->getCurrentCompany()))
        <a href="{{ route('credit.cobranza') }}" class="btn btn-primary">
            <i class="bi bi-cash-coin me-1"></i>Ir a cobranza
        </a>
        @endif
    </div>

    {{-- KPI total en mora --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid var(--brand-red) !important;">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(230,57,70,.08);">
                        <i class="bi bi-exclamation-circle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Total en mora</div>
                        <div class="fw-bold text-danger fs-4">${{ number_format($totalOverdue, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(102,16,242,.08);">
                        <i class="bi bi-people fs-4 text-secondary"></i>
                    </div>
                    <div>
                        <div class="text-muted small mb-1">Clientes en mora</div>
                        <div class="fw-bold fs-4">{{ $morosos->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Cuotas vencidas</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Vence desde</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Total adeudado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($morosos as $m)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-danger-subtle border border-danger-subtle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:32px;height:32px;font-size:.75rem;font-weight:700;color:#dc3545;">
                                        {{ strtoupper(substr($m->client_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold small lh-sm">{{ $m->client_name }}</div>
                                        @if($m->client)
                                        <small class="text-muted">{{ $m->client->id_number ?? '' }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">
                                    {{ $m->overdue_count }}
                                </span>
                            </td>
                            <td class="py-3 small text-danger fw-semibold">
                                @if($m->oldest_due instanceof \Carbon\Carbon)
                                    {{ $m->oldest_due->format('d/m/Y') }}
                                    <small class="text-muted fw-normal">({{ $m->oldest_due->diffForHumans() }})</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 text-end fw-bold text-danger fs-6 pe-4">
                                ${{ number_format($m->overdue_total, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center mb-3"
                                     style="width:64px;height:64px;">
                                    <i class="bi bi-check-circle fs-2 text-success"></i>
                                </div>
                                <p class="fw-semibold mb-0">Sin morosos</p>
                                <small>Todos los clientes están al dia con sus pagos.</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
