@extends('layouts.app')

@section('title', 'Almacenes')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="mb-1"><i class="bi bi-building-add"></i> Almacenes</h1><p class="text-muted mb-0">Controla ubicaciones físicas y movimientos de inventario.</p></div><a href="{{ route('warehouses.create') }}" class="btn btn-primary">Nuevo almacén</a></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Almacén</th><th>Código</th><th>Sucursal principal</th><th>Empresa</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>@forelse($warehouses as $warehouse)<tr><td>{{ $warehouse->name }}</td><td>{{ $warehouse->code }}</td><td>{{ $warehouse->primaryBranch?->name ?: 'No asignada' }}</td><td>{{ $warehouse->company?->name ?: '-' }}</td><td><span class="badge {{ $warehouse->active ? 'bg-success' : 'bg-secondary' }}">{{ $warehouse->active ? 'Activo' : 'Inactivo' }}</span></td><td class="text-end"><a href="{{ route('warehouses.show', $warehouse) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a><a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar almacén?')"><i class="bi bi-trash"></i></button></form></td></tr>@empty<tr><td colspan="6" class="text-center py-5 text-muted">No hay almacenes registrados.</td></tr>@endforelse</tbody></table></div></div></div>
    <div class="mt-4 d-flex justify-content-center">{{ $warehouses->links() }}</div>
</div>
@endsection