@extends('layouts.app')
@section('title', 'Servicios de Taller')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gear me-2 text-danger"></i>Servicios</h1>
            <p class="text-muted mb-0 small">Catálogo de servicios ofrecidos en el taller.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo servicio
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Servicio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Descripción</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Tiempo est.</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Precio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="fw-semibold small">{{ $service->name }}</div>
                            </td>
                            <td class="py-2 small text-muted">
                                {{ $service->description ? \Illuminate\Support\Str::limit($service->description, 60) : '—' }}
                            </td>
                            <td class="py-2 small text-muted">
                                {{ $service->estimated_time ?: '—' }}
                            </td>
                            <td class="py-2 text-end fw-semibold small">
                                {{ money($service->price, null, 2) }}
                            </td>
                            <td class="py-2">
                                @if($service->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.7rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.7rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('services.edit', $service) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('services.destroy', $service) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el servicio «{{ addslashes($service->name) }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-gear fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay servicios registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('services.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Crear primer servicio
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

    <div class="mt-4 d-flex justify-content-center">{{ $services->links() }}</div>

</div>
@endsection
