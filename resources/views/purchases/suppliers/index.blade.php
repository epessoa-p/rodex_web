@extends('layouts.app')
@section('title', 'Proveedores')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-truck me-2 text-danger"></i>Proveedores</h1>
            <p class="text-muted mb-0 small">Gestión de proveedores y datos de contacto.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo proveedor
        </a>
        @endif
    </div>

    {{-- Búsqueda --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control"
                               value="{{ request('q') }}"
                               placeholder="Nombre, NIT, contacto...">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-funnel me-1"></i>Filtrar
                    </button>
                    @if(request('q'))
                    <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light border ms-1">
                        <i class="bi bi-x me-1"></i>Limpiar
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Proveedor</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">NIT</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Contacto</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Teléfono</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center fw-bold text-muted flex-shrink-0"
                                         style="width:38px;height:38px;font-size:.85rem">
                                        {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('suppliers.show', $supplier) }}"
                                           class="text-decoration-none text-dark fw-semibold">
                                            {{ $supplier->name }}
                                        </a>
                                        @if($supplier->email)
                                        <div class="text-muted" style="font-size:.8rem">{{ $supplier->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="text-muted small">{{ $supplier->nit ?: '—' }}</span>
                            </td>
                            <td class="py-3 small">{{ $supplier->contact_name ?: '—' }}</td>
                            <td class="py-3 small">
                                @if($supplier->phone)
                                <a href="tel:{{ $supplier->phone }}" class="text-decoration-none text-muted">
                                    <i class="bi bi-telephone me-1"></i>{{ $supplier->phone }}
                                </a>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3">
                                @if($supplier->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-3 text-end pe-4">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-light border me-1" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar proveedor «{{ addslashes($supplier->name) }}»?')">
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
                                <i class="bi bi-truck fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay proveedores registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer proveedor
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

    <div class="mt-4 d-flex justify-content-center">{{ $suppliers->links() }}</div>

</div>
@endsection
