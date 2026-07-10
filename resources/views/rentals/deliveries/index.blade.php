@extends('layouts.app')
@section('title', 'Alquileres · Entregas')
@section('page')
<div class="container-fluid">

    <div class="mb-4">
        <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-arrow-up me-2 text-danger"></i>Entregas pendientes</h1>
        <p class="text-muted mb-0 small">Reservas y contratos listos para entregar la moto al cliente.</p>
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
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Inicio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $c)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2"><a href="{{ route('rentals.show', $c) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">{{ $c->code }}</a></td>
                            <td class="py-2 small">{{ $c->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $c->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $c->start_date?->format('d/m/Y') }}</td>
                            <td class="py-2"><span class="badge bg-{{ $c->status_color }}-subtle text-{{ $c->status_color }} border border-{{ $c->status_color }}-subtle" style="font-size:.68rem;">{{ $c->status_label }}</span></td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.deliver', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('rentals.deliver', $c) }}" class="btn btn-sm btn-primary" title="Entregar"><i class="bi bi-box-arrow-up me-1"></i>Entregar</a>
                                @else
                                <a href="{{ route('rentals.show', $c) }}" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-box fs-2 opacity-25 d-block mb-2"></i>No hay entregas pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $contracts->links() }}</div>
</div>
@endsection
