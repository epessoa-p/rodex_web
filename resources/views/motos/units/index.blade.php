@extends('layouts.app')
@section('title', 'Inventario de Motos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-box-seam me-2 text-danger"></i>Inventario de Motos</h1>
            <p class="text-muted mb-0 small">Gestión de unidades en stock, reservadas y vendidas.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('moto-units.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva unidad
        </a>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">

                {{-- Status pills --}}
                <div class="d-flex flex-column gap-1">
                    <span class="text-muted small fw-semibold">Estado</span>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="{{ route('moto-units.index', array_merge(request()->except('status'), [])) }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                            Todos
                        </a>
                        @foreach(['disponible' => 'Disponible', 'reservada' => 'Reservada', 'vendida' => 'Vendida', 'entregada' => 'Entregada'] as $val => $label)
                        <a href="{{ route('moto-units.index', array_merge(request()->except('status'), ['status' => $val])) }}"
                           class="btn btn-sm {{ request('status') === $val ? 'btn-dark' : 'btn-light border' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Modelo --}}
                <div style="min-width:180px;">
                    <label class="form-label small fw-semibold mb-1">Modelo</label>
                    <select name="moto_model_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los modelos</option>
                        @foreach($models as $m)
                        <option value="{{ $m->id }}" {{ request('moto_model_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->display_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Búsqueda --}}
                <div style="min-width:220px;">
                    <label class="form-label small fw-semibold mb-1">Buscar</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control"
                               value="{{ request('q') }}"
                               placeholder="Chasis, motor, color...">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </div>

                @if(request()->hasAny(['status', 'moto_model_id', 'q']))
                <div class="align-self-end">
                    <a href="{{ route('moto-units.index') }}" class="btn btn-sm btn-light border">
                        <i class="bi bi-x me-1"></i>Limpiar
                    </a>
                </div>
                @endif

            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Modelo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Chasis</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Motor</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Color</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Año</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Precio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Sucursal</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($units as $unit)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="fw-semibold">{{ $unit->model?->display_name ?? '—' }}</div>
                            </td>
                            <td class="py-2">
                                <span class="text-muted small font-monospace">{{ $unit->chassis_number }}</span>
                            </td>
                            <td class="py-2">
                                <span class="text-muted small font-monospace">{{ $unit->engine_number ?: '—' }}</span>
                            </td>
                            <td class="py-2 small">{{ $unit->color ?: '—' }}</td>
                            <td class="py-2 text-center small text-muted">{{ $unit->year ?? '—' }}</td>
                            <td class="py-2 text-end fw-semibold">{{ money($unit->price, null, 2) }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $unit->status_color }}-subtle text-{{ $unit->status_color }} border border-{{ $unit->status_color }}-subtle" style="font-size:.72rem;">
                                    {{ $unit->status_label }}
                                </span>
                            </td>
                            <td class="py-2 small text-muted">{{ $unit->branch?->name ?? '—' }}</td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-units.show', $unit) }}" class="btn btn-sm btn-light border me-1" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-units.edit', $unit) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('moto-units.destroy', $unit) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar unidad «{{ addslashes($unit->chassis_number) }}»?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light border text-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">
                                    @if(request()->hasAny(['status', 'moto_model_id', 'q']))
                                        No hay unidades con los filtros seleccionados.
                                        <a href="{{ route('moto-units.index') }}" class="ms-1 small">Limpiar filtros</a>
                                    @else
                                        No hay unidades registradas en inventario.
                                    @endif
                                </p>
                                @if(!request()->hasAny(['status', 'moto_model_id', 'q']) && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.create', auth()->user()->getCurrentCompany())))
                                <a href="{{ route('moto-units.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Agregar primera unidad
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

    <div class="mt-4 d-flex justify-content-center">{{ $units->withQueryString()->links() }}</div>

</div>
@endsection
