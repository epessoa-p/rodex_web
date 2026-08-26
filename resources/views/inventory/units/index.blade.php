@extends('layouts.app')
@section('title', 'Unidades de medida')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-rulers me-2 text-danger"></i>Unidades de medida</h1>
            <p class="text-muted mb-0 small">Define las unidades disponibles al crear o importar productos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-units.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('product-units.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva unidad
        </a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>@foreach($errors->all() as $e){{ $e }}@endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead>
                    <tr class="table-light border-bottom">
                        <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Unidad</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Productos</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr class="border-bottom border-light">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;">
                                    <i class="bi bi-rulers" style="font-size:.72rem;"></i>
                                </div>
                                <span class="fw-semibold">{{ $unit->name }}</span>
                            </div>
                        </td>
                        <td class="py-2 text-center">
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $counts[$unit->name] ?? 0 }}</span>
                        </td>
                        <td class="py-2">
                            @if($unit->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activa</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-2 text-end pe-3">
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-units.edit', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-units.edit', $unit) }}" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-units.delete', auth()->user()->getCurrentCompany()))
                            <form action="{{ route('product-units.destroy', $unit) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la unidad «{{ addslashes($unit->name) }}»? Los productos conservan su unidad.')">
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
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-rulers fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No hay unidades registradas.</p>
                            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-units.create', auth()->user()->getCurrentCompany()))
                            <a href="{{ route('product-units.create') }}" class="btn btn-sm btn-primary mt-3">
                                <i class="bi bi-plus-lg me-1"></i>Crear primera unidad
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $units->links() }}</div>
</div>
@endsection
