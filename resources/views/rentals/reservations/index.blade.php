@extends('layouts.app')
@section('title', 'Alquileres · Reservas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bookmark-plus me-2 text-danger"></i>Reservas</h1>
            <p class="text-muted mb-0 small">Reservas de motos pendientes de confirmar o entregar.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('rentals.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva reserva</a>
        @endif
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
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $c)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2"><a href="{{ route('rentals.show', $c) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">{{ $c->code }}</a></td>
                            <td class="py-2 small">{{ $c->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $c->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $c->start_date?->format('d/m/Y') }} → {{ $c->end_date?->format('d/m/Y') }} <span class="text-secondary">({{ $c->days }}d)</span></td>
                            <td class="py-2 text-end fw-semibold">{{ money($c->total) }}</td>
                            <td class="py-2 text-end pe-4">
                                <div class="btn-group">
                                    <a href="{{ route('rentals.show', $c) }}" class="btn btn-sm btn-light border" title="Ver"><i class="bi bi-eye"></i></a>
                                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.deliver', auth()->user()->getCurrentCompany()))
                                    <a href="{{ route('rentals.deliver', $c) }}" class="btn btn-sm btn-primary" title="Entregar"><i class="bi bi-box-arrow-up"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-bookmark fs-2 opacity-25 d-block mb-2"></i>Sin reservas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $contracts->links() }}</div>
</div>
@endsection
