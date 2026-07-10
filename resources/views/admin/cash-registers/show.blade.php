@extends('layouts.app')

@section('title', 'Caja: ' . $cashRegister->name)

@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h1 class="mb-0"><i class="bi bi-cash-register"></i> {{ $cashRegister->name }}</h1>
                <span class="badge {{ $cashRegister->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                    {{ $cashRegister->active ? 'Activa' : 'Inactiva' }}
                </span>
            </div>
            <p class="text-muted mb-0">
                <i class="bi bi-geo-alt"></i> {{ $cashRegister->branch?->name ?: '—' }}
                @if($cashRegister->assignedPersonal)
                    &nbsp;·&nbsp; <i class="bi bi-person"></i> {{ $cashRegister->assignedPersonal->full_name }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cash-registers.edit', $cashRegister) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('cash-registers.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    {{-- Descripción --}}
    @if($cashRegister->description)
    <div class="alert alert-light border mb-4">
        <i class="bi bi-info-circle text-muted"></i> {{ $cashRegister->description }}
    </div>
    @endif

    {{-- Historial de sesiones --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historial de sesiones</h6>
            <small class="text-muted">{{ $sessions->total() }} sesiones registradas</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Apertura</th>
                            <th>Cajero</th>
                            <th class="text-end">Monto inicial</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-end">Egresos</th>
                            <th class="text-end">Monto cierre</th>
                            <th class="text-end">Diferencia</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                        @php
                            $income   = $session->totalIncome();
                            $expense  = $session->totalExpense();
                            $expected = $session->expectedBalance();
                            $diff     = $session->closing_amount !== null ? (float)$session->closing_amount - $expected : null;
                        @endphp
                        <tr>
                            <td>
                                <div>{{ $session->opened_at->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $session->opened_at->format('H:i') }}</small>
                            </td>
                            <td>{{ $session->openedBy?->name ?: '—' }}</td>
                            <td class="text-end">{{ number_format($session->opening_amount, 2) }}</td>
                            <td class="text-end text-success">+{{ number_format($income, 2) }}</td>
                            <td class="text-end text-danger">-{{ number_format($expense, 2) }}</td>
                            <td class="text-end">
                                {{ $session->closing_amount !== null ? number_format($session->closing_amount, 2) : '—' }}
                            </td>
                            <td class="text-end">
                                @if($diff !== null)
                                    <span class="{{ $diff >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($session->isOpen())
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-circle-fill" style="font-size:.4rem;vertical-align:middle;"></i> Abierta
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border">Cerrada</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('cash.session.show', $session) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-clock fs-1 d-block mb-2 opacity-25"></i>
                                No hay sesiones registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $sessions->links() }}
    </div>

</div>
@endsection
