@extends('layouts.app')
@section('title', 'Fidelización · Canjes')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bag-check me-2 text-danger"></i>Canjes</h1>
            <p class="text-muted mb-0 small">Historial de recompensas canjeadas.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty.redeem', auth()->user()->getCurrentCompany()))
        <a href="{{ route('loyalty.redemptions.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Registrar canje</a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <select name="client_id" class="form-select form-select-sm" style="max-width:260px;" onchange="this.form.submit()">
                    <option value="">Todos los clientes</option>
                    @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                    @endforeach
                </select>
                @if(request('client_id'))<a href="{{ route('loyalty.redemptions.index') }}" class="btn btn-sm btn-light border">Limpiar</a>@endif
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
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Recompensa</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Puntos</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase pe-4" style="font-size:.72rem;">Registró</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redemptions as $r)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 small text-muted">{{ $r->redeemed_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-2 small fw-semibold">{{ $r->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $r->reward?->name ?? '—' }}</td>
                            <td class="py-2 text-end fw-semibold text-danger">−{{ number_format($r->points_spent, 0) }}</td>
                            <td class="py-2 pe-4 small text-muted">{{ $r->user?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-bag-x fs-2 d-block mb-2 opacity-25"></i>Sin canjes registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $redemptions->withQueryString()->links() }}</div>
</div>
@endsection
