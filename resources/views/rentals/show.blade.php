@extends('layouts.app')
@section('title', 'Alquiler · ' . $rental->code)
@section('page')
@php
    $company = auth()->user()->getCurrentCompany();
    $canPay   = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.pay', $company);
    $canEdit  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.edit', $company);
    $canDeliver = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.deliver', $company);
    $canReturn  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.return', $company);
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4">
                <i class="bi bi-file-earmark-text me-2 text-danger"></i>{{ $rental->code }}
                <span class="badge bg-{{ $rental->status_color }}-subtle text-{{ $rental->status_color }} border border-{{ $rental->status_color }}-subtle ms-2 align-middle" style="font-size:.7rem;">{{ $rental->status_label }}</span>
            </h1>
            <p class="text-muted mb-0 small">{{ $rental->client?->full_name }} · {{ $rental->motoUnit?->display_name }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('rentals.reservations') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>

            @if($rental->status === 'reservada' && $canEdit)
            <form action="{{ route('rentals.confirm', $rental) }}" method="POST">@csrf
                <button class="btn btn-dark"><i class="bi bi-check2 me-1"></i>Confirmar contrato</button>
            </form>
            @endif
            @if(in_array($rental->status, ['reservada','contrato']) && $canDeliver)
            <a href="{{ route('rentals.deliver', $rental) }}" class="btn btn-primary"><i class="bi bi-box-arrow-up me-1"></i>Entregar</a>
            @endif
            @if($rental->status === 'entregada' && $canReturn)
            <a href="{{ route('rentals.return', $rental) }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-down me-1"></i>Devolver</a>
            @endif
            @if(in_array($rental->status, ['reservada','contrato']) && $canEdit)
            <form action="{{ route('rentals.cancel', $rental) }}" method="POST" onsubmit="return confirm('¿Anular este contrato?');">@csrf
                <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Anular</button>
            </form>
            @endif
        </div>
    </div>

    @include('rentals.partials.flash')

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Detalle --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Detalle del contrato</h6></div>
                <div class="card-body p-4">
                    <div class="row g-3 small">
                        <div class="col-md-4"><div class="text-muted">Cliente</div><div class="fw-semibold">{{ $rental->client?->full_name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted">Moto</div><div class="fw-semibold">{{ $rental->motoUnit?->display_name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted">Período</div><div class="fw-semibold">{{ $rental->start_date?->format('d/m/Y') }} → {{ $rental->end_date?->format('d/m/Y') }} ({{ $rental->days }}d)</div></div>
                        <div class="col-md-4"><div class="text-muted">Modalidad</div><div class="fw-semibold">{{ $rental->payment_mode_label }}@if($rental->isRenta()) · {{ $rental->billing_period_label }}@endif</div></div>
                        @if($rental->isRenta())
                        <div class="col-md-4"><div class="text-muted">Monto por período</div><div class="fw-semibold">Bs. {{ number_format($rental->period_amount, 2) }}</div></div>
                        <div class="col-md-4"><div class="text-muted">Mora / día</div><div class="fw-semibold">Bs. {{ number_format($rental->late_fee_per_day, 2) }}</div></div>
                        @else
                        <div class="col-md-4"><div class="text-muted">Tarifa / día</div><div class="fw-semibold">Bs. {{ number_format($rental->daily_rate, 2) }}</div></div>
                        @endif
                        <div class="col-md-4"><div class="text-muted">Creado por</div><div class="fw-semibold">{{ $rental->createdBy?->name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted">Caja</div><div class="fw-semibold">{{ $rental->session?->cashRegister?->name ?? '—' }}</div></div>
                        @if($rental->notes)<div class="col-12"><div class="text-muted">Notas</div><div>{{ $rental->notes }}</div></div>@endif
                    </div>
                </div>
            </div>

            {{-- Calendario de renta (cuotas) --}}
            @if($rental->isRenta())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar2-week me-2 text-muted"></i>Calendario de renta</h6>
                    @if($canPay && in_array($rental->status, ['contrato','entregada']) && $rental->balance > 0.01)
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin me-1"></i>Registrar cobro</button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-2" style="width:36px;">#</th>
                                    <th class="py-2">Período</th>
                                    <th class="py-2">Vence</th>
                                    <th class="py-2 text-end">Monto</th>
                                    <th class="py-2 text-end">Pagado</th>
                                    <th class="py-2 text-end">Saldo</th>
                                    <th class="py-2 text-end pe-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rental->installments as $inst)
                                <tr>
                                    <td class="ps-4 py-2">{{ $inst->number }}</td>
                                    <td class="py-2">{{ $inst->period_label ?? '—' }}</td>
                                    <td class="py-2">
                                        @if($inst->is_overdue)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $inst->due_date?->format('d/m/Y') }} · {{ $inst->overdue_days }}d</span>
                                        @else
                                            <span class="text-muted">{{ $inst->due_date?->format('d/m/Y') }}</span>
                                        @endif
                                        @if($inst->accrued_late_fee > 0)<span class="text-danger small ms-1">+mora Bs. {{ number_format($inst->accrued_late_fee, 2) }}</span>@endif
                                    </td>
                                    <td class="py-2 text-end">Bs. {{ number_format($inst->amount, 2) }}</td>
                                    <td class="py-2 text-end text-success">Bs. {{ number_format($inst->paid_amount, 2) }}</td>
                                    <td class="py-2 text-end fw-semibold">Bs. {{ number_format($inst->balance, 2) }}</td>
                                    <td class="py-2 text-end pe-4"><span class="badge bg-{{ $inst->status_color }}-subtle text-{{ $inst->status_color }} border border-{{ $inst->status_color }}-subtle" style="font-size:.66rem;">{{ $inst->status_label }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center py-3 text-muted small">Sin cuotas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            {{-- Inspecciones --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard-check me-2 text-muted"></i>Inspecciones</h6></div>
                <div class="card-body p-4">
                    @php
                        $salida  = $rental->inspections->firstWhere('type', 'salida');
                        $entrada = $rental->inspections->firstWhere('type', 'entrada');
                    @endphp
                    <div class="row g-4 small">
                        @foreach([['salida','Salida','box-arrow-up','warning',$salida,$rental->delivered_at,$rental->delivery_mileage,$rental->delivery_fuel,$rental->delivery_notes],
                                  ['entrada','Entrada','box-arrow-in-down','secondary',$entrada,$rental->returned_at,$rental->return_mileage,$rental->return_fuel,$rental->return_notes]] as $insp)
                        @php [$type,$label,$icon,$color,$rec,$at,$mile,$fuel,$inotes] = $insp; @endphp
                        <div class="col-md-6">
                            <div class="fw-semibold mb-2"><i class="bi bi-{{ $icon }} me-1 text-{{ $color }}"></i>{{ $label }}</div>
                            @if($at)
                                <div class="text-muted">{{ $type === 'salida' ? 'Entregado' : 'Devuelto' }}: {{ $at->format('d/m/Y H:i') }}</div>
                                <div>Km: <strong>{{ $mile ?? '—' }}</strong> · Combustible: <strong>{{ $fuel ?? '—' }}</strong></div>
                                @if($inotes)<div class="text-muted mt-1">{{ $inotes }}</div>@endif
                                @if($rec && $rec->checklist)
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    @foreach($rec->checklist as $key => $item)
                                        @php $cond = is_array($item) ? ($item['condition'] ?? null) : $item; $cc = $cond === 'bien' ? 'success' : ($cond === 'regular' ? 'warning' : 'danger'); @endphp
                                        @if($cond)
                                        <span class="badge bg-{{ $cc }}-subtle text-{{ $cc }} border border-{{ $cc }}-subtle" style="font-size:.66rem;">{{ \App\Models\Rentals\RentalInspection::CHECKLIST_ITEMS[$key] ?? $key }}: {{ \App\Models\Rentals\RentalInspection::CONDITIONS[$cond] ?? $cond }}</span>
                                        @endif
                                    @endforeach
                                </div>
                                @endif
                                @if($rec && $rec->photos->count())
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    @foreach($rec->photos as $ph)
                                    <a href="{{ $ph->url }}" target="_blank" rel="noopener">
                                        <img src="{{ $ph->url }}" alt="foto" class="rounded border" style="width:64px;height:64px;object-fit:cover;">
                                    </a>
                                    @endforeach
                                </div>
                                @endif
                            @else<div class="text-muted">Pendiente.</div>@endif
                        </div>
                        @endforeach
                    </div>
                    @if($rental->workOrder)
                    <div class="alert alert-primary border-0 mt-3 mb-0 small"><i class="bi bi-tools me-2"></i>Orden de Taller generada: <strong>{{ $rental->workOrder->code }}</strong> ({{ $rental->workOrder->status_label }})</div>
                    @endif
                </div>
            </div>

            {{-- Pagos --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-cash-coin me-2 text-muted"></i>Pagos</h6>
                    @if($canPay && in_array($rental->status, ['contrato','entregada','devuelta']) && $rental->balance > 0.01)
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-plus-lg me-1"></i>Registrar pago</button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                        <tbody>
                            @forelse($rental->payments as $p)
                            @php $isRefund = $p->type === 'devolucion_deposito'; @endphp
                            <tr>
                                <td class="ps-4 py-2 text-muted">{{ $p->payment_date?->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $p->type_label }}</td>
                                <td class="py-2 text-muted">{{ ucfirst($p->method) }}</td>
                                <td class="py-2 text-end pe-4 fw-semibold {{ $isRefund ? 'text-danger' : '' }}">{{ $isRefund ? '-' : '' }}Bs. {{ number_format($p->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted small">Sin pagos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Penalizaciones --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-muted"></i>Penalizaciones</h6>
                    @if($canEdit && !in_array($rental->status, ['cerrada','anulada']))
                    <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#penaltyModal"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                        <tbody>
                            @forelse($rental->penalties as $p)
                            <tr>
                                <td class="ps-4 py-2 text-muted">{{ $p->penalty_date?->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $p->concept }}</td>
                                <td class="py-2 text-end pe-4 fw-semibold">Bs. {{ number_format($p->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-3 text-muted small">Sin penalizaciones.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar resumen --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-calculator me-2 text-muted"></i>Resumen financiero</h6></div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Alquiler</span><span class="fw-semibold">Bs. {{ number_format($rental->rental_total, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Penalizaciones</span><span class="fw-semibold">Bs. {{ number_format($rental->penalties_total, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2 fw-bold border-top pt-2"><span>Total</span><span>Bs. {{ number_format($rental->total, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Pagado</span><span class="fw-semibold text-success">Bs. {{ number_format($rental->paid_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-3 small"><span class="text-muted">Saldo</span><span class="fw-semibold text-danger">Bs. {{ number_format($rental->balance, 2) }}</span></div>
                    <div class="text-center">
                        <span class="badge bg-{{ $rental->payment_status_color }}-subtle text-{{ $rental->payment_status_color }} border border-{{ $rental->payment_status_color }}-subtle">{{ $rental->payment_status_label }}</span>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4"><h6 class="mb-0 fw-semibold"><i class="bi bi-safe2 me-2 text-muted"></i>Depósito de garantía</h6></div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Depósito</span><span class="fw-semibold">Bs. {{ number_format($rental->deposit, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Reembolsado</span><span class="fw-semibold">Bs. {{ number_format($rental->deposit_refunded, 2) }}</span></div>
                    <div class="text-center mt-2"><span class="badge bg-light text-dark border">{{ $rental->deposit_status_label }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Pago --}}
@if($canPay)
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" action="{{ route('rentals.pay', $rental) }}" method="POST">
      @csrf
      <div class="modal-header"><h6 class="modal-title fw-semibold"><i class="bi bi-cash-coin me-2"></i>Registrar pago</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="small text-muted">Saldo pendiente: <strong>Bs. {{ number_format($rental->balance, 2) }}</strong></p>
        <div class="mb-3"><label class="form-label small fw-semibold">Monto *</label>
            <div class="input-group"><span class="input-group-text bg-light">Bs.</span>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $rental->balance }}" value="{{ number_format($rental->balance, 2, '.', '') }}" required></div></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Método</label>
            <select name="method" class="form-select">
                <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="qr">QR</option>
            </select></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Referencia</label><input type="text" name="reference" class="form-control"></div>
        <div class="mb-0"><label class="form-label small fw-semibold">Notas</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Registrar</button></div>
    </form>
  </div>
</div>
@endif

{{-- Modal Penalización --}}
@if($canEdit)
<div class="modal fade" id="penaltyModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" action="{{ route('rentals.penalty', $rental) }}" method="POST">
      @csrf
      <div class="modal-header"><h6 class="modal-title fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Agregar penalización</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label small fw-semibold">Concepto *</label><input type="text" name="concept" class="form-control" required placeholder="Ej: Daño en espejo"></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Monto *</label>
            <div class="input-group"><span class="input-group-text bg-light">Bs.</span>
            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required></div></div>
        <div class="mb-3"><label class="form-label small fw-semibold">Fecha</label><input type="date" name="penalty_date" class="form-control" value="{{ now()->format('Y-m-d') }}"></div>
        <div class="mb-0"><label class="form-label small fw-semibold">Notas</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Agregar</button></div>
    </form>
  </div>
</div>
@endif

@endsection
