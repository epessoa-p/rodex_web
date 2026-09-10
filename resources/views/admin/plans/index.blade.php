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
                            <th class="px-1" style="width:32px;"><i class="bi bi-arrow-down-up text-muted" title="Orden de presentación"></i></th>
                            <th style="min-width:220px;">Plan</th>
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
                                <td class="text-center px-1 lh-1">
                                    <form action="{{ route('plans.move', [$plan, 'up']) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 text-secondary"
                                                title="Subir" {{ $loop->first ? 'disabled' : '' }}><i class="bi bi-chevron-up"></i></button>
                                    </form>
                                    <form action="{{ route('plans.move', [$plan, 'down']) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 text-secondary"
                                                title="Bajar" {{ $loop->last ? 'disabled' : '' }}><i class="bi bi-chevron-down"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                    @if($plan->description)
                                        {{-- Máx. 2 líneas; el texto completo queda en el tooltip. --}}
                                        <div class="text-muted small" title="{{ $plan->description }}"
                                             style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $plan->description }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ config('inventory.currency') }} {{ number_format($plan->price, 2) }}
                                    <div class="text-muted small">{{ $plan->billing_period_label }}</div>
                                </td>
                                <td class="text-center">{{ $plan->max_users ?? '∞' }}</td>
                                <td class="text-center">{{ $plan->max_branches ?? '∞' }}</td>
                                <td class="text-center">{{ $plan->max_products ?? '∞' }}</td>
                                <td class="text-center"><span class="badge bg-light text-dark border">{{ count($plan->features ?? []) }}</span></td>
                                <td class="text-center">{{ $plan->subscriptions_count }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $plan->active ? 'bg-success' : 'bg-secondary' }}">{{ $plan->active ? 'Activo' : 'Inactivo' }}</span>
                                </td>
                                <td class="text-end text-nowrap">
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
                            <tr><td colspan="10" class="text-center py-5 text-muted">No hay planes configurados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
