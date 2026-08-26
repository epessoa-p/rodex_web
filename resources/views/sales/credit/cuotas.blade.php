@extends('layouts.app')
@section('title', 'Cuotas de crédito')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-calendar2-check me-2 text-danger"></i>Cuotas de crédito</h1>
            <p class="text-muted mb-0 small">Seguimiento de cuotas por venta a crédito.</p>
        </div>
    </div>

    {{-- ── FILTERS ────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                <div>
                    <label class="form-label small fw-semibold mb-1">Estado</label>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('credit.cuotas', array_merge(request()->except('status'), [])) }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">Todas</a>
                        @foreach(['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagada', 'overdue' => 'Vencida'] as $key => $lbl)
                        <a href="{{ route('credit.cuotas', array_merge(request()->except('status'), ['status' => $key])) }}"
                           class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-light border' }}">
                            {{ $lbl }}
                        </a>
                        @endforeach
                    </div>
                </div>
                <div style="min-width:200px;">
                    <label class="form-label small fw-semibold mb-1">Cliente</label>
                    <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los clientes</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- ── TABLE ──────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Venta</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Cuota #</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Vencimiento</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Monto</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Pagado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Saldo</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase pe-4" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($installments as $inst)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('sales.show', $inst->sale) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $inst->sale->code }}
                                </a>
                                @else
                                <span class="fw-semibold">{{ $inst->sale->code }}</span>
                                @endif
                            </td>
                            <td class="py-2 small">{{ $inst->sale->client?->full_name ?? 'Cliente general' }}</td>
                            <td class="py-2 text-center small">{{ $inst->number }}</td>
                            <td class="py-2 small {{ $inst->is_overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $inst->due_date->format('d/m/Y') }}
                                @if($inst->is_overdue)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size:.62rem;">VENCIDA</span>
                                @endif
                            </td>
                            <td class="py-2 text-end small">{{ money($inst->amount, null, 2) }}</td>
                            <td class="py-2 text-end small text-success">{{ money($inst->paid_amount, null, 2) }}</td>
                            <td class="py-2 text-end fw-semibold small {{ $inst->balance > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ money($inst->balance, null, 2) }}
                            </td>
                            <td class="py-2 pe-4">
                                <span class="badge bg-{{ $inst->status_color }}-subtle text-{{ $inst->status_color }} border border-{{ $inst->status_color }}-subtle" style="font-size:.68rem;">
                                    {{ $inst->status_label }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar2-check fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay cuotas con los filtros seleccionados.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $installments->withQueryString()->links() }}</div>

</div>
@endsection
