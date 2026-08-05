@extends('layouts.app')
@section('title', 'Planes de Pago')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-list-check me-2 text-danger"></i>Planes de Pago</h1>
            <p class="text-muted mb-0 small">Configura los planes de cuotas disponibles para ventas a crédito.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('payment-plans.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nuevo plan
        </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Nombre</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">N° cuotas</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Frecuencia (días)</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Interés (%)</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:36px;height:36px;background:rgba(230,57,70,.07);">
                                        <i class="bi bi-list-check text-danger"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">{{ $plan->name }}</div>
                                        @if($plan->interest_rate > 0)
                                        <small class="text-muted">Con interés</small>
                                        @else
                                        <small class="text-success">Sin interés</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-2 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                                    {{ $plan->number_of_installments }}
                                </span>
                            </td>
                            <td class="py-2 text-center small">
                                @php
                                    $freqLabel = match(true) {
                                        $plan->frequency_days == 30 => '30 — mensual',
                                        $plan->frequency_days == 15 => '15 — quincenal',
                                        $plan->frequency_days == 7  => '7 — semanal',
                                        default => $plan->frequency_days . ' días',
                                    };
                                @endphp
                                <span class="text-muted">{{ $freqLabel }}</span>
                            </td>
                            <td class="py-2 text-center small">
                                @if($plan->interest_rate > 0)
                                <span class="fw-semibold text-danger">{{ number_format($plan->interest_rate, 2) }}%</span>
                                @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle">0%</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if($plan->active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.edit', auth()->user()->getCurrentCompany()))
                                <a href="{{ route('payment-plans.edit', $plan) }}" class="btn btn-sm btn-light border me-1" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endif
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.delete', auth()->user()->getCurrentCompany()))
                                <form action="{{ route('payment-plans.destroy', $plan) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar plan «{{ addslashes($plan->name) }}»?')">
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                                     style="width:64px;height:64px;">
                                    <i class="bi bi-list-check fs-2 opacity-25"></i>
                                </div>
                                <p class="fw-semibold mb-1">Sin planes de pago</p>
                                <small class="text-muted">Crea el primer plan para usarlo en ventas a crédito.</small>
                                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.create', auth()->user()->getCurrentCompany()))
                                <div class="mt-3">
                                    <a href="{{ route('payment-plans.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1"></i>Crear primer plan
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $plans->links() }}</div>

</div>
@endsection
