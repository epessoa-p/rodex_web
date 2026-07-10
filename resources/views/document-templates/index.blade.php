@extends('layouts.app')

@section('title', 'Plantillas de Documentos')

@section('page')
<div class="container-fluid" style="max-width: 1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-file-earmark-ruled"></i> Plantillas de Documentos</h1>
            <p class="text-muted mb-0">Gestiona tus plantillas de contratos, boletas, recibos y más.</p>
        </div>
        <a href="{{ route('document-templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva plantilla
        </a>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('document-templates.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Nombre o descripción…" value="{{ $q }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tipo</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        @foreach(\App\Models\DocumentTemplate::TYPES as $value => $label)
                            <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Estado</label>
                    <select name="active" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1" {{ $active === '1' ? 'selected' : '' }}>Activos</option>
                        <option value="0" {{ $active === '0' ? 'selected' : '' }}>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
                    <a href="{{ route('document-templates.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $tpl)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('document-templates.show', $tpl) }}" class="fw-semibold text-decoration-none">
                                        {{ $tpl->name }}
                                    </a>
                                    @if(!$tpl->company_id)
                                        <span class="badge bg-dark ms-1 small">Global</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tpl->type_badge }}">{{ $tpl->type_label }}</span>
                                </td>
                                <td class="text-muted small" style="max-width: 300px;">
                                    {{ Str::limit($tpl->description, 80) ?: '—' }}
                                </td>
                                <td class="text-center">
                                    @if($tpl->active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('document-templates.show', $tpl) }}" class="btn btn-sm btn-outline-secondary" title="Ver / Vista previa">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('document-templates.edit', $tpl) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('document-templates.destroy', $tpl) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar la plantilla «{{ addslashes($tpl->name) }}»?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-file-earmark-ruled fs-2 d-block mb-2"></i>
                                    No hay plantillas que mostrar.
                                    <a href="{{ route('document-templates.create') }}" class="d-block mt-2">Crear primera plantilla</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
            <div class="card-footer bg-white">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
