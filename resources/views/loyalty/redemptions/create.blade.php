@extends('layouts.app')
@section('title', 'Fidelización · Registrar canje')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gift me-2 text-danger"></i>Registrar canje</h1>
            <p class="text-muted mb-0 small">Selecciona un cliente y la recompensa a canjear.</p>
        </div>
        <a href="{{ route('loyalty.redemptions.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('loyalty.redemptions.store') }}" method="POST" id="redeemForm">
        @csrf
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <label class="form-label small fw-semibold">Cliente <span class="text-danger">*</span></label>
                        <select name="client_id" id="rd_client" class="form-select" required
                                data-url="{{ route('loyalty.clients.data', ':id') }}">
                            <option value="">Selecciona un cliente…</option>
                            @foreach($clients as $c)
                            <option value="{{ $c->id }}" data-balance="{{ $c->points_balance }}">
                                {{ $c->full_name }}{{ $c->id_number ? ' ('.$c->id_number.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div class="mt-3 rounded-3 border p-3 bg-light d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Saldo disponible</span>
                            <span class="fw-bold fs-5 text-primary"><span id="rd_balance">0</span> pts</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-stars me-2 text-muted"></i>Recompensa a canjear</h6>
                    </div>
                    <div class="card-body p-3">
                        <input type="hidden" name="reward_id" id="rd_reward" required>
                        <div class="row g-2" id="rd_rewards">
                            @forelse($rewards as $r)
                            <div class="col-md-6">
                                <button type="button" class="reward-pick w-100 text-start" data-id="{{ $r->id }}" data-cost="{{ $r->points_cost }}"
                                        {{ ($r->stock !== null && $r->stock <= 0) ? 'disabled' : '' }}>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold small">{{ $r->name }}</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ number_format($r->points_cost, 0) }} pts</span>
                                    </div>
                                    <div class="text-muted" style="font-size:.7rem;">Stock: {{ $r->stock === null ? 'Ilimitado' : $r->stock }}</div>
                                </button>
                            </div>
                            @empty
                            <div class="col-12 text-center text-muted py-4 small">No hay recompensas activas. Crea alguna en Recompensas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4" id="rd_submit" disabled><i class="bi bi-check-lg me-1"></i>Confirmar canje</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
.reward-pick { border:1.5px solid #e3e3e3; background:#fff; border-radius:10px; padding:.6rem .75rem; transition:all .15s; }
.reward-pick:hover:not(:disabled) { border-color:#22242e; }
.reward-pick.active { border-color:var(--brand-red,#e63946); background:rgba(230,57,70,.04); box-shadow:0 0 0 .15rem rgba(230,57,70,.12); }
.reward-pick:disabled { opacity:.5; cursor:not-allowed; }
</style>
@endpush
@push('scripts')
<script>
(function () {
    const sel = document.getElementById('rd_client');
    const balEl = document.getElementById('rd_balance');
    const rewardInput = document.getElementById('rd_reward');
    const submit = document.getElementById('rd_submit');
    let balance = 0;

    function refreshAffordable() {
        document.querySelectorAll('.reward-pick').forEach(btn => {
            const cost = parseInt(btn.dataset.cost, 10) || 0;
            const noStock = btn.hasAttribute('disabled') && !btn.classList.contains('js-stock');
            // afford: balance >= cost
            if (cost > balance) { btn.classList.add('js-afford-off'); btn.setAttribute('disabled', 'disabled'); }
            else if (btn.classList.contains('js-afford-off')) { btn.classList.remove('js-afford-off'); btn.removeAttribute('disabled'); }
        });
    }

    function onClientChange() {
        const opt = sel.options[sel.selectedIndex];
        rewardInput.value = ''; submit.disabled = true;
        document.querySelectorAll('.reward-pick').forEach(b => b.classList.remove('active'));
        if (!sel.value) { balance = 0; balEl.textContent = '0'; refreshAffordable(); return; }
        balance = parseInt(opt.dataset.balance, 10) || 0;
        balEl.textContent = balance.toLocaleString();
        refreshAffordable();
    }

    sel.addEventListener('change', onClientChange);
    // Select2 dispara change vía jQuery; re-enganchar
    if (window.jQuery) { jQuery(sel).on('change', onClientChange); }

    document.querySelectorAll('.reward-pick').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.hasAttribute('disabled')) return;
            document.querySelectorAll('.reward-pick').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            rewardInput.value = this.dataset.id;
            submit.disabled = !sel.value;
        });
    });
})();
</script>
@endpush
@endsection
