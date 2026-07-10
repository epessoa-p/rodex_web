@extends('layouts.app')
@section('title', 'Almacenes')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-building-add me-2 text-danger"></i>Almacenes</h1>
            <p class="text-muted mb-0 small">Controla ubicaciones físicas y movimientos de inventario.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo almacén
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Almacén</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Código</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Sucursal</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Empresa</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $warehouse)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">
                                        <i class="bi bi-building" style="font-size:.85rem"></i>
                                    </div>
                                    <div>
                                        <a href="{{ route('warehouses.show', $warehouse) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $warehouse->name }}
                                        </a>
                                        @if($warehouse->location)
                                        <div class="text-muted" style="font-size:.78rem">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $warehouse->location }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <code class="bg-light px-2 py-1 rounded border small">{{ $warehouse->code }}</code>
                            </td>
                            <td class="py-3 small text-muted">{{ $warehouse->primaryBranch?->name ?: '—' }}</td>
                            <td class="py-3 small text-muted">{{ $warehouse->company?->name ?: '—' }}</td>
                            <td class="py-3">
                                @if($warehouse->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2">
                                    <i class="bi bi-check-circle me-1"></i>Activo
                                </span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2">
                                    <i class="bi bi-pause-circle me-1"></i>Inactivo
                                </span>
                                @endif
                            </td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-sm btn-light border me-1" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el almacén «{{ addslashes($warehouse->name) }}»?')">
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-building-add fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay almacenes registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('warehouses.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer almacén
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

    <div class="mt-4 d-flex justify-content-center">{{ $warehouses->links() }}</div>
</div>
@endsection
