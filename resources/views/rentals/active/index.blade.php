@extends('layouts.app')
@section('title', 'Alquileres · En curso')
@section('page')
@php
    $company = auth()->user()->getCurrentCompany();
    $canReturn = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.return', $company);
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bicycle me-2 text-danger"></i>Alquileres en curso</h1>
            <p class="text-muted mb-0 small">Motos actualmente alquiladas (entregadas y pendientes de devolución).</p>
        </div>
        <a href="{{ route('rentals.calendar') }}" class="btn btn-light border"><i class="bi bi-calendar3 me-1"></i>Ver calendario</a>
    </div>

    @include('rentals.partials.flash')

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Período</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Modalidad</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cuotas</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Saldo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Vence</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $c)
                        @php
                            $totalInst = $c->installments->count();
                            $paidInst  = $totalInst - $c->pending_installments;
                            $hasOverdue = $c->installments->contains(fn ($i) => $i->is_overdue);
                        @endphp
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2"><a href="{{ route('rentals.show', $c) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">{{ $c->code }}</a></td>
                            <td class="py-2 small">{{ $c->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $c->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $c->start_date?->format('d/m/Y') }} → {{ $c->end_date?->format('d/m/Y') }}</td>
                            <td class="py-2 small">
                                @if($c->isRenta())
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.66rem;">Renta · {{ $c->billing_period_label }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.66rem;">Pago único</span>
                                @endif
                            </td>
                            <td class="py-2 small">
                                @if($c->isRenta())
                                    <span class="{{ $hasOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $paidInst }}/{{ $totalInst }} pagadas</span>
                                    @if($hasOverdue)<i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Tiene cuotas vencidas"></i>@endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold {{ $c->balance > 0 ? '' : 'text-success' }}">Bs. {{ number_format($c->balance, 2) }}</td>
                            <td class="py-2 small">
                                @if($c->end_date && $c->end_date->isPast() && !$c->end_date->isToday())
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Vencido {{ $c->end_date->format('d/m') }}</span>
                                @else
                                    <span class="text-muted">{{ $c->end_date?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('rentals.show', $c) }}" class="btn btn-sm btn-light border" title="Ver detalle"><i class="bi bi-eye"></i></a>
                                    @if($canReturn)
                                    <a href="{{ route('rentals.return', $c) }}" class="btn btn-sm btn-primary" title="Registrar devolución"><i class="bi bi-box-arrow-in-down"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-bicycle fs-2 opacity-25 d-block mb-2"></i>No hay motos en alquiler actualmente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $contracts->links() }}</div>
</div>
@endsection
