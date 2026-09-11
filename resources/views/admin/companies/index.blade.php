@extends('layouts.app')

@section('title', 'Empresas - Sistema de Préstamos')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-building"></i> Empresas</h1>
        <a href="{{ route('companies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Empresa
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @php $usageSvc = app(\App\Services\Admin\CompanyUsageService::class); @endphp
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Plan</th>
                        <th>Última actividad</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                        @php
                            $last = $lastActivity[$company->id] ?? null;
                            $st   = $usageSvc->status($last);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('companies.show', $company) }}" class="fw-semibold text-decoration-none text-dark">{{ $company->name }}</a>
                                <div class="text-muted small">{{ $company->ruc ?? '' }}{{ $company->ruc && $company->phone ? ' · ' : '' }}{{ $company->phone ?? '' }}</div>
                            </td>
                            <td>
                                @if($company->subscription?->plan)
                                    <div class="small">{{ $company->subscription->plan->name }}</div>
                                    <span class="badge bg-light text-dark border">{{ \App\Models\Subscription::STATUSES[$company->subscription->status] ?? $company->subscription->status }}</span>
                                @else
                                    <span class="text-muted small">Sin suscripción</span>
                                @endif
                            </td>
                            <td>
                                @if($last)
                                    <div class="small" title="{{ $last->format('d/m/Y H:i') }}">{{ $last->diffForHumans() }}</div>
                                    <span class="badge bg-{{ $st['color'] }}-subtle text-{{ $st['color'] }}-emphasis border border-{{ $st['color'] }}-subtle">{{ $st['label'] }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border">Sin actividad</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $company->active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $company->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('companies.show', $company) }}" class="btn btn-sm btn-info" title="Ver uso y detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('companies.destroy', $company) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-center">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
