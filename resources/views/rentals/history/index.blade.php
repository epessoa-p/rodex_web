@extends('layouts.app')
@section('title', 'Alquileres · Historial')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clock-history me-2 text-danger"></i>Historial</h1>
            <p class="text-muted mb-0 small">Contratos devueltos, cerrados y anulados.</p>
        </div>
        <form method="GET" action="{{ route('rentals.history') }}" class="d-flex gap-2" data-no-spinner>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Buscar código o cliente..." style="min-width:220px;">
            <button class="btn btn-sm btn-dark"><i class="bi bi-search"></i></button>
        </form>
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
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $c)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2"><a href="{{ route('rentals.show', $c) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">{{ $c->code }}</a></td>
                            <td class="py-2 small">{{ $c->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $c->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $c->start_date?->format('d/m/Y') }} → {{ $c->end_date?->format('d/m/Y') }}</td>
                            <td class="py-2 text-end fw-semibold">{{ money($c->total) }}</td>
                            <td class="py-2"><span class="badge bg-{{ $c->status_color }}-subtle text-{{ $c->status_color }} border border-{{ $c->status_color }}-subtle" style="font-size:.68rem;">{{ $c->status_label }}</span></td>
                            <td class="py-2 text-end pe-4"><a href="{{ route('rentals.show', $c) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-clock-history fs-2 opacity-25 d-block mb-2"></i>Sin historial.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $contracts->links() }}</div>
</div>
@endsection
