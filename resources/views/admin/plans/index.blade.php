@extends('layouts.app')

@section('title', 'Planes')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-box-seam"></i> Planes</h1>
            <p class="text-muted mb-0 small">Configura los planes de la plataforma: módulos y límites de uso.</p>
        </div>
        <a href="{{ route('plans.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo plan</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plan</th>
                            <th>Precio</th>
                            <th class="text-center">Usuarios</th>
                            <th class="text-center">Sucursales</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Módulos</th>
                            <th class="text-center">Empresas</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                    <div class="text-muted small">{{ $plan->description }}</div>
                                </td>
                                <td>{{ config('inventory.currency') }} {{ number_format($plan->price, 2) }}<span class="text-muted small"> / {{ $plan->billing_period_label }}</span></td>
                                <td class="text-center">{{ $plan->max_users ?? '∞' }}</td>
                                <td class="text-center">{{ $plan->max_branches ?? '∞' }}</td>
                                <td class="text-center">{{ $plan->max_products ?? '∞' }}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border">{{ count($plan->features ?? []) }}</span></td>
                                <td class="text-center">{{ $plan->subscriptions_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $plan->active ? 'bg-success' : 'bg-secondary' }}">{{ $plan->active ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('plans.destroy', $plan) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar el plan «{{ $plan->name }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" {{ $plan->subscriptions_count > 0 ? 'disabled title=Hay empresas suscritas' : '' }}><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-5 text-muted">No hay planes configurados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
