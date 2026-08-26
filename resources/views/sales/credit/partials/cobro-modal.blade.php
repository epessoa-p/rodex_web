{{-- Modal de registro de cobro de una venta a crédito. Requiere $sale (con installments). --}}
@php $allInst = $sale->installments->sortBy('number'); @endphp
<div class="modal fade" id="cobroModal{{ $sale->id }}" tabindex="-1"
     aria-labelledby="cobroModalLabel{{ $sale->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="cobroModalLabel{{ $sale->id }}">
                    <i class="bi bi-cash-coin me-2 text-muted"></i>Registrar cobro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('credit.payment', $sale) }}" method="POST">
                @csrf
                <div class="modal-body p-3">

                    {{-- Sale info --}}
                    <div class="rounded-3 border p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">{{ $sale->code }}</div>
                                <div class="text-muted small">{{ $sale->client_name }}</div>
                            </div>
                            <span class="badge bg-{{ $sale->payment_status_color }}-subtle text-{{ $sale->payment_status_color }} border border-{{ $sale->payment_status_color }}-subtle">
                                {{ $sale->payment_status_label }}
                            </span>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4">
                                <div class="text-muted">Total venta</div>
                                <div class="fw-semibold">{{ money($sale->total, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Pagado</div>
                                <div class="fw-semibold text-success">{{ money($sale->paid_amount, null, 2) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Saldo</div>
                                <div class="fw-bold text-danger fs-6">{{ money($sale->balance, null, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Aplicar a cuota: fila de cards seleccionables (las pagadas no se pueden elegir) --}}
                    @if($allInst->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Aplicar a cuota <span class="text-muted">({{ $allInst->count() }})</span></label>
                        <input type="hidden" name="sale_installment_id" id="inst_{{ $sale->id }}" value="">
                        <div class="d-flex gap-2 overflow-auto pb-2 inst-cards" data-target="inst_{{ $sale->id }}" data-amount="amt_{{ $sale->id }}">
                            <button type="button" class="inst-card active" data-value="" data-amount-val="{{ number_format($sale->balance, 2, '.', '') }}">
                                <div class="ic-title"><i class="bi bi-shuffle me-1"></i>Automático</div>
                                <div class="ic-sub">Distribuir saldo</div>
                                <div class="ic-amt">{{ money($sale->balance, null, 2) }}</div>
                            </button>
                            @foreach($allInst as $inst)
                            @php $isPaid = (float) $inst->balance <= 0.001; @endphp
                            <button type="button"
                                    class="inst-card {{ $isPaid ? 'paid' : ($inst->is_overdue ? 'overdue' : '') }}"
                                    @if($isPaid) disabled title="Cuota pagada" @else data-value="{{ $inst->id }}" data-amount-val="{{ number_format($inst->balance, 2, '.', '') }}" @endif>
                                <div class="ic-title">
                                    Cuota #{{ $inst->number }}
                                    @if($isPaid)
                                        <span class="badge bg-success text-white ms-1" style="font-size:.55rem;"><i class="bi bi-check-lg"></i> PAGADA</span>
                                    @elseif($inst->is_overdue)
                                        <span class="badge bg-danger text-white ms-1" style="font-size:.55rem;">VENCIDA</span>
                                    @endif
                                </div>
                                <div class="ic-sub"><i class="bi bi-calendar3 me-1"></i>{{ $inst->due_date->format('d/m/Y') }}</div>
                                <div class="ic-amt">{{ $isPaid ? money($inst->amount) : money($inst->balance) }}</div>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="amt_{{ $sale->id }}">
                                Monto <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                <input type="number" id="amt_{{ $sale->id }}" name="amount"
                                       step="0.01" min="0.01" max="{{ $sale->balance }}"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount', number_format($sale->balance, 2, '.', '')) }}"
                                       required>
                            </div>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="pd_{{ $sale->id }}">
                                Fecha <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="pd_{{ $sale->id }}" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                   required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="method_{{ $sale->id }}">Método de pago</label>
                            <select name="method" id="method_{{ $sale->id }}" class="form-select">
                                <option value="efectivo" {{ old('method', 'efectivo') === 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                <option value="transferencia" {{ old('method') === 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                                <option value="tarjeta" {{ old('method') === 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="notes_{{ $sale->id }}">Notas</label>
                        <textarea name="notes" id="notes_{{ $sale->id }}"
                                  class="form-control" rows="2"
                                  placeholder="Observaciones del cobro...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="alert alert-info border-0 py-2 mb-0" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        El cobro ingresa a tu caja abierta.
                    </div>

                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Registrar cobro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
