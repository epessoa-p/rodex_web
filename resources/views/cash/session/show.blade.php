@extends('layouts.app')

@section('title', 'Sesión de caja')

@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <h1 class="mb-0"><i class="bi bi-receipt"></i> {{ $session->cashRegister?->name }}</h1>
                @if($session->isOpen())
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-6 px-3 py-2">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle;"></i> Sesión abierta
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-6 px-3 py-2">
                        Sesión cerrada
                    </span>
                @endif
            </div>
            <p class="text-muted mb-0">
                <i class="bi bi-geo-alt"></i> {{ $session->cashRegister?->branch?->name }}
                &nbsp;·&nbsp;
                <i class="bi bi-person"></i> {{ $session->openedBy?->name }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3"></i> {{ $session->opened_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($session->isOpen())
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion">
                    <i class="bi bi-lock"></i> Cerrar caja
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoMovimiento">
                    <i class="bi bi-plus-lg"></i> Agregar movimiento
                </button>
            @endif
            <a href="{{ url()->previous() }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
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

    {{-- KPIs --}}
    @php
        $income   = $session->totalIncome();
        $expense  = $session->totalExpense();
        $expected = $session->expectedBalance();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-wallet2"></i> Monto inicial</div>
                    <div class="fs-4 fw-bold">{{ number_format($session->opening_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-success border-3">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-arrow-down-circle text-success"></i> Total ingresos</div>
                    <div class="fs-4 fw-bold text-success">+{{ number_format($income, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-3">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-arrow-up-circle text-danger"></i> Total egresos</div>
                    <div class="fs-4 fw-bold text-danger">-{{ number_format($expense, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-3">
                <div class="card-body">
                    <div class="text-muted small mb-1"><i class="bi bi-calculator text-primary"></i> Saldo esperado</div>
                    <div class="fs-4 fw-bold text-primary">{{ number_format($expected, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(!$session->isOpen())
    {{-- Cierre info --}}
    @php $diff = (float)$session->closing_amount - $expected; @endphp
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-lock"></i> Resumen de cierre</h6>
            @if($canAdjustCash && abs($diff) >= 0.01)
            <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold btn-adjust-cash"
                    data-session="{{ $session->id }}"
                    data-expected="{{ number_format($expected, 2, '.', '') }}"
                    data-counted="{{ number_format($session->closing_amount, 2, '.', '') }}"
                    data-difference="{{ number_format($diff, 2, '.', '') }}"
                    data-register="{{ $session->cashRegister?->name }}">
                <i class="bi bi-sliders me-1"></i>Resolver diferencia
            </button>
            @endif
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Cerrado por</div>
                    <div class="fw-semibold">{{ $session->closedBy?->name ?: '—' }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Fecha de cierre</div>
                    <div class="fw-semibold">{{ $session->closed_at?->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Monto contado</div>
                    <div class="fw-semibold">{{ number_format($session->closing_amount, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Diferencia</div>
                    <div class="fw-semibold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                        @if($diff > 0) <small>(sobrante)</small>
                        @elseif($diff < 0) <small>(faltante)</small>
                        @else <small>(exacto)</small>
                        @endif
                    </div>
                </div>
                @if($session->notes)
                <div class="col-12">
                    <div class="text-muted small">Notas de cierre</div>
                    <div>{{ $session->notes }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Movimientos --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-list-ul"></i> Movimientos</h6>
            <small class="text-muted">{{ $movements->count() }} registros</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th class="text-end">Monto</th>
                            <th>Descripción</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        <tr>
                            <td>
                                <div>{{ $movement->movement_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $movement->movement_date->format('H:i') }}</small>
                            </td>
                            <td>{{ $movement->category_label }}</td>
                            <td>
                                @if($movement->type === 'income')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-arrow-down-short"></i> Ingreso
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="bi bi-arrow-up-short"></i> Egreso
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-semibold {{ $movement->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $movement->type === 'income' ? '+' : '-' }}{{ number_format($movement->amount, 2) }}
                            </td>
                            <td class="text-muted">{{ $movement->description ?: '—' }}</td>
                            <td>{{ $movement->user?->name ?: '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                No hay movimientos en esta sesión.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@if($session->isOpen())
{{-- Modal: Agregar movimiento --}}
<div class="modal fade" id="modalNuevoMovimiento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-plus-circle text-primary"></i> Nuevo movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('cash.movement.store', $session) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" id="movType" required>
                                <option value="income">Ingreso</option>
                                <option value="expense">Egreso</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" id="movCategory" required>
                                @foreach(\App\Models\CashMovement::CATEGORIES as $key => $cat)
                                    <option value="{{ $key }}" data-movtype="{{ $cat['type'] }}">{{ $cat['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Medio de pago</label>
                            <select name="method" class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia bancaria</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="qr">QR</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="movement_date" class="form-control" required
                                   value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="500"
                                      placeholder="Detalle del movimiento (opcional)..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Cerrar caja --}}
<div class="modal fade" id="modalCerrarSesion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-lock text-danger"></i> Cerrar caja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('cash.session.close', $session) }}" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- Resumen --}}
                    <div class="rounded-3 bg-light p-3 mb-3">
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="text-muted small">Monto inicial</div>
                                <div class="fw-bold">{{ number_format($session->opening_amount, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Ingresos</div>
                                <div class="fw-bold text-success">+{{ number_format($income, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted small">Egresos</div>
                                <div class="fw-bold text-danger">-{{ number_format($expense, 2) }}</div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="text-center">
                            <div class="text-muted small">Saldo esperado en caja</div>
                            <div class="fs-5 fw-bold text-primary">{{ number_format($expected, 2) }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Monto físico contado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="closing_amount" class="form-control form-control-lg"
                                       step="0.01" min="0" required placeholder="0.00"
                                       id="closingAmountInput">
                            </div>
                            <div id="diffPreview" class="mt-2 text-center fw-semibold d-none"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notas de cierre</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Observaciones (opcional)..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-lock"></i> Confirmar cierre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Filtrar categorías por tipo
    document.getElementById('movType')?.addEventListener('change', function () {
        const type = this.value;
        document.querySelectorAll('#movCategory option').forEach(opt => {
            opt.hidden = opt.dataset.movtype !== type;
        });
        const firstVisible = document.querySelector('#movCategory option:not([hidden])');
        if (firstVisible) document.getElementById('movCategory').value = firstVisible.value;
    });
    document.getElementById('movType')?.dispatchEvent(new Event('change'));

    // Diferencia en tiempo real al cerrar
    const expectedBalance = {{ $expected }};
    document.getElementById('closingAmountInput')?.addEventListener('input', function () {
        const diff = parseFloat(this.value || 0) - expectedBalance;
        const el = document.getElementById('diffPreview');
        el.classList.remove('d-none', 'text-success', 'text-danger', 'text-muted');
        if (isNaN(diff)) { el.classList.add('d-none'); return; }
        el.textContent = 'Diferencia: ' + (diff >= 0 ? '+' : '') + diff.toFixed(2) + (diff > 0 ? ' (sobrante)' : diff < 0 ? ' (faltante)' : ' (exacto)');
        el.classList.add(diff > 0 ? 'text-success' : diff < 0 ? 'text-danger' : 'text-muted');
    });
</script>
@endpush
@endif

{{-- Modal compartido "Resolver diferencia" (para sesiones cerradas con diferencia) --}}
@include('cash.session._adjust-modal')

@endsection
