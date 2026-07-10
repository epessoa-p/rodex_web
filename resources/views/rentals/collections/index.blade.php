@extends('layouts.app')
@section('title', 'Alquileres · Cobros')
@section('page')
@php $company = auth()->user()->getCurrentCompany(); $canPay = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.pay', $company); @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-cash-stack me-2 text-danger"></i>Cobros de renta</h1>
            <p class="text-muted mb-0 small">Cuotas por cobrar de los contratos de renta periódica.</p>
        </div>
        <div class="btn-group">
            @foreach(['vencidas' => 'Vencidas', 'hoy' => 'Del día', 'todas' => 'Todas'] as $key => $lbl)
            <a href="{{ route('rentals.collections', ['due' => $key]) }}" class="btn btn-sm {{ $filter === $key ? 'btn-primary' : 'btn-light border' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    @include('rentals.partials.flash')

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Contrato</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Moto</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cuota / Período</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Vence</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Saldo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Mora</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($installments as $inst)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2"><a href="{{ route('rentals.show', $inst->rental_contract_id) }}" class="text-decoration-none fw-semibold text-dark font-monospace" style="font-size:.8rem;">{{ $inst->contract?->code }}</a></td>
                            <td class="py-2 small">{{ $inst->contract?->client?->full_name ?? '—' }}</td>
                            <td class="py-2 small">{{ $inst->contract?->motoUnit?->display_name ?? '—' }}</td>
                            <td class="py-2 small">#{{ $inst->number }} · {{ $inst->period_label ?? '—' }}</td>
                            <td class="py-2 small">
                                @if($inst->is_overdue)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Vencida {{ $inst->overdue_days }}d</span>
                                @else
                                    <span class="text-muted">{{ $inst->due_date?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold">Bs. {{ number_format($inst->balance, 2) }}</td>
                            <td class="py-2 text-end {{ $inst->accrued_late_fee > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                {{ $inst->accrued_late_fee > 0 ? 'Bs. ' . number_format($inst->accrued_late_fee, 2) : '—' }}
                            </td>
                            <td class="py-2 text-end pe-4">
                                @if($canPay)
                                <button class="btn btn-sm btn-primary btn-cobrar"
                                        data-action="{{ route('rentals.pay', $inst->rental_contract_id) }}"
                                        data-inst="{{ $inst->id }}"
                                        data-code="{{ $inst->contract?->code }}"
                                        data-num="{{ $inst->number }}"
                                        data-balance="{{ number_format($inst->balance, 2, '.', '') }}"
                                        data-latefee="{{ number_format($inst->accrued_late_fee, 2, '.', '') }}">
                                    <i class="bi bi-cash-coin me-1"></i>Cobrar
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-check2-circle fs-2 opacity-25 d-block mb-2"></i>No hay cuotas por cobrar en este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $installments->links() }}</div>
</div>

@if($canPay)
<div class="modal fade" id="cobrarModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="cobrarForm">
      @csrf
      <input type="hidden" name="installment_id" id="cb_inst">
      <div class="modal-header"><h6 class="modal-title fw-semibold"><i class="bi bi-cash-coin me-2"></i>Cobrar cuota</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="small text-muted mb-3" id="cb_info"></p>
        <div class="mb-3"><label class="form-label small fw-semibold">Monto *</label>
            <div class="input-group"><span class="input-group-text bg-light">Bs.</span>
            <input type="number" name="amount" id="cb_amount" class="form-control" step="0.01" min="0.01" required></div></div>
        <div class="mb-3" id="cb_latewrap" style="display:none;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="charge_late_fee" id="cb_latefee" value="1">
                <label class="form-check-label small" for="cb_latefee">Cobrar mora acumulada (<span id="cb_latefee_amt"></span>)</label>
            </div>
        </div>
        <div class="mb-3"><label class="form-label small fw-semibold">Método</label>
            <select name="method" class="form-select">
                <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="qr">QR</option>
            </select></div>
        <div class="mb-0"><label class="form-label small fw-semibold">Referencia</label><input type="text" name="reference" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Registrar cobro</button></div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('cobrarModal');
    const modal = new bootstrap.Modal(modalEl);
    document.querySelectorAll('.btn-cobrar').forEach(btn => btn.addEventListener('click', function () {
        const d = this.dataset;
        document.getElementById('cobrarForm').action = d.action;
        document.getElementById('cb_inst').value = d.inst;
        document.getElementById('cb_amount').value = d.balance;
        document.getElementById('cb_info').textContent = d.code + ' · Cuota #' + d.num + ' — saldo Bs. ' + d.balance;
        const late = parseFloat(d.latefee) || 0;
        const wrap = document.getElementById('cb_latewrap');
        if (late > 0) {
            wrap.style.display = '';
            document.getElementById('cb_latefee_amt').textContent = 'Bs. ' + late.toFixed(2);
            document.getElementById('cb_latefee').checked = true;
        } else {
            wrap.style.display = 'none';
            document.getElementById('cb_latefee').checked = false;
        }
        modal.show();
    }));
});
</script>
@endpush
@endif
@endsection
