@extends('layouts.app')
@section('title', 'Modelos de Motos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bicycle me-2 text-danger"></i>Modelos de Motos</h1>
            <p class="text-muted mb-0 small">Catálogo de modelos por marca con datos técnicos y precio sugerido.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('moto-models.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo modelo
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Modelo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Marca</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Cilindrada</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Año</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Precio sug.</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Unidades</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($models as $model)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="fw-semibold">{{ $model->name }}</div>
                                <div class="text-muted" style="font-size:.75rem;">{{ $model->display_name }}</div>
                            </td>
                            <td class="py-2">
                                @if($model->brand)
                                <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">
                                    {{ $model->brand->name }}
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-center small text-muted">
                                {{ $model->engine_cc ? $model->engine_cc . ' cc' : '—' }}
                            </td>
                            <td class="py-2 text-center small text-muted">
                                {{ $model->year ?? '—' }}
                            </td>
                            <td class="py-2 text-end fw-semibold">
                                {{ $model->suggested_price ? '$' . number_format($model->suggested_price, 2) : '—' }}
                            </td>
                            <td class="py-2 text-center">
                                <span class="badge bg-light text-dark border" style="font-size:.75rem;">
                                    {{ $model->units_count }}
                                </span>
                            </td>
                            <td class="py-2">
                                @if($model->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.72rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.72rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-models.edit', $model) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('moto-models.destroy', $model) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar modelo «{{ addslashes($model->name) }}»?')">
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
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-bicycle fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay modelos registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('moto-models.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer modelo
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

    <div class="mt-4 d-flex justify-content-center">{{ $models->links() }}</div>

</div>
@endsection
