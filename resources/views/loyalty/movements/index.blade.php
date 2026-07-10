@extends('layouts.app')
@section('title', 'Fidelización · Movimientos de puntos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-arrow-left-right me-2 text-danger"></i>Movimientos de puntos</h1>
            <p class="text-muted mb-0 small">Historial de acumulaciones, canjes y ajustes.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="client_id" class="form-select form-select-sm" style="max-width:240px;" onchange="this.form.submit()">
                    <option value="">Todos los clientes</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select form-select-sm" style="max-width:180px;" data-no-search onchange="this.form.submit()">
                    <option value="">Todos los tipos</option>
                    <option value="earn"   {{ request('type') === 'earn' ? 'selected' : '' }}>Acumulación</option>
                    <option value="redeem" {{ request('type') === 'redeem' ? 'selected' : '' }}>Canje</option>
                    <option value="adjust" {{ request('type') === 'adjust' ? 'selected' : '' }}>Ajuste</option>
                </select>
                @if(request('client_id') || request('type'))<a href="{{ route('loyalty.movements.index') }}" class="btn btn-sm btn-light border">Limpiar</a>@endif
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Tipo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Detalle</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $m)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 small text-muted">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 small fw-semibold">{{ $m->client?->full_name ?? '—' }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $m->type_color }}-subtle text-{{ $m->type_color }} border border-{{ $m->type_color }}-subtle" style="font-size:.68rem;">{{ $m->type_label }}</span>
                            </td>
                            <td class="py-2 small text-muted">{{ $m->description }}</td>
                            <td class="py-2 text-end fw-bold pe-4 {{ $m->points >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $m->points >= 0 ? '+' : '' }}{{ number_format($m->points, 0) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-arrow-left-right fs-2 d-block mb-2 opacity-25"></i>Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $movements->withQueryString()->links() }}</div>
</div>
@endsection
