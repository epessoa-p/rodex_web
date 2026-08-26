@extends('layouts.app')
@section('title', 'Vehículos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-bicycle me-2 text-danger"></i>Vehículos</h1>
            <p class="text-muted mb-0 small">Registro de vehículos de clientes.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo vehículo
        </a>
        @endif
    </div>

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" class="d-flex align-items-center gap-2">
                <i class="bi bi-search text-muted"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none"
                       placeholder="Buscar por marca, modelo, placa o cliente..."
                       value="{{ request('q') }}" style="max-width:400px;">
                @if(request('q'))
                <a href="{{ route('vehicles.index') }}" class="btn btn-sm btn-light border">Limpiar</a>
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
                            <th class="ps-4 py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Vehículo</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Cilindrada</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Año</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Color</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted small text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="fw-semibold small lh-sm">
                                    {{ $vehicle->brand }} {{ $vehicle->model }}
                                </div>
                                @if($vehicle->plate)
                                <span class="badge bg-light text-dark border" style="font-size:.68rem;letter-spacing:.05em;">
                                    {{ $vehicle->plate }}
                                </span>
                                @endif
                            </td>
                            <td class="py-2 small">{{ $vehicle->client?->full_name ?? '—' }}</td>
                            <td class="py-2 text-center small text-muted">
                                {{ $vehicle->engine_cc ? $vehicle->engine_cc . ' cc' : '—' }}
                            </td>
                            <td class="py-2 text-center small text-muted">{{ $vehicle->year ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $vehicle->color ?? '—' }}</td>
                            <td class="py-2">
                                <span class="badge {{ $vehicle->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}" style="font-size:.7rem;">
                                    {{ $vehicle->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.view', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('vehicles.show', $vehicle) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('vehicles.edit', $vehicle) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el vehículo {{ addslashes($vehicle->brand . ' ' . $vehicle->model) }}?')">
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
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-bicycle fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay vehículos registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('vehicles.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Registrar primer vehículo
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

    <div class="mt-4 d-flex justify-content-center">{{ $vehicles->withQueryString()->links() }}</div>

</div>
@endsection
