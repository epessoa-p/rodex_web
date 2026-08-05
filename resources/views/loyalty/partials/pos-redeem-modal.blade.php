{{-- Modal de canje de puntos para el POS. Requiere: $session (caja). --}}
<div class="modal fade" id="redeemModal" tabindex="-1" aria-hidden="true"
     data-balance-url="{{ route('loyalty.clients.data', ':id') }}"
     data-store-url="{{ route('loyalty.redemptions.store') }}"
     data-branch-id="{{ $session->cashRegister->branch_id ?? '' }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-gift me-2 text-danger"></i>Canjear puntos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center rounded-3 border p-3 bg-light mb-3">
                    <div>
                        <div class="text-muted small">Cliente</div>
                        <div class="fw-semibold" id="rm_client">—</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Saldo</div>
                        <div class="fw-bold fs-5 text-primary"><span id="rm_balance">0</span> pts</div>
                    </div>
                </div>
                <div id="rm_empty" class="text-center text-muted py-4" style="display:none;"></div>
                <div class="row g-2" id="rm_rewards"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('redeemModal');
    if (!modalEl) return;
    const balanceUrl = modalEl.dataset.balanceUrl;
    const storeUrl   = modalEl.dataset.storeUrl;
    const branchId   = modalEl.dataset.branchId;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    let currentClient = null;

    window.openRedeemModal = function () {
        const clientId = document.getElementById('client_id')?.value;
        const label    = document.getElementById('selectedClientLabel')?.textContent?.trim();
        if (!clientId) {
            (window.showToast || alert)('Selecciona un cliente registrado para canjear puntos.', 'warning');
            return;
        }
        currentClient = clientId;
        document.getElementById('rm_client').textContent = label || 'Cliente';
        document.getElementById('rm_rewards').innerHTML = '<div class="col-12 text-center text-muted py-3"><span class="spinner-border spinner-border-sm"></span> Cargando…</div>';
        document.getElementById('rm_empty').style.display = 'none';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        loadData(clientId);
    };

    function loadData(clientId) {
        fetch(balanceUrl.replace(':id', clientId), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => render(d.balance || 0, d.rewards || []))
            .catch(() => { document.getElementById('rm_rewards').innerHTML = '<div class="col-12 text-danger text-center py-3">Error al cargar.</div>'; });
    }

    function render(balance, rewards) {
        document.getElementById('rm_balance').textContent = Number(balance).toLocaleString();
        const wrap = document.getElementById('rm_rewards');
        const empty = document.getElementById('rm_empty');
        if (!rewards.length) {
            wrap.innerHTML = '';
            empty.style.display = ''; empty.innerHTML = '<i class="bi bi-gift fs-2 d-block mb-2 opacity-25"></i>No hay recompensas disponibles.';
            return;
        }
        empty.style.display = 'none';
        wrap.innerHTML = rewards.map(r => {
            const disabled = (!r.available || !r.affordable);
            const reason = !r.available ? 'Sin stock' : (!r.affordable ? 'Saldo insuficiente' : '');
            return `
            <div class="col-md-6">
                <button type="button" class="rm-pick w-100 text-start" data-id="${r.id}" data-cost="${r.points_cost}" ${disabled ? 'disabled' : ''}>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold small">${r.name}</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">${Number(r.points_cost).toLocaleString()} pts</span>
                    </div>
                    ${reason ? `<div class="text-danger" style="font-size:.68rem;">${reason}</div>` : ''}
                </button>
            </div>`;
        }).join('');
        wrap.querySelectorAll('.rm-pick').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.hasAttribute('disabled')) return;
                confirmRedeem(this.dataset.id, this);
            });
        });
    }

    function confirmRedeem(rewardId, btn) {
        const doRedeem = function () {
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ client_id: currentClient, reward_id: rewardId, branch_id: branchId || null }),
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok || !d.ok) { (window.showToast || alert)(d.message || 'No se pudo canjear.', 'danger'); loadData(currentClient); return; }
                (window.showToast || alert)(d.message, 'success');
                render(d.balance, []); // recargar
                loadData(currentClient);
            })
            .catch(() => (window.showToast || alert)('Error de conexión.', 'danger'));
        };

        const ask = window.appConfirm
            ? window.appConfirm({ title: 'Confirmar canje', message: '¿Confirmar el canje de esta recompensa?', confirmText: 'Canjear', variant: 'primary' })
            : Promise.resolve(confirm('¿Confirmar el canje de esta recompensa?'));
        ask.then(function (ok) { if (ok) doRedeem(); });
    }
})();
</script>
<style>
.rm-pick { border:1.5px solid #e3e3e3; background:#fff; border-radius:10px; padding:.6rem .75rem; transition:all .15s; }
.rm-pick:hover:not(:disabled) { border-color:var(--brand-red,#e63946); }
.rm-pick:disabled { opacity:.5; cursor:not-allowed; }
</style>
@endpush
