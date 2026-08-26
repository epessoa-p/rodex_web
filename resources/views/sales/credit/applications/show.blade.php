@extends('layouts.app')
@section('title', 'Solicitud ' . $application->code)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="mb-0 fw-bold fs-4"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>{{ $application->code }}</h1>
                    <span class="badge bg-{{ $application->status_color }}-subtle text-{{ $application->status_color }} border border-{{ $application->status_color }}-subtle fs-6">
                        {{ $application->status_label }}
                    </span>
                </div>
                <p class="text-muted mb-0 small">Solicitud de crédito — {{ $application->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($application->status === 'pendiente')
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.edit', auth()->user()->getCurrentCompany()))
                <a href="{{ route('credit-applications.edit', $application) }}" class="btn btn-light border">
                    <i class="bi bi-pencil me-1"></i>Editar
                </a>
                @endif
            @endif
            <a href="{{ route('credit-applications.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    {{-- Converted banner --}}
    @if($application->status === 'convertida' && $application->convertedSale)
    <div class="alert border-0 shadow-sm d-flex align-items-center gap-3 mb-4"
         style="background:rgba(25,135,84,.07);border-left:4px solid #198754 !important;">
        <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
        <div>
            Esta solicitud fue convertida en la venta
            <a href="{{ route('sales.show', $application->convertedSale) }}" class="fw-bold text-decoration-none text-success">
                {{ $application->convertedSale->code }}
            </a>.
        </div>
    </div>
    @endif

    <div class="row g-4">

        {{-- Left column --}}
        <div class="col-lg-8">

            {{-- Datos solicitud --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la solicitud</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Cliente</div>
                            <div class="fw-semibold">{{ $application->client?->full_name ?? '—' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Monto solicitado</div>
                            <div class="fw-bold fs-5">{{ money($application->requested_amount, null, 2) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Pago inicial</div>
                            <div class="fw-semibold">{{ money($application->down_payment ?? 0, null, 2) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Plan de pago</div>
                            <div class="fw-semibold">{{ $application->paymentPlan?->name ?? '—' }}</div>
                            @if($application->paymentPlan)
                            <small class="text-muted">{{ $application->paymentPlan->number_of_installments }} cuotas c/{{ $application->paymentPlan->frequency_days }}d</small>
                            @endif
                        </div>
                        @if($application->installments_count || $application->frequency_days)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Cuotas (manual)</div>
                            <div class="fw-semibold">{{ $application->installments_count ?? '—' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Frecuencia días (manual)</div>
                            <div class="fw-semibold">{{ $application->frequency_days ?? '—' }}</div>
                        </div>
                        @endif
                        @if($application->notes)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notas</div>
                            <div class="p-3 rounded-3 border bg-light small">{{ $application->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Garante --}}
            @if($application->guarantor_name || $application->guarantor_phone)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person-check me-2 text-muted"></i>Garante</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @if($application->guarantor_name)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Nombre</div>
                            <div class="fw-semibold">{{ $application->guarantor_name }}</div>
                        </div>
                        @endif
                        @if($application->guarantor_phone)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Teléfono</div>
                            <a href="tel:{{ $application->guarantor_phone }}" class="fw-semibold text-decoration-none">
                                <i class="bi bi-telephone me-1 text-muted"></i>{{ $application->guarantor_phone }}
                            </a>
                        </div>
                        @endif
                        @if($application->guarantor_notes)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notas del garante</div>
                            <div class="p-3 rounded-3 border bg-light small">{{ $application->guarantor_notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Evaluación --}}
            @if($application->evaluated_by || $application->evaluation_notes || $application->approved_amount)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2-check me-2 text-muted"></i>Evaluación</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @if($application->evaluatedBy)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Evaluado por</div>
                            <div class="fw-semibold">{{ $application->evaluatedBy->name }}</div>
                        </div>
                        @endif
                        @if($application->approved_amount)
                        <div class="col-sm-6">
                            <div class="text-muted small mb-1">Monto aprobado</div>
                            <div class="fw-bold text-success fs-5">{{ money($application->approved_amount, null, 2) }}</div>
                        </div>
                        @endif
                        @if($application->evaluation_notes)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notas de evaluación</div>
                            <div class="p-3 rounded-3 border bg-light small">{{ $application->evaluation_notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- Right sidebar --}}
        <div class="col-lg-4">

            {{-- Creado por --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Creada por</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:40px;height:40px;font-size:.85rem;font-weight:700;color:#555;">
                            {{ strtoupper(substr($application->createdBy?->name ?? 'S', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold small">{{ $application->createdBy?->name ?? 'Sistema' }}</div>
                            <div class="text-muted small">{{ $application->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions card --}}
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.approve', auth()->user()->getCurrentCompany()))
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2 text-muted"></i>Acciones</h6>
                </div>
                <div class="card-body p-4">
                    @if($application->status === 'pendiente')
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#approveModal">
                            <i class="bi bi-check-circle me-1"></i>Aprobar solicitud
                        </button>
                        <button type="button" class="btn btn-light border text-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i>Rechazar solicitud
                        </button>
                    </div>
                    @elseif($application->status === 'aprobada')
                    <div class="alert border-0 mb-3 py-2 small" style="background:rgba(25,135,84,.07);">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        Monto aprobado: <strong>{{ money($application->approved_amount ?? $application->requested_amount, null, 2) }}</strong>
                    </div>
                    <a href="{{ route('credit-applications.convert', $application) }}"
                       class="btn btn-primary w-100"
                       onclick="return confirm('¿Convertir esta solicitud en una venta a crédito?')">
                        <i class="bi bi-arrow-right-circle me-1"></i>Convertir en venta
                    </a>
                    @elseif($application->status === 'rechazada')
                    <div class="text-center py-2 text-muted small">
                        <i class="bi bi-x-circle-fill text-danger d-block fs-3 mb-1"></i>
                        Solicitud rechazada.<br>No hay acciones disponibles.
                    </div>
                    @elseif($application->status === 'convertida')
                    <div class="text-center py-2 text-muted small">
                        <i class="bi bi-check-circle-fill text-success d-block fs-3 mb-1"></i>
                        Convertida en venta.<br>No hay acciones disponibles.
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>

</div>

{{-- ── APPROVE MODAL ───────────────────────────────────────────── --}}
@if(($application->status === 'pendiente') && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.approve', auth()->user()->getCurrentCompany())))
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="approveModalLabel">
                    <i class="bi bi-check-circle me-2 text-success"></i>Aprobar solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('credit-applications.approve', $application) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="rounded-3 border bg-light p-3 mb-4 small">
                        <div class="fw-semibold mb-1">{{ $application->code }} — {{ $application->client?->full_name }}</div>
                        <div class="text-muted">Monto solicitado: <strong>{{ money($application->requested_amount, null, 2) }}</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="approved_amount">
                            Monto aprobado <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                            <input type="number" id="approved_amount" name="approved_amount"
                                   class="form-control"
                                   value="{{ number_format($application->requested_amount, 2, '.', '') }}"
                                   step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="app_eval_notes">Notas de evaluación</label>
                        <textarea id="app_eval_notes" name="evaluation_notes" rows="3"
                                  class="form-control"
                                  placeholder="Condiciones, observaciones de la aprobación..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i>Aprobar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── REJECT MODAL ────────────────────────────────────────────── --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="rejectModalLabel">
                    <i class="bi bi-x-circle me-2 text-danger"></i>Rechazar solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('credit-applications.reject', $application) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger border-0 py-2 mb-4 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Esta acción rechazará definitivamente la solicitud <strong>{{ $application->code }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="rej_eval_notes">Motivo del rechazo</label>
                        <textarea id="rej_eval_notes" name="evaluation_notes" rows="3"
                                  class="form-control"
                                  placeholder="Explica el motivo del rechazo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-x-lg me-1"></i>Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
