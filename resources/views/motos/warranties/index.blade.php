@extends('layouts.app')
@section('title', 'Garantías')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-shield-check me-2 text-danger"></i>Garantías</h1>
            <p class="text-muted mb-0 small">Garantías registradas para motos vendidas.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.manage', auth()->user()->getCurrentCompany()))
        <a href="{{ route('warranties.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva garantía
        </a>
        @endif
    </div>

    {{-- Filtros de estado --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small fw-semibold me-1">Estado:</span>
                <a href="{{ route('warranties.index') }}"
                   class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                    Todos
                </a>
                <a href="{{ route('warranties.index', ['status' => 'vigente']) }}"
                   class="btn btn-sm {{ request('status') === 'vigente' ? 'btn-success' : 'btn-light border' }}">
                    Vigentes
                </a>
                <a href="{{ route('warranties.index', ['status' => 'vencida']) }}"
                   class="btn btn-sm {{ request('status') === 'vencida' ? 'btn-warning' : 'btn-light border' }}">
                    Vencidas
                </a>
                <a href="{{ route('warranties.index', ['status' => 'anulada']) }}"
                   class="btn btn-sm {{ request('status') === 'anulada' ? 'btn-secondary' : 'btn-light border' }}">
                    Anuladas
                </a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Inicio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Meses</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Vence</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Ver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warranties as $warranty)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('warranties.show', $warranty) }}"
                                   class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.82rem;">
                                    {{ $warranty->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $warranty->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $warranty->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $warranty->start_date->format('d/m/Y') }}</td>
                            <td class="py-2 text-center small text-muted">{{ $warranty->months }}</td>
                            <td class="py-2 small {{ $warranty->is_active ? '' : 'text-muted' }}">
                                {{ $warranty->end_date->format('d/m/Y') }}
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $warranty->status_color }}-subtle text-{{ $warranty->status_color }} border border-{{ $warranty->status_color }}-subtle" style="font-size:.68rem;">
                                    {{ $warranty->status_label }}
                                </span>
                            </td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('warranties.show', $warranty) }}" class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-shield-check fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">
                                    @if(request('status'))
                                        No hay garantías con estado «{{ request('status') }}».
                                        <a href="{{ route('warranties.index') }}" class="ms-1 small">Ver todas</a>
                                    @else
                                        No hay garantías registradas.
                                    @endif
                                </p>
                                @if(!request('status') && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.manage', auth()->user()->getCurrentCompany())))
                                <a href="{{ route('warranties.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Registrar primera garantía
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $warranties->withQueryString()->links() }}</div>

</div>
@endsection
