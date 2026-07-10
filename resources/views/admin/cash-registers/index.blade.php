@extends('layouts.app')

@section('title', 'Cajas')

@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1"><i class="bi bi-safe2"></i> Cajas</h1>
            <p class="text-muted mb-0">Administra las cajas registradoras de cada sucursal.</p>
        </div>
        <a href="{{ route('cash-registers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nueva caja
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Caja</th>
                            <th>Sucursal</th>
                            <th>Cajero asignado</th>
                            <th>Estado</th>
                            <th>Sesión</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registers as $register)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $register->name }}</div>
                                @if($register->description)
                                    <small class="text-muted">{{ Str::limit($register->description, 60) }}</small>
                                @endif
                            </td>
                            <td>{{ $register->branch?->name ?: '—' }}</td>
                            <td>
                                @if($register->assignedPersonal)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;font-size:.75rem;">
                                            {{ strtoupper(substr($register->assignedPersonal->full_name, 0, 1)) }}
                                        </div>
                                        {{ $register->assignedPersonal->full_name }}
                                    </div>
                                @else
                                    <span class="text-muted">Sin asignar</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $register->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                    {{ $register->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td>
                                @if($register->activeSession)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-circle-fill" style="font-size:.5rem;"></i> Abierta
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">Cerrada</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('cash-registers.show', $register) }}" class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('cash-registers.edit', $register) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('cash-registers.destroy', $register) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar la caja «{{ addslashes($register->name) }}»?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-safe2 fs-1 d-block mb-2 opacity-25"></i>
                                No hay cajas registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $registers->links() }}
    </div>

</div>
@endsection
