@extends('layouts.app')
@section('title', 'Alquileres · Penalizaciones')
@section('page')
<div class="container-fluid">

    <div class="mb-4">
        <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Penalizaciones</h1>
        <p class="text-muted mb-0 small">Cargos aplicados a contratos de alquiler.</p>
    </div>

    @include('rentals.partials.flash')

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Contrato</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Concepto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($penalties as $p)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 small text-muted">{{ $p->penalty_date?->format('d/m/Y') }}</td>
                            <td class="py-2"><a href="{{ route('rentals.show', $p->rental_contract_id) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $p->contract?->code }}</a></td>
                            <td class="py-2 small">{{ $p->contract?->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $p->concept }}</td>
                            <td class="py-2 text-end fw-semibold pe-4">{{ money($p->amount) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-exclamation-triangle fs-2 opacity-25 d-block mb-2"></i>Sin penalizaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $penalties->links() }}</div>
</div>
@endsection
