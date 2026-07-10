{{-- Estilos + JS del modal de cobro (cuotas en cards). Se emite una sola vez. --}}
@once
@push('styles')
<style>
.inst-cards { scrollbar-width: thin; }
.inst-cards::-webkit-scrollbar { height: 6px; }
.inst-cards::-webkit-scrollbar-thumb { background: #d8d8d8; border-radius: 3px; }
.inst-card {
    flex: 0 0 auto; min-width: 132px; text-align: left;
    border: 1.5px solid #e3e3e3; background: #fff; border-radius: 10px;
    padding: .55rem .7rem; cursor: pointer; transition: all .15s ease;
}
.inst-card:hover { border-color: var(--brand-black, #0a0a0a); }
.inst-card.active {
    border-color: var(--brand-red, #e10600);
    background: rgba(225,6,0,.04);
    box-shadow: 0 0 0 .15rem rgba(225,6,0,.12);
}
.inst-card.overdue { border-color: #f1aeb5; }
.inst-card.paid {
    background: #f6f7f8; border-color: #e3e3e3; color: #9aa0a6;
    cursor: not-allowed; opacity: .75;
}
.inst-card.paid:hover { border-color: #e3e3e3; }
.inst-card.paid .ic-amt { text-decoration: line-through; }
.inst-card .ic-title { font-weight: 700; font-size: .78rem; }
.inst-card .ic-sub   { color: #8a8a8a; font-size: .7rem; }
.inst-card .ic-amt   { font-weight: 700; font-size: .9rem; margin-top: 2px; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.__cobroCardsInit) return;
    window.__cobroCardsInit = true;
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.inst-card');
        if (!card) return;
        const wrap = card.closest('.inst-cards');
        if (!wrap) return;
        wrap.querySelectorAll('.inst-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        const hidden = document.getElementById(wrap.dataset.target);
        if (hidden) hidden.value = card.dataset.value || '';
        const amt = document.getElementById(wrap.dataset.amount);
        if (amt && card.dataset.amountVal) amt.value = card.dataset.amountVal;
    });
})();
</script>
@endpush
@endonce
