@extends('layouts.app')
@section('title', 'Categorías de producto')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-tags me-2 text-danger"></i>Categorías</h1>
            <p class="text-muted mb-0 small">Clasifica los productos de tu catálogo por categoría.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('product-categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva categoría
        </a>
        @endif
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        @foreach($errors->all() as $e){{ $e }}@endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead>
                    <tr class="table-light border-bottom">
                        <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Nombre</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Descripción</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Productos</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr class="border-bottom border-light">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;">
                                    <i class="bi bi-tag" style="font-size:.72rem;"></i>
                                </div>
                                <span class="fw-semibold">{{ $category->name }}</span>
                            </div>
                        </td>
                        <td class="py-2 text-muted" style="max-width:280px;">{{ Str::limit($category->description, 70) ?: '—' }}</td>
                        <td class="py-2 text-center">
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $category->products()->count() }}</span>
                        </td>
                        <td class="py-2">
                            @if($category->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activa</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-2 text-end pe-3">
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.edit', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-categories.edit', $category) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.delete', auth()->user()->getCurrentCompany()))
                            <form action="{{ route('product-categories.destroy', $category) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la categoría «{{ addslashes($category->name) }}»?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No hay categorías registradas.</p>
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.create', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-categories.create') }}" class="btn btn-sm btn-primary mt-3">
                                <i class="bi bi-plus-lg me-1"></i>Crear primera categoría
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $categories->links() }}</div>
</div>
@endsection
