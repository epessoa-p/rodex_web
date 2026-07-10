@extends('layouts.app')
@section('title', 'Marcas de Motos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-tag me-2 text-danger"></i>Marcas de Motos</h1>
            <p class="text-muted mb-0 small">Catálogo de marcas para el inventario de motos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('moto-brands.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva marca
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Marca</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">País</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Modelos</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center fw-bold text-muted flex-shrink-0"
                                         style="width:34px;height:34px;font-size:.8rem;">
                                        {{ strtoupper(substr($brand->name, 0, 2)) }}
                                    </div>
                                    <span class="fw-semibold">{{ $brand->name }}</span>
                                </div>
                            </td>
                            <td class="py-2 small text-muted">{{ $brand->country ?: '—' }}</td>
                            <td class="py-2 text-center">
                                <span class="badge bg-light text-dark border" style="font-size:.75rem;">
                                    {{ $brand->models_count }}
                                </span>
                            </td>
                            <td class="py-2">
                                @if($brand->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.72rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-brands.edit', $brand) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('moto-brands.destroy', $brand) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar marca «{{ addslashes($brand->name) }}»?')">
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
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-tag fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay marcas registradas.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-brands.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primera marca
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

    <div class="mt-4 d-flex justify-content-center">{{ $brands->links() }}</div>

</div>
@endsection
