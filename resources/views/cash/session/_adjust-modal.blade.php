{{-- Modal compartido "Resolver diferencia" (ajuste real / corrección de conteo).
     Requiere $canAdjustCash en el scope. Se usa en la pestaña de cierres
     (cash/movements/index) y en el detalle de sesión (cash/session/show). --}}
@if($canAdjustCash)
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header adjust-modal-header">
                <h5 class="modal-title fw-semibold"><i class="bi bi-sliders me-2"></i>Resolver diferencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-muted small mb-3" id="adjustRegisterLabel"></div>

                {{-- Referencia (común a ambas pestañas) --}}
                <div class="row g-2 text-center mb-3">
                    <div class="col-4">
                        <div class="rounded-3 bg-light p-2">
                            <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Esperado</div>
                            <div class="fw-bold" id="adjExpected">—</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="rounded-3 bg-light p-2">
                            <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Contado</div>
                            <div class="fw-bold" id="adjCounted">—</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="rounded-3 bg-light p-2">
                            <div class="text-muted text-uppercase" style="font-size:.6rem;letter-spacing:.04em;">Diferencia</div>
                            <div class="fw-bold" id="adjDifference">—</div>
                        </div>
                    </div>
                </div>

                {{-- Selector de modo --}}
                <ul class="nav nav-pills nav-fill adjust-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tabRecountBtn" data-bs-toggle="pill"
                                data-bs-target="#tabRecount" type="button" role="tab">
                            <i class="bi bi-pencil-square me-1"></i>Error de conteo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tabRealBtn" data-bs-toggle="pill"
                                data-bs-target="#tabReal" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1"></i>Sobra / falta dinero
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- ── Ajuste real (crea movimiento) ── --}}
                    <div class="tab-pane fade" id="tabReal" role="tabpanel">
                        <form method="POST" id="adjustFormReal">
                            @csrf
                            <div class="alert alert-warning border-0 py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i>Registra un <strong>ingreso/egreso de caja</strong> por la diferencia (el dinero realmente sobró o faltó).
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="adjAmount">
                                    Monto a ajustar <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal" style="font-size:.75rem;">(sugerido: la diferencia)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light px-2">Bs.</span>
                                    <input type="number" name="amount" id="adjAmount" class="form-control"
                                           step="0.01" min="0.01" required>
                                </div>
                                <div class="mt-2 small fw-semibold" id="adjPreview"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="adjReasonReal">Motivo <span class="text-danger">*</span></label>
                                <textarea name="reason" id="adjReasonReal" class="form-control" rows="2" maxlength="500" required
                                          placeholder="Ej. sobrante real, vuelto mal dado, pérdida de efectivo…"></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-warning text-dark fw-semibold px-4">
                                    <i class="bi bi-check-lg me-1"></i>Registrar ajuste
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- ── Corrección de conteo (sin movimiento) ── --}}
                    <div class="tab-pane fade show active" id="tabRecount" role="tabpanel">
                        <form method="POST" id="adjustFormRecount">
                            @csrf
                            <div class="alert alert-info border-0 py-2 small mb-3">
                                <i class="bi bi-info-circle me-1"></i><strong>No genera movimiento de caja.</strong> Úsalo cuando el cajero contó o tecleó mal: corrige el monto contado y recalcula la diferencia.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="adjNewCount">
                                    Monto real contado <span class="text-danger">*</span>
                                    <span class="text-muted fw-normal" style="font-size:.75rem;">(sugerido: el esperado)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light px-2">Bs.</span>
                                    <input type="number" name="counted_amount" id="adjNewCount" class="form-control"
                                           step="0.01" min="0" required>
                                </div>
                                <div class="mt-2 small fw-semibold" id="adjRecountPreview"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small" for="adjReasonRecount">Motivo <span class="text-danger">*</span></label>
                                <textarea name="reason" id="adjReasonRecount" class="form-control" rows="2" maxlength="500" required
                                          placeholder="Ej. el cajero contó mal, se tecleó un monto equivocado…"></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">
                                    <i class="bi bi-check-lg me-1"></i>Guardar corrección
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Header del modal de ajuste */
    #adjustModal .adjust-modal-header {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border-bottom: 0;
    }
    #adjustModal .adjust-modal-header .modal-title,
    #adjustModal .adjust-modal-header .modal-title i { color: #fff; }

    /* Pestañas del modal de ajuste */
    .adjust-tabs .nav-link {
        color: #495057;
        border: 1.5px solid #dee2e6;
        font-size: .82rem;
        font-weight: 600;
    }
    .adjust-tabs .nav-link.active {
        background: var(--brand-black, #0a0a0a);
        border-color: var(--brand-black, #0a0a0a);
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.__adjustModalInit) return;   // evitar doble inicialización
    window.__adjustModalInit = true;

    var adjustModalEl = document.getElementById('adjustModal');
    if (!adjustModalEl) return;

    var adjustDiff     = 0;   // diferencia actual (con signo)
    var adjustExpected = 0;   // monto esperado
    var formReal    = document.getElementById('adjustFormReal');
    var formRecount = document.getElementById('adjustFormRecount');

    function fmt(n) {
        return Number(n).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Previsualización modo "ajuste real": diferencia resultante tras el movimiento
    function renderAdjustPreview() {
        var amountEl = document.getElementById('adjAmount');
        var out = document.getElementById('adjPreview');
        if (!amountEl || !out) return;
        var amount = parseFloat(amountEl.value);
        var absDiff = Math.abs(adjustDiff);

        if (isNaN(amount) || amount <= 0) { out.textContent = ''; return; }
        if (amount > absDiff + 0.001) {
            out.className = 'mt-2 small fw-semibold text-danger';
            out.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>El monto no puede superar la diferencia (' + fmt(absDiff) + ').';
            return;
        }
        var sign = adjustDiff > 0 ? 1 : -1;
        var resulting = sign * (absDiff - amount);
        var label = Math.abs(resulting) < 0.005
            ? '<span class="text-success">exacto</span>'
            : (resulting > 0 ? '<span class="text-success">sobrante</span>' : '<span class="text-danger">faltante</span>');
        out.className = 'mt-2 small fw-semibold text-muted';
        out.innerHTML = 'Diferencia resultante: <strong>' + (resulting > 0 ? '+' : '') + fmt(resulting) + '</strong> ' + label;
    }

    // Previsualización modo "error de conteo": nueva diferencia = contado − esperado
    function renderRecountPreview() {
        var el  = document.getElementById('adjNewCount');
        var out = document.getElementById('adjRecountPreview');
        if (!el || !out) return;
        var counted = parseFloat(el.value);
        if (isNaN(counted)) { out.textContent = ''; return; }
        var resulting = counted - adjustExpected;
        var label = Math.abs(resulting) < 0.005
            ? '<span class="text-success">exacto</span>'
            : (resulting > 0 ? '<span class="text-success">sobrante</span>' : '<span class="text-danger">faltante</span>');
        out.className = 'mt-2 small fw-semibold text-muted';
        out.innerHTML = 'Nueva diferencia: <strong>' + (resulting > 0 ? '+' : '') + fmt(resulting) + '</strong> ' + label
            + ' <span class="text-muted">· sin movimiento de caja</span>';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-adjust-cash');
        if (!btn) return;
        e.stopPropagation();

        var id         = btn.dataset.session;
        var expected   = parseFloat(btn.dataset.expected) || 0;
        var counted    = parseFloat(btn.dataset.counted) || 0;
        adjustDiff     = parseFloat(btn.dataset.difference) || 0;
        adjustExpected = expected;

        formReal.action    = '{{ route('cash.session.adjust', '__ID__') }}'.replace('__ID__', id);
        formRecount.action = '{{ route('cash.session.recount', '__ID__') }}'.replace('__ID__', id);

        var regLabel = document.getElementById('adjustRegisterLabel');
        if (regLabel) regLabel.innerHTML = '<i class="bi bi-safe2 me-1"></i>' + (btn.dataset.register || 'Caja');

        document.getElementById('adjExpected').textContent = 'Bs. ' + fmt(expected);
        document.getElementById('adjCounted').textContent  = 'Bs. ' + fmt(counted);
        var diffEl = document.getElementById('adjDifference');
        diffEl.textContent = (adjustDiff > 0 ? '+' : '') + fmt(adjustDiff);
        diffEl.className = 'fw-bold ' + (adjustDiff > 0 ? 'text-success' : (adjustDiff < 0 ? 'text-danger' : 'text-muted'));

        // Modo real: sugerir |diferencia|
        var amountEl = document.getElementById('adjAmount');
        amountEl.max   = Math.abs(adjustDiff).toFixed(2);
        amountEl.value = Math.abs(adjustDiff).toFixed(2);
        document.getElementById('adjReasonReal').value = '';
        renderAdjustPreview();

        // Modo corrección: sugerir el esperado
        var countEl = document.getElementById('adjNewCount');
        countEl.value = expected.toFixed(2);
        document.getElementById('adjReasonRecount').value = '';
        renderRecountPreview();

        // Siempre abrir en la primera pestaña ("Error de conteo")
        var firstTab = document.getElementById('tabRecountBtn');
        if (firstTab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(firstTab).show();

        bootstrap.Modal.getOrCreateInstance(adjustModalEl).show();
    });

    var amountInput = document.getElementById('adjAmount');
    if (amountInput) amountInput.addEventListener('input', renderAdjustPreview);
    var countInput = document.getElementById('adjNewCount');
    if (countInput) countInput.addEventListener('input', renderRecountPreview);

    // Si estamos en la pestaña de cierres, reabrirla tras el redirect
    [formReal, formRecount].forEach(function (f) {
        if (f) f.addEventListener('submit', function () {
            if (document.getElementById('paneCierres')) {
                try { sessionStorage.setItem('cashOpenCierres', '1'); } catch (e) {}
            }
        });
    });
})();
</script>
@endpush
@endif
