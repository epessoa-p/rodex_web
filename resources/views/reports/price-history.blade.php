@extends('layouts.app')
@section('title', 'Cambios de precio')
@section('page')
@php
    $f = $filters;
    $money = fn ($v) => money((float) $v);
    $fieldLabel = ['price' => 'Precio', 'cost' => 'Costo'];
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clock-history me-2 text-primary"></i>Cambios de precio</h1>
            <p class="text-muted mb-0 small">Quién cambió precios y costos, cuándo y desde dónde.
                @if($product)<br>Producto: <strong>{{ $product->name }}</strong>@endif
            </p>
        </div>
    </div>

    @unless($ready)
        <div class="alert alert-warning border-0">
            <i class="bi bi-database-exclamation me-1"></i>
            El historial todavía no está activo: falta correr el script <code>20261012_price_history.sql</code> en la base de datos.
        </div>
    @else

    {{-- Filtros --}}
    <form method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 row g-2 align-items-end">
            @if(!empty($f['product_id']))<input type="hidden" name="product_id" value="{{ $f['product_id'] }}">@endif
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="phFrom">Desde</label>
                <input type="date" id="phFrom" name="from" value="{{ $f['from'] }}" class="form-control form-control-sm" data-no-uppercase>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="phTo">Hasta</label>
                <input type="date" id="phTo" name="to" value="{{ $f['to'] }}" class="form-control form-control-sm" data-no-uppercase>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0" for="phUser">Persona</label>
                <select id="phUser" name="user_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($users as $u)<option value="{{ $u->id }}" @selected(($f['user_id'] ?? null) == $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-0" for="phField">Qué cambió</label>
                <select id="phField" name="field" class="form-select form-select-sm" data-no-search>
                    <option value="">Precio y costo</option>
                    <option value="price" @selected(($f['field'] ?? '') === 'price')>Solo precio</option>
                    <option value="cost" @selected(($f['field'] ?? '') === 'cost')>Solo costo</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                @if($product)<a href="{{ route('price-history.index', array_diff_key($f, ['product_id' => 1])) }}" class="btn btn-sm btn-light border">Todos los productos</a>@endif
            </div>
        </div>
    </form>

    {{-- Alertas --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-semibold {{ $alerts->isNotEmpty() ? 'text-danger' : '' }}">
                <i class="bi bi-exclamation-triangle me-2"></i>Bajó el precio, vendió y lo volvió a subir
            </h6>
            <span class="text-muted small">Vuelta al precio anterior dentro de {{ $window }} días, con ventas al precio bajo</span>
        </div>
        <div class="card-body p-0">
            @forelse($alerts as $a)
                @php $d = $a['drop']; $r = $a['restore']; @endphp
                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-semibold">{{ $a['product']?->name ?? 'Producto eliminado' }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-arrow-down-circle text-danger me-1"></i>{{ $d->created_at->format('d/m H:i') }}: {{ $money($d->old_value) }} → <strong class="text-danger">{{ $money($d->new_value) }}</strong> por <strong>{{ $d->user?->name ?? 'sin usuario' }}</strong>
                                · <i class="bi bi-arrow-up-circle text-success ms-1 me-1"></i>{{ $r->created_at->format('d/m H:i') }}: → {{ $money($r->new_value) }} por <strong>{{ $r->user?->name ?? 'sin usuario' }}</strong>
                                · {{ $a['minutes'] < 120 ? $a['minutes'] . ' min después' : round($a['minutes'] / 60) . ' h después' }}
                            </div>
                            @if($a['same_user'])<span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1">La misma persona bajó y subió el precio</span>@endif
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Se dejó de cobrar</div>
                            <div class="fw-bold text-danger fs-5">{{ $money($a['loss']) }}</div>
                        </div>
                    </div>
                    <div class="small mt-2">
                        Ventas al precio bajo ({{ \App\Services\Inventory\KardexMath::qty($a['units']) }} u.):
                        @foreach($a['sales'] as $s)
                            <a href="{{ route('sales.show', $s->id) }}" class="text-decoration-none me-2">{{ $s->code }}</a><span class="text-muted me-3">{{ \Illuminate\Support\Carbon::parse($s->sale_date)->format('d/m H:i') }} · {{ \App\Services\Inventory\KardexMath::qty((float) $s->quantity) }} × {{ $money($s->unit_price) }} · vendió {{ $s->seller ?? '—' }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center text-muted small py-4"><i class="bi bi-shield-check me-1 text-success"></i>Sin casos en el período.</div>
            @endforelse
        </div>
    </div>

    {{-- Historial --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2 text-muted"></i>Historial</h6>
            <span class="text-muted small">{{ $changes->total() }} {{ $changes->total() === 1 ? 'cambio' : 'cambios' }}</span>
        </div>
        <div class="card-body p-0">
            @if($changes->isEmpty())
                <div class="text-center text-muted small py-4">Sin cambios de precio ni costo en el período.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th class="ps-4">Fecha</th><th>Producto</th><th>Qué</th>
                        <th class="text-end">Antes</th><th class="text-end">Después</th><th class="text-end">Cambio</th>
                        <th>Quién</th><th class="pe-4">Desde dónde</th>
                    </tr></thead>
                    <tbody>
                    @foreach($changes as $c)
                        @php $pct = $c->percent; @endphp
                        <tr>
                            <td class="ps-4 small text-nowrap">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="small">
                                @if($c->product)<a href="{{ route('price-history.index', array_merge($f, ['product_id' => $c->product_id])) }}" class="text-decoration-none" title="Ver solo este producto">{{ $c->product->name }}</a>@else<span class="text-muted">Eliminado</span>@endif
                            </td>
                            <td class="small">{{ $fieldLabel[$c->field] ?? $c->field }}</td>
                            <td class="text-end small text-muted">{{ $c->old_value !== null ? $money($c->old_value) : '—' }}</td>
                            <td class="text-end small fw-semibold">{{ $money($c->new_value) }}</td>
                            <td class="text-end small {{ $pct === null ? 'text-muted' : ($pct < 0 ? 'text-danger' : 'text-success') }}">
                                {{ $pct === null ? '—' : ($pct > 0 ? '+' : '') . number_format($pct, 1) . '%' }}
                            </td>
                            <td class="small">{{ $c->user?->name ?? 'Sistema' }}</td>
                            <td class="small text-muted pe-4">{{ $c->source_label }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
    @if($changes instanceof \Illuminate\Contracts\Pagination\Paginator && $changes->hasPages())
        <div class="mt-3 d-flex justify-content-center">{{ $changes->links() }}</div>
    @endif
    @endunless
</div>
@endsection
