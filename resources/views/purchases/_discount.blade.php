{{--
    Descuento del proveedor + "Total a pagar" editable (OC y compra directa).
    Se puede escribir el % , el monto o directamente el total que cobra el
    proveedor: los tres se mantienen sincronizados. Al servidor viaja solo el
    monto (hidden `discount`). El formulario llama a
    PurchaseDiscountUI.update(subtotal, impuesto) en su recalcTotals() y usa el
    descuento que devuelve.

    @param float $discount  Descuento inicial (edición / old()).
--}}
@php $pdSymbol = currency_symbol(); @endphp
<div class="pd-box rounded-3 px-2 py-2 mb-2">
    <div class="d-flex justify-content-between align-items-center mb-1 small">
        <span class="fw-semibold pd-title"><i class="bi bi-tag me-1"></i>Descuento del proveedor</span>
        <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none small d-none" id="pdClear">
            <i class="bi bi-x-circle me-1"></i>Quitar
        </button>
    </div>
    <div class="d-flex gap-2">
        <div class="input-group input-group-sm" style="max-width:110px">
            <input type="number" id="pdPct" step="0.01" min="0" max="100" inputmode="decimal"
                   class="form-control text-end" placeholder="0" aria-label="Descuento en porcentaje">
            <span class="input-group-text bg-light">%</span>
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-light">{{ $pdSymbol }}</span>
            <input type="number" id="pdAmt" step="0.01" min="0" inputmode="decimal"
                   class="form-control text-end" placeholder="0.00" aria-label="Descuento en monto">
        </div>
    </div>
    <div id="pdWarn" class="text-danger mt-1 d-none" style="font-size:.75rem"></div>
</div>
<input type="hidden" name="discount" id="pdDiscount" value="{{ number_format((float) ($discount ?? 0), 2, '.', '') }}">

<div class="d-flex justify-content-between align-items-center fw-bold border-top pt-2">
    <label for="pdTotal" class="mb-0">Total a pagar</label>
    <div class="input-group input-group-sm" style="width:150px">
        <span class="input-group-text bg-light">{{ $pdSymbol }}</span>
        <input type="number" id="pdTotal" step="0.01" min="0" inputmode="decimal"
               class="form-control text-end fw-bold">
    </div>
</div>
<div class="text-muted text-end mt-1" style="font-size:.72rem">
    ¿El proveedor te cobra otro total? Escríbelo y el descuento se calcula solo.
</div>

<style>
    .pd-box { background: rgba(25,135,84,.06); border: 1px dashed rgba(25,135,84,.35); }
    .pd-box .pd-title { color: #198754; }
    .pd-box.pd-active { background: rgba(25,135,84,.11); border-style: solid; }
</style>

<script>
window.PurchaseDiscountUI = (function () {
    const $ = id => document.getElementById(id);
    const pct = $('pdPct'), amt = $('pdAmt'), hid = $('pdDiscount'), tot = $('pdTotal');
    const warn = $('pdWarn'), clear = $('pdClear'), box = pct.closest('.pd-box');
    const r2 = v => Math.round(v * 100) / 100;
    const fmt = v => (window.money ? window.money(v, 2) : v.toFixed(2));

    let sub = 0, tax = 0;
    let disc = parseFloat(hid.value) || 0;
    let mode = 'amt';            // 'pct': se mantiene el % al cambiar productos
    let warning = '';

    function apply(from) {
        if (mode === 'pct') disc = r2(sub * (parseFloat(pct.value) || 0) / 100);
        if (disc > sub) { disc = sub; warning = warning || 'El descuento no puede pasar del subtotal.'; }
        if (disc < 0) disc = 0;
        hid.value = disc.toFixed(2);

        if (from !== 'pct') pct.value = disc > 0 && sub > 0 ? r2(disc / sub * 100) : '';
        if (from !== 'amt') amt.value = disc > 0 ? disc.toFixed(2) : '';
        if (from !== 'tot') tot.value = (sub - disc + tax).toFixed(2);

        warn.textContent = warning;
        warn.classList.toggle('d-none', !warning);
        clear.classList.toggle('d-none', disc <= 0);
        box.classList.toggle('pd-active', disc > 0);

        // Resúmenes del formulario (si existen).
        const total = sub - disc + tax;
        if ($('displayTotal')) $('displayTotal').textContent = fmt(total);
        if ($('summaryTotal')) $('summaryTotal').textContent = fmt(total);
        if ($('summaryDiscount')) $('summaryDiscount').textContent = '− ' + fmt(disc);
        if ($('summaryDiscountRow')) $('summaryDiscountRow').classList.toggle('d-none', disc <= 0);
        warning = '';
        return disc;
    }

    pct.addEventListener('input', () => {
        mode = 'pct';
        if ((parseFloat(pct.value) || 0) > 100) warning = 'El porcentaje no puede pasar de 100%.';
        apply('pct');
    });
    amt.addEventListener('input', () => {
        mode = 'amt';
        disc = r2(parseFloat(amt.value) || 0);
        apply('amt');
    });
    tot.addEventListener('input', () => {
        mode = 'amt';
        const t = parseFloat(tot.value);
        disc = isNaN(t) ? 0 : r2(sub + tax - t);
        if (disc < 0) { disc = 0; warning = 'El total no puede ser mayor al subtotal' + (tax > 0 ? ' más impuesto.' : '.'); }
        apply('tot');
    });
    // Al salir del total, se muestra el valor real aplicado.
    tot.addEventListener('blur', () => { tot.value = (sub - disc + tax).toFixed(2); });
    clear.addEventListener('click', () => { mode = 'amt'; disc = 0; apply(null); });

    return {
        /** Recalcula con el nuevo subtotal/impuesto y devuelve el descuento. */
        update(subtotal, taxAmount) {
            sub = r2(subtotal || 0);
            tax = r2(taxAmount || 0);
            return apply(document.activeElement === tot ? 'tot' : null);
        },
        get discount() { return disc; },
    };
})();
</script>
