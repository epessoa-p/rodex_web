@extends('layouts.app')
@section('title', 'Mecánicos')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-person-gear me-2 text-danger"></i>Mecánicos</h1>
            <p class="text-muted mb-0 small">Gestión del personal técnico del taller.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('mechanics.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo mecánico
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Mecánico</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Teléfono</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Comisión</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;letter-spacing:.04em;">OT</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mechanics as $mechanic)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="fw-semibold small lh-sm">{{ $mechanic->name }}</div>
                                @if($mechanic->specialty)
                                <small class="text-muted">{{ $mechanic->specialty }}</small>
                                @endif
                            </td>
                            <td class="py-2 small text-muted">
                                @if($mechanic->phone)
                                <a href="tel:{{ $mechanic->phone }}" class="text-decoration-none text-muted">
                                    <i class="bi bi-telephone me-1"></i>{{ $mechanic->phone }}
                                </a>
                                @else
                                —
                                @endif
                            </td>
                            <td class="py-2 small text-muted">
                                {{ $mechanic->commission_rate ? number_format($mechanic->commission_rate, 1) . '%' : '—' }}
                            </td>
                            <td class="py-2 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.7rem;">
                                    {{ $mechanic->work_orders_count }}
                                </span>
                            </td>
                            <td class="py-2">
                                @if($mechanic->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.7rem;">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.7rem;">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('mechanics.edit', $mechanic) }}"
                                   class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('mechanics.destroy', $mechanic) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar al mecánico «{{ addslashes($mechanic->name) }}»?')">
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
                                <i class="bi bi-person-gear fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay mecánicos registrados.</p>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.create', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('mechanics.create') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Registrar primer mecánico
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

    <div class="mt-4 d-flex justify-content-center">{{ $mechanics->links() }}</div>

</div>
@endsection
