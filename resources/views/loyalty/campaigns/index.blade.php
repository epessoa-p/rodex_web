@extends('layouts.app')
@section('title', 'Fidelización · Campañas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-megaphone me-2 text-danger"></i>Campañas</h1>
            <p class="text-muted mb-0 small">Multiplicadores temporales de puntos (ej. x2 fin de semana).</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-campaigns.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('loyalty.campaigns.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva campaña</a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Campaña</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;">Multiplicador</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Vigencia</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $c)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 small fw-semibold">{{ $c->name }}</td>
                            <td class="py-2 text-center"><span class="badge bg-primary-subtle text-primary border border-primary-subtle">x{{ rtrim(rtrim(number_format($c->multiplier, 2), '0'), '.') }}</span></td>
                            <td class="py-2 small text-muted">{{ $c->starts_at->format('d/m/Y') }} — {{ $c->ends_at->format('d/m/Y') }}</td>
                            <td class="py-2">
                                @if($c->is_running)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Activa ahora</span>
                                @elseif(!$c->active)
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactiva</span>
                                @elseif($c->ends_at->isPast())
                                <span class="badge bg-light text-muted border">Finalizada</span>
                                @else
                                <span class="badge bg-info-subtle text-info border border-info-subtle">Programada</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-campaigns.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('loyalty.campaigns.edit', $c) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-campaigns.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('loyalty.campaigns.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta campaña?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-megaphone fs-2 d-block mb-2 opacity-25"></i>Sin campañas. Crea la primera.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $campaigns->links() }}</div>
</div>
@endsection
