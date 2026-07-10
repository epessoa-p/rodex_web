@php
    $expPersonal = auth()->user()->personal ?? null;
    $expSession  = null;
    if ($expPersonal) {
        $expSession = \App\Models\CashRegisterSession::whereHas(
            'cashRegister',
            fn ($q) => $q->where('assigned_personal_id', $expPersonal->id)->where('active', true)
        )->whereNull('closed_at')->latest()->first();
    }
@endphp

@if($expSession)
    <button class="btn btn-expense" type="button" data-bs-toggle="modal" data-bs-target="#modalGasto" title="Registrar gasto">
        <i class="bi bi-wallet2 me-1"></i>Gastos
    </button>

    <div class="modal fade" id="modalGasto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow" action="{{ route('cash.expense.store') }}" method="POST" id="formGasto">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-wallet2 me-2 text-danger"></i>Registrar gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    {{-- Dinero disponible en la caja del personal --}}
                    <div class="d-flex align-items-center justify-content-between rounded-3 px-3 py-2 mb-3"
                         style="background:#f0fbf4;border:1px solid #c9efd6;">
                        <span class="small fw-semibold text-success-emphasis">
                            <i class="bi bi-cash-stack me-1"></i>Disponible en caja
                        </span>
                        <span class="fw-bold fs-6 text-success" id="gastoCajaBalance">Bs. {{ number_format($expSession->expectedBalance(), 2) }}</span>
                    </div>

                    <div id="gastoLoading" class="text-center text-muted py-2 small"><span class="spinner-border spinner-border-sm me-1"></span>Cargando…</div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tipo de gasto <span class="text-danger">*</span></label>
                        <select name="expense_type" id="gastoType" class="form-select" data-no-search required>
                            <option value="servicio">Servicio (básico / externo)</option>
                            <option value="personal">Pago a personal</option>
                            <option value="transporte">Transporte / envío</option>
                            <option value="proveedor">Pago a proveedor</option>
                            <option value="otro">Otro gasto operativo</option>
                        </select>
                    </div>

                    {{-- Servicio --}}
                    <div class="gasto-block" data-block="servicio">
                        <label class="form-label small fw-semibold">Servicio</label>
                        <select id="gastoService" class="form-select form-select-sm mb-2" data-no-search>
                            <option value="">— Escribir concepto —</option>
                        </select>
                        <input type="text" name="concept" id="gastoServiceConcept" class="form-control form-control-sm" placeholder="Concepto del servicio (ej. Internet Tigo)">
                    </div>

                    {{-- Personal --}}
                    <div class="gasto-block d-none" data-block="personal">
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label small fw-semibold">Personal</label>
                                <select name="personal_id" id="gastoPersonal" class="form-select form-select-sm" data-no-search><option value="">— Seleccionar —</option></select>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-semibold">Período</label>
                                <select name="period" class="form-select form-select-sm" data-no-search>
                                    <option value="Día">Día</option>
                                    <option value="Semana">Semana</option>
                                    <option value="Quincena">Quincena</option>
                                    <option value="Mes">Mes</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Transporte --}}
                    <div class="gasto-block d-none" data-block="transporte">
                        <label class="form-label small fw-semibold">Concepto</label>
                        <input type="text" name="concept" class="form-control form-control-sm" placeholder="Ej. Envío a Santa Cruz / guía 12345">
                    </div>

                    {{-- Proveedor --}}
                    <div class="gasto-block d-none" data-block="proveedor">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Proveedor</label>
                                <select name="supplier_id" id="gastoSupplier" class="form-select form-select-sm" data-no-search><option value="">— Seleccionar —</option></select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Factura pendiente</label>
                                <select name="purchase_id" id="gastoPurchase" class="form-select form-select-sm" data-no-search><option value="">— Selecciona un proveedor —</option></select>
                                <div class="form-text" id="gastoPurchaseHint"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Otro --}}
                    <div class="gasto-block d-none" data-block="otro">
                        <label class="form-label small fw-semibold">Concepto</label>
                        <input type="text" name="concept" class="form-control form-control-sm" placeholder="Describe el gasto">
                    </div>

                    {{-- Comunes --}}
                    <div class="row g-2 mt-1">
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">Bs.</span>
                                <input type="number" name="amount" id="gastoAmount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Método</label>
                            <select name="method" class="form-select form-select-sm" data-no-search>
                                @foreach(\App\Models\CashMovement::METHOD_LABELS as $k => $lbl)
                                <option value="{{ $k }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold">Fecha</label>
                            <input type="date" name="movement_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notas</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Opcional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg me-1"></i>Registrar gasto</button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
    <style>
        .btn-expense {
            border: 1px solid #e3e3e3; background: #fff; color: #b00020; font-weight: 600;
            border-radius: .5rem; padding: .35rem .8rem; font-size: .85rem; transition: all .15s ease;
        }
        .btn-expense:hover { background: #b00020; border-color: #b00020; color: #fff; }
    </style>
    @endpush

    @push('scripts')
    <script>
    (function () {
        const modal = document.getElementById('modalGasto');
        if (!modal) return;
        const DATA_URL = '{{ route('cash.expense.data') }}';
        let loaded = false, pending = [];

        const typeSel   = document.getElementById('gastoType');
        const amount    = document.getElementById('gastoAmount');
        const svcSel     = document.getElementById('gastoService');
        const svcConcept = document.getElementById('gastoServiceConcept');
        const persSel    = document.getElementById('gastoPersonal');
        const supSel     = document.getElementById('gastoSupplier');
        const purSel     = document.getElementById('gastoPurchase');
        const purHint    = document.getElementById('gastoPurchaseHint');

        function showBlock(type) {
            modal.querySelectorAll('.gasto-block').forEach(function (b) {
                b.classList.toggle('d-none', b.dataset.block !== type);
            });
        }
        typeSel.addEventListener('change', function () { showBlock(this.value); });

        // Servicio → prellenar monto con default
        svcSel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (this.value) {
                svcConcept.value = opt.textContent.trim();
                if (opt.dataset.amount && parseFloat(opt.dataset.amount) > 0) amount.value = parseFloat(opt.dataset.amount).toFixed(2);
            }
        });

        // Proveedor → poblar facturas pendientes
        supSel.addEventListener('change', function () {
            const sid = this.value;
            purSel.innerHTML = '<option value="">— Seleccionar factura —</option>';
            purHint.textContent = '';
            (pending || []).filter(p => String(p.supplier_id) === String(sid)).forEach(function (p) {
                const o = document.createElement('option');
                o.value = p.id; o.dataset.balance = p.balance;
                o.textContent = p.code + ' · saldo Bs. ' + Number(p.balance).toFixed(2) + (p.date ? ' · ' + p.date : '');
                purSel.appendChild(o);
            });
        });
        purSel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.balance) {
                amount.value = parseFloat(opt.dataset.balance).toFixed(2);
                amount.max = opt.dataset.balance;
                purHint.textContent = 'Saldo de la factura: Bs. ' + parseFloat(opt.dataset.balance).toFixed(2);
            }
        });

        function fill(sel, items, valueKey, labelFn, extra) {
            items.forEach(function (it) {
                const o = document.createElement('option');
                o.value = it[valueKey];
                o.textContent = labelFn(it);
                if (extra) extra(o, it);
                sel.appendChild(o);
            });
        }

        modal.addEventListener('show.bs.modal', function () {
            if (loaded) return;
            fetch(DATA_URL, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(function (d) {
                    fill(svcSel, d.services || [], 'id', s => s.name, (o, s) => { o.dataset.amount = s.default_amount || ''; });
                    fill(persSel, d.personal || [], 'id', p => p.full_name);
                    fill(supSel, d.suppliers || [], 'id', s => s.name);
                    pending = d.pendingPurchases || [];
                    if (d.session) {
                        document.getElementById('gastoCajaBalance').textContent =
                            'Bs. ' + Number(d.session.balance).toFixed(2);
                    }
                    loaded = true;
                })
                .catch(function () {})
                .finally(function () { document.getElementById('gastoLoading').classList.add('d-none'); });
        });

        showBlock(typeSel.value);
    })();
    </script>
    @endpush
@endif
