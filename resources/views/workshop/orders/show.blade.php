@extends('layouts.app')
@section('title', 'OT: ' . $order->code)
@section('page')

@php
    $editable = !in_array($order->status, ['entregada', 'anulada']);
    $canEdit  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.edit', auth()->user()->getCurrentCompany());
    $canDeliver = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.deliver', auth()->user()->getCurrentCompany());
@endphp

<div class="container-fluid">

    {{-- Print header (hidden on screen) --}}
    <div class="print-header d-none">
        <h3 class="fw-bold mb-1">{{ $order->company?->name ?? config('brand.name') }} — Taller</h3>
        <p class="mb-0 text-muted small">Orden de Trabajo</p>
    </div>

    {{-- ── HEADER CARD ──────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden no-print-shadow">
        <div style="height:6px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4">{{ $order->code }}</h1>
                        <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle"
                              id="orderStatusBadge">
                            {{ $order->status_label }}
                        </span>
                        <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle">
                            {{ $order->payment_status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        <span><i class="bi bi-person me-1"></i>{{ $order->client?->full_name ?? '—' }}</span>
                        <span><i class="bi bi-bicycle me-1"></i>{{ $order->vehicle?->display_name ?? '—' }}</span>
                        @if($order->mechanic)
                        <span><i class="bi bi-person-gear me-1"></i>{{ $order->mechanic->name }}</span>
                        @endif
                        @if($order->branch)
                        <span><i class="bi bi-building me-1"></i>{{ $order->branch->name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($order->reception_date)->format('d/m/Y') }}</span>
                        @if($order->createdBy)
                        <span><i class="bi bi-person-badge me-1"></i>{{ $order->createdBy->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">

                    {{-- Status change buttons --}}
                    @if($editable && $canEdit)
                        @if($order->status === 'recibida')
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="diagnosticada">
                            <button class="btn btn-light border btn-sm">
                                <i class="bi bi-search me-1"></i>Diagnosticada
                            </button>
                        </form>
                        @endif
                        @if(in_array($order->status, ['recibida', 'diagnosticada']))
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="en_proceso">
                            <button class="btn btn-light border btn-sm">
                                <i class="bi bi-wrench me-1"></i>En proceso
                            </button>
                        </form>
                        @endif
                        @if(in_array($order->status, ['recibida', 'diagnosticada', 'en_proceso']))
                        <form action="{{ route('workshop.orders.status', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="terminada">
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-check-circle me-1"></i>Terminada
                            </button>
                        </form>
                        @endif
                    @endif

                    {{-- Deliver button --}}
                    @if($order->status === 'terminada' && $canDeliver)
                    <a href="{{ route('workshop.deliveries.create', $order) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Entregar / Cobrar
                    </a>
                    @endif

                    {{-- Register payment if delivered with balance --}}
                    @if($order->status === 'entregada' && $order->balance > 0 && $canDeliver)
                    <button type="button" class="btn btn-primary btn-sm no-print"
                            data-bs-toggle="modal" data-bs-target="#woPayModal">
                        <i class="bi bi-cash-coin me-1"></i>Registrar pago
                    </button>
                    @endif

                    {{-- Edit / Cancel --}}
                    @if($editable && $canEdit)
                    <a href="{{ route('workshop.orders.edit', $order) }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    <form action="{{ route('workshop.orders.cancel', $order) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('¿Anular la OT {{ addslashes($order->code) }}? Esta acción no se puede deshacer.')">
                        @csrf
                        <button class="btn btn-sm btn-light border text-danger">
                            <i class="bi bi-x-circle me-1"></i>Anular
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('workshop.orders.print', $order) }}" target="_blank" class="btn btn-light border btn-sm">
                        <i class="bi bi-printer me-1"></i>Imprimir
                    </a>
                    <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- ── LEFT COLUMN ──────────────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Recepción --}}
            <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #14b8a6 !important;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2" style="color:#14b8a6;"></i>Recepción</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-muted small mb-1">Kilometraje</div>
                            <div class="fw-semibold small">{{ $order->mileage ? number_format($order->mileage) . ' km' : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small mb-1">Combustible</div>
                            <div class="fw-semibold small">{{ $order->fuel_level ?: '—' }}</div>
                        </div>
                        @if($order->reported_issue)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Falla reportada</div>
                            <div class="small">{{ $order->reported_issue }}</div>
                        </div>
                        @endif
                        @if($order->received_items)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Objetos / accesorios recibidos</div>
                            <div class="small">{{ $order->received_items }}</div>
                        </div>
                        @endif
                        @if($order->notes)
                        <div class="col-12">
                            <div class="text-muted small mb-1">Notas</div>
                            <div class="small text-muted">{{ $order->notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Fotos --}}
            <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #0ea5e9 !important;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-images me-2" style="color:#0ea5e9;"></i>Fotos</h6>
                    <span class="badge bg-light text-muted border">{{ $order->photos->count() }}</span>
                </div>
                <div class="card-body p-4">
                    @if($order->photos->isEmpty())
                        <div class="text-muted small mb-0">Sin fotos.</div>
                    @else
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($order->photos as $photo)
                            <div style="width:104px;">
                                <div class="position-relative" style="width:104px;height:104px;">
                                    <img src="{{ $photo->url }}" alt="{{ $photo->file_name }}"
                                         class="rounded border w-100 h-100 wo-photo-thumb"
                                         style="object-fit:cover;cursor:zoom-in;"
                                         data-bs-toggle="modal" data-bs-target="#photoModal"
                                         data-src="{{ $photo->url }}"
                                         data-caption="{{ $photo->caption }}"
                                         data-update="{{ route('workshop.orders.photos.update', [$order, $photo]) }}">
                                    @if($canEdit && $editable)
                                    <form action="{{ route('workshop.orders.photos.remove', [$order, $photo]) }}" method="POST"
                                          class="position-absolute top-0 end-0 m-1"
                                          onsubmit="return confirm('¿Eliminar esta foto?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                style="width:24px;height:24px;" title="Eliminar">
                                            <i class="bi bi-x" style="font-size:1rem;"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                                <div class="small text-truncate mt-1 {{ $photo->caption ? 'text-body' : 'text-muted fst-italic' }}"
                                     title="{{ $photo->caption }}">
                                    {{ $photo->caption ?: 'Sin comentario' }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif

                    @if($canEdit && $editable)
                    <form action="{{ route('workshop.orders.photos.add', $order) }}" method="POST"
                          enctype="multipart/form-data" class="mt-3 d-flex gap-2 align-items-center flex-wrap">
                        @csrf
                        <input type="file" name="photos[]" accept="image/*" multiple required
                               class="form-control form-control-sm" style="max-width:320px;">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-upload me-1"></i>Subir fotos
                        </button>
                    </form>
                    @error('photos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    @endif
                </div>
            </div>

            {{-- Diagnóstico --}}
            <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #8b5cf6 !important;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-search me-2" style="color:#8b5cf6;"></i>Diagnóstico</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small mb-3 {{ $order->diagnosis ? '' : 'text-muted' }}" id="diagnosisText">
                        @if($order->diagnosis){{ $order->diagnosis }}@else<em>Sin diagnóstico registrado.</em>@endif
                    </p>
                    @if($editable && $canEdit)
                    <form action="{{ route('workshop.orders.diagnosis', $order) }}" method="POST" class="no-print wo-diagnosis-form" id="diagnosisForm" data-no-spinner>
                        @csrf
                        <div class="d-flex gap-2 align-items-start">
                            <textarea name="diagnosis" rows="2"
                                      class="form-control form-control-sm flex-grow-1"
                                      placeholder="Ingresa el diagnóstico técnico...">{{ old('diagnosis', $order->diagnosis) }}</textarea>
                            <button type="submit" class="btn btn-primary btn-sm flex-shrink-0">
                                <i class="bi bi-check-lg me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Servicios (AJAX, parcial reutilizable) --}}
            <div id="servicesCard">
                @include('workshop.orders._services')
            </div>

            {{-- Repuestos (AJAX, parcial reutilizable) --}}
            <div id="partsCard">
                @include('workshop.orders._parts')
            </div>

        </div>

        {{-- ── RIGHT COLUMN ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="sticky-top" style="top:20px">

                {{-- Datos del cliente --}}
                @if($order->client)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2 text-muted"></i>Datos del cliente</h6>
                    </div>
                    <div class="card-body p-4">
                        <dl class="row g-2 small mb-3">
                            <dt class="col-5 text-muted fw-normal">Nombre</dt>
                            <dd class="col-7 mb-0 fw-semibold">{{ $order->client->full_name }}</dd>
                            @if($order->client->id_number)
                            <dt class="col-5 text-muted fw-normal">Documento</dt>
                            <dd class="col-7 mb-0">{{ $order->client->id_number }}</dd>
                            @endif
                            @if($order->client->phone)
                            <dt class="col-5 text-muted fw-normal">Teléfono</dt>
                            <dd class="col-7 mb-0">
                                <a href="tel:{{ $order->client->phone }}" class="text-decoration-none">{{ $order->client->phone }}</a>
                            </dd>
                            @endif
                            @if($order->client->email)
                            <dt class="col-5 text-muted fw-normal">Email</dt>
                            <dd class="col-7 mb-0 text-truncate">
                                <a href="mailto:{{ $order->client->email }}" class="text-decoration-none">{{ $order->client->email }}</a>
                            </dd>
                            @endif
                        </dl>
                        <a href="{{ route('clients.show', $order->client) }}" class="btn btn-light border btn-sm w-100 no-print">
                            <i class="bi bi-person-lines-fill me-1"></i>Ver ficha del cliente
                        </a>
                    </div>
                </div>
                @endif

                {{-- Vehículo --}}
                @if($order->vehicle)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-bicycle me-2 text-muted"></i>Vehículo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-1">{{ $order->vehicle->display_name }}</div>
                        @if($order->vehicle->plate)
                        <div class="text-muted small"><i class="bi bi-tag me-1"></i>{{ $order->vehicle->plate }}</div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Mecánico asignado --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2 text-muted"></i>Mecánico asignado</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-3">
                            {{ $order->mechanic?->name ?? 'Sin asignar' }}
                        </div>
                        @if($editable && $canEdit)
                        <form action="{{ route('workshop.orders.mechanic', $order) }}" method="POST" class="no-print">
                            @csrf
                            <select name="mechanic_id" class="form-select form-select-sm mb-2">
                                <option value="">Sin asignar</option>
                                @foreach($mechanics as $m)
                                <option value="{{ $m->id }}" {{ $order->mechanic_id == $m->id ? 'selected' : '' }}>
                                    {{ $m->name }}{{ $m->specialty ? ' — ' . $m->specialty : '' }}
                                </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-light border btn-sm w-100">
                                <i class="bi bi-check-lg me-1"></i>Asignar
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Totales (se actualiza por AJAX) --}}
                <div id="totalsCard">
                    @include('workshop.orders._totals')
                </div>

                {{-- Cuotas --}}
                @if($order->installments->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-check me-2 text-muted"></i>Cuotas</h6>
                        <span class="badge bg-light text-muted border">{{ $order->installments->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="font-size:.83rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2 fw-semibold text-muted" style="font-size:.7rem;">#</th>
                                        <th class="py-2 fw-semibold text-muted" style="font-size:.7rem;">Vence</th>
                                        <th class="py-2 fw-semibold text-muted text-end" style="font-size:.7rem;">Monto</th>
                                        <th class="py-2 fw-semibold text-muted pe-3" style="font-size:.7rem;">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->installments as $inst)
                                    <tr class="border-bottom border-light">
                                        <td class="ps-3 py-2 small">{{ $inst->number }}</td>
                                        <td class="py-2 small {{ $inst->is_overdue ? 'text-danger fw-semibold' : '' }}">
                                            {{ $inst->due_date->format('d/m/Y') }}
                                            @if($inst->is_overdue)
                                            <span class="d-block badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.6rem;">VENCIDA</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-end small fw-semibold">
                                            {{ money($inst->amount, null, 2) }}
                                            @if($inst->balance > 0)
                                            <div class="text-danger" style="font-size:.7rem;">Saldo: {{ money($inst->balance, null, 2) }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2 pe-3">
                                            <span class="badge bg-{{ $inst->status_color }}-subtle text-{{ $inst->status_color }} border border-{{ $inst->status_color }}-subtle" style="font-size:.65rem;">
                                                {{ $inst->status_label }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Pagos --}}
                @if($order->payments->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Pagos</h6>
                        <span class="badge bg-light text-muted border">{{ $order->payments->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @foreach($order->payments as $payment)
                        <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom border-light">
                            <div>
                                <div class="fw-semibold small">{{ money($payment->amount, null, 2) }}</div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    {{ $payment->payment_date->format('d/m/Y') }}
                                    @if($payment->method) &middot; {{ ucfirst($payment->method) }} @endif
                                    @if($payment->reference) &middot; {{ $payment->reference }} @endif
                                </div>
                            </div>
                            <div class="text-muted small">{{ $payment->user?->name ?: '—' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>

</div>

{{-- ── PAYMENT MODAL ─────────────────────────────────────────────────── --}}
@if($order->status === 'entregada' && $order->balance > 0 && $canDeliver)
<div class="modal fade" id="woPayModal" tabindex="-1" aria-labelledby="woPayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="woPayModalLabel">
                    <i class="bi bi-cash-coin me-2 text-muted"></i>Registrar pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('workshop.orders.payment', $order) }}" method="POST">
                @csrf
                <div class="modal-body p-4">

                    <div class="rounded-3 border p-3 mb-4 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $order->code }}</div>
                                <div class="text-muted small">{{ $order->client?->full_name ?? '—' }}</div>
                            </div>
                            <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle">
                                {{ $order->payment_status_label }}
                            </span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4">
                                <div class="text-muted">Total</div>
                                <div class="fw-semibold">{{ money($order->total, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Pagado</div>
                                <div class="fw-semibold text-success">{{ money($order->paid_amount, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Saldo</div>
                                <div class="fw-bold text-danger fs-6">{{ money($order->balance, null, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_amount">
                                Monto <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="wo_amount" name="amount"
                                       step="0.01" min="0.01" max="{{ $order->balance }}"
                                       class="form-control"
                                       value="{{ number_format($order->balance, 2, '.', '') }}"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_date">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="wo_date" name="payment_date"
                                   class="form-control"
                                   value="{{ now()->format('Y-m-d') }}"
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_method">Método de pago</label>
                            <select name="method" id="wo_method" class="form-select">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="wo_reference">Referencia</label>
                            <input type="text" name="reference" id="wo_reference"
                                   class="form-control"
                                   placeholder="N° transacción, etc.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="wo_notes">Notas</label>
                        <textarea name="notes" id="wo_notes" class="form-control" rows="2"
                                  placeholder="Observaciones del cobro..."></textarea>
                    </div>

                    <div class="alert alert-info border-0 py-2 mb-0" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Ingresa a tu caja abierta.
                    </div>

                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Registrar pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Lightbox de fotos --}}
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="text-end mb-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <img id="photoModalImg" src="" alt="Foto de la OT" class="img-fluid rounded shadow" style="max-height:70vh;object-fit:contain;">
            @if($canEdit && $editable)
            <form id="captionForm" method="POST" class="bg-white rounded p-2 mt-2 d-flex gap-2 align-items-end">
                @csrf @method('PUT')
                <div class="flex-grow-1">
                    <label class="form-label small fw-semibold mb-1">Comentario de la foto</label>
                    <textarea name="caption" id="captionInput" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Ej: Cambiar esta pieza gastada por una nueva"></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
            </form>
            @else
            <div id="captionView" class="bg-white rounded p-2 mt-2 small text-muted d-none"></div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print, .no-print * { display: none !important; }
    .app-sidebar, .app-topbar { display: none !important; }
    .app-main { padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .print-header { display: block !important; margin-bottom: 1rem; }
    .no-print-shadow { box-shadow: none !important; }
    .sticky-top { position: static !important; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const CSRF = '{{ csrf_token() }}';

    // ── Lightbox de fotos ────────────────────────────────────────────
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function (ev) {
            const t = ev.relatedTarget;
            const src = t?.getAttribute('data-src');
            if (src) document.getElementById('photoModalImg').src = src;
            const caption = t?.getAttribute('data-caption') || '';
            const update = t?.getAttribute('data-update') || '';
            const form = document.getElementById('captionForm');
            if (form) {
                form.action = update;
                document.getElementById('captionInput').value = caption;
            }
            const view = document.getElementById('captionView');
            if (view) {
                view.textContent = caption;
                view.classList.toggle('d-none', !caption);
            }
        });
    }

    // ── Toast ────────────────────────────────────────────────────────
    function toast(msg, ok) {
        let cont = document.getElementById('woToastCont');
        if (!cont) {
            cont = document.createElement('div');
            cont.id = 'woToastCont';
            cont.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(cont);
        }
        const color = ok === false ? '#e11d48' : '#16a34a';
        const icon  = ok === false ? 'bi-x-octagon' : 'bi-check-circle';
        const el = document.createElement('div');
        el.style.cssText = 'background:#fff;border-left:4px solid ' + color + ';box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:340px;display:flex;align-items:center;gap:.5rem;';
        el.innerHTML = '<i class="bi ' + icon + '" style="color:' + color + ';"></i><span>' + msg + '</span>';
        cont.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
    }

    // ── Autollenado de precio en el form de servicios (datalist) ──────
    function svcPriceMap() {
        const map = {};
        document.querySelectorAll('#svcOptions option').forEach(o => {
            if (o.value) map[o.value.trim().toLowerCase()] = o.dataset.price || '';
        });
        return map;
    }
    function bindServiceAutofill() {
        const name  = document.querySelector('.wo-svc-name');
        const price = document.querySelector('.wo-svc-price');
        if (!name || !price) return;
        const map = svcPriceMap();
        name.addEventListener('input', function () {
            const p = map[this.value.trim().toLowerCase()];
            if (p && !price.value) price.value = parseFloat(p).toFixed(2);
        });
    }
    // ── Autollenado de precio en el form de repuestos ─────────────────
    // El select usa select2, que dispara 'change' vía jQuery: hay que
    // escucharlo con jQuery (un addEventListener nativo no lo captura).
    function bindPartAutofill() {
        const sel   = document.querySelector('.wo-part-select');
        const price = document.querySelector('.wo-part-price');
        if (!sel || !price) return;
        const handler = function () {
            const opt = sel.options[sel.selectedIndex];
            const p = opt ? (opt.dataset.price || '') : '';
            if (p !== '') price.value = parseFloat(p).toFixed(2);
        };
        if (window.jQuery) jQuery(sel).off('change.woPart').on('change.woPart', handler);
        else sel.addEventListener('change', handler);
    }

    // ── Autollenado de costo/precio en el modal de compra directa ─────
    function bindPurchaseAutofill() {
        const name  = document.querySelector('.wo-purchase-name');
        const cost  = document.querySelector('.wo-purchase-cost');
        const price = document.querySelector('.wo-purchase-price');
        if (!name) return;
        const opts = {};
        document.querySelectorAll('#wpProducts option').forEach(o => {
            if (o.value) opts[o.value.trim().toLowerCase()] = { price: o.dataset.price || '', cost: o.dataset.cost || '' };
        });
        name.addEventListener('input', function () {
            const d = opts[this.value.trim().toLowerCase()];
            if (!d) return;
            if (price && d.price && !price.value) price.value = parseFloat(d.price).toFixed(2);
            if (cost  && d.cost  && !cost.value)  cost.value  = parseFloat(d.cost).toFixed(2);
        });
    }

    function bindAll() { bindServiceAutofill(); bindPartAutofill(); bindPurchaseAutofill(); }

    // ── Envío AJAX de altas/bajas de servicios y repuestos ────────────
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.wo-ajax-form');
        if (!form) return;
        e.preventDefault();

        const btn = form.querySelector('[type="submit"]');
        const original = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.ok) {
                const msg = d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo completar la acción.');
                toast(msg, false);
                if (btn) { btn.disabled = false; btn.innerHTML = original; }
                return;
            }
            // Si el envío vino de un modal (p. ej. compra directa), cerrarlo antes
            // de reemplazar el DOM que lo contiene.
            const modalEl = form.closest('.modal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            document.getElementById('servicesCard').innerHTML = d.services;
            document.getElementById('partsCard').innerHTML    = d.parts;
            document.getElementById('totalsCard').innerHTML   = d.totals;
            // Limpieza por si el modal se removió antes de terminar su transición.
            if (modalEl) {
                document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
            bindAll();
            if (window.initSelect2) window.initSelect2(document.getElementById('partsCard'));
            toast(d.message || 'Listo.');
        })
        .catch(() => {
            toast('Error de conexión.', false);
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
        });
    });

    // ── Diagnóstico por AJAX (sin recargar) ───────────────────────────
    document.addEventListener('submit', function (e) {
        const form = e.target.closest('.wo-diagnosis-form');
        if (!form) return;
        e.preventDefault();

        const btn = form.querySelector('[type="submit"]');
        const original = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
            if (!ok || !d.ok) {
                toast(d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar.'), false);
                return;
            }
            const txt = document.getElementById('diagnosisText');
            if (txt) {
                if (d.diagnosis && d.diagnosis.trim()) {
                    txt.textContent = d.diagnosis;
                    txt.classList.remove('text-muted');
                } else {
                    txt.innerHTML = '<em>Sin diagnóstico registrado.</em>';
                    txt.classList.add('text-muted');
                }
            }
            const badge = document.getElementById('orderStatusBadge');
            if (badge && d.status_label) {
                badge.textContent = d.status_label;
                badge.className = 'badge bg-' + d.status_color + '-subtle text-' + d.status_color + ' border border-' + d.status_color + '-subtle';
            }
            toast(d.message || 'Diagnóstico guardado.');
        })
        .catch(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
            toast('Error de conexión.', false);
        });
    });

    document.addEventListener('DOMContentLoaded', bindAll);
})();
</script>
@endpush

@endsection
