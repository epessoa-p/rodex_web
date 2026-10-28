{{-- Configuración del Punto de venta de la empresa: paso del botón "Redondear"
     del total y si vende a crédito. Lo usan el alta/edición de empresa (super
     admin) y "Mi empresa". $company puede ser null (alta); $disabled opcional.
     La moneda es la de ESA empresa (en el alta, la que se está escribiendo). --}}
@php
    $company  = $company ?? null;
    $disabled = $disabled ?? false;
    $symbol   = $company ? currency_symbol($company) : (old('currency') ?: config('inventory.currency', 'Bs'));
    $step     = (string) old('pos_rounding_step', $company ? number_format($company->posRoundingStep(), 2, '.', '') : '0.50');
    $step     = in_array($step, ['1', '1.00'], true) ? '1' : '0.50';
    $credit   = (bool) old('allow_credit_sales', $company ? $company->allowsCreditSales() : true);
    $methods  = (array) old('payment_methods', $company ? $company->paymentMethods() : ['efectivo']);
    $otherPay = (bool) old('expenses_use_other_methods', $company ? $company->expensesUseOtherMethods() : true);
    $cardFields = array_values(array_filter((array) old('pos_card_fields', $company ? $company->posCardFields() : \App\Models\Company::POS_CARD_DEFAULT)));
    $labels   = \App\Models\CashMovement::METHOD_SHORT;
    $icons    = ['efectivo' => 'bi-cash', 'qr' => 'bi-qr-code', 'transferencia' => 'bi-bank', 'tarjeta' => 'bi-credit-card'];
@endphp

<div class="border rounded-3 p-3 mb-3">
    <div class="fw-semibold mb-2"><i class="bi bi-shop me-1 text-primary"></i>Punto de venta</div>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="pos_rounding_step" class="form-label small fw-semibold">Redondear totales a</label>
            <select id="pos_rounding_step" name="pos_rounding_step"
                    class="form-select @error('pos_rounding_step') is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}>
                <option value="0.50" {{ $step === '0.50' ? 'selected' : '' }} data-amount="0,50">{{ $symbol }} 0,50</option>
                <option value="1"    {{ $step === '1' ? 'selected' : '' }}    data-amount="1">{{ $symbol }} 1</option>
            </select>
            <div class="form-text">
                Si el total queda con centavos (ej. {{ $symbol }} 91,57), el POS ofrece un botón para
                bajarlo al valor cerrado: {{ $symbol }} 91,50 con 0,50 o {{ $symbol }} 91 con 1.
            </div>
            @error('pos_rounding_step')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold d-block">Ventas a crédito</label>
            {{-- hidden antes del switch: apagado también se envía (0). --}}
            <input type="hidden" name="allow_credit_sales" value="0" {{ $disabled ? 'disabled' : '' }}>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="allow_credit_sales"
                       name="allow_credit_sales" value="1" {{ $credit ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}>
                <label class="form-check-label" for="allow_credit_sales">Permitir ventas a crédito</label>
            </div>
            <div class="form-text">
                Apagado: el botón «A Crédito» no aparece en el POS ni en el formulario de venta.
                Los créditos ya dados se siguen cobrando normalmente.
            </div>
            @error('allow_credit_sales')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label class="form-label small fw-semibold d-block">Formas de pago aceptadas</label>
            {{-- Efectivo siempre: se envía oculto (el checkbox deshabilitado no viaja). --}}
            <input type="hidden" name="payment_methods[]" value="efectivo" {{ $disabled ? 'disabled' : '' }}>
            <div class="d-flex flex-wrap gap-3">
                @foreach(\App\Models\CashMovement::SALE_METHODS as $m)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="pm_{{ $m }}"
                           @if($m === 'efectivo') checked disabled @else name="payment_methods[]" value="{{ $m }}" {{ in_array($m, $methods, true) ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }} @endif>
                    <label class="form-check-label" for="pm_{{ $m }}"><i class="bi {{ $icons[$m] ?? 'bi-wallet2' }} me-1 text-muted"></i>{{ $labels[$m] ?? $m }}</label>
                </div>
                @endforeach
            </div>
            <div class="form-text">
                Con más de una, el POS y los cobros del taller muestran botones para elegir cómo paga el cliente.
                En el cierre de caja se cuenta solo el efectivo; lo demás aparece aparte.
            </div>
            @error('payment_methods')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold d-block">Gastos con lo cobrado por QR / transferencia</label>
            <input type="hidden" name="expenses_use_other_methods" value="0" {{ $disabled ? 'disabled' : '' }}>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="expenses_use_other_methods"
                       name="expenses_use_other_methods" value="1" {{ $otherPay ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}>
                <label class="form-check-label" for="expenses_use_other_methods">Usarlo para pagar gastos desde la caja</label>
            </div>
            <div class="form-text">
                Ej.: cobraste {{ $symbol }} 100 en efectivo y {{ $symbol }} 100 por QR. Un gasto de {{ $symbol }} 150
                se registra {{ $symbol }} 100 en efectivo + {{ $symbol }} 50 por QR, y el cierre queda exacto.
                Apagado: solo se puede gastar el efectivo del cajón.
            </div>
        </div>

        <div class="col-12">
            <label class="form-label small fw-semibold d-block">Datos que se ven en cada producto del POS</label>
            {{-- Vacío oculto: si se desmarcan todas, igual viaja la lista (vacía). --}}
            <input type="hidden" name="pos_card_fields[]" value="" {{ $disabled ? 'disabled' : '' }}>
            <div class="row g-3 align-items-start">
                <div class="col-md-7">
                    <div class="d-flex flex-wrap gap-3">
                        @foreach(\App\Models\Company::POS_CARD_FIELDS as $key => $label)
                        <div class="form-check">
                            <input class="form-check-input pos-card-field" type="checkbox" id="pcf_{{ $key }}"
                                   name="pos_card_fields[]" value="{{ $key }}" data-field="{{ $key }}"
                                   {{ in_array($key, $cardFields, true) ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}>
                            <label class="form-check-label" for="pcf_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                    <div class="form-text">
                        Vale para el POS de la web y de la app. La ubicación es la del almacén de la sucursal
                        de la caja (ej. PA5-VI2). «Sin stock» se avisa siempre.
                    </div>
                    @error('pos_card_fields')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    {{-- Vista previa: tarjeta de ejemplo que sigue a las casillas. --}}
                    <div class="small text-muted mb-1">Vista previa</div>
                    <div class="border rounded-3 p-2 bg-body" style="max-width:220px;" id="posCardPreview">
                        <div class="fw-semibold" style="font-size:.8rem;">Pastillas de freno</div>
                        <div class="mb-1 d-flex flex-wrap gap-1">
                            <span class="badge bg-light text-dark border" style="font-size:.6rem;" data-show="code"><i class="bi bi-upc me-1"></i>FR-120</span>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size:.62rem;" data-show="location"><i class="bi bi-geo-alt-fill text-danger me-1"></i>PA5-VI2</span>
                        </div>
                        <div class="text-muted mb-1" style="font-size:.66rem;">
                            <span data-show="brand" data-sep>Bosch</span><span data-show="category" data-sep>Frenos</span><span data-show="models" data-sep>CG 150, Pulsar 200</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-primary" style="font-size:.85rem;">{{ $symbol }} 120,00</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem;" data-show="stock">15 en stock</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    // Vista previa de la tarjeta del POS según las casillas marcadas.
    (function () {
        const box = document.getElementById('posCardPreview');
        if (!box) return;
        const sync = function () {
            const on = new Set(Array.from(document.querySelectorAll('.pos-card-field:checked')).map(function (c) { return c.dataset.field; }));
            let first = true;
            box.querySelectorAll('[data-show]').forEach(function (el) {
                const show = on.has(el.dataset.show);
                el.classList.toggle('d-none', !show);
                // Separador " · " entre marca, categoría y modelos visibles.
                if (el.hasAttribute('data-sep')) {
                    el.textContent = el.textContent.replace(/^ · /, '');
                    if (show) { if (!first) el.textContent = ' · ' + el.textContent; first = false; }
                }
            });
        };
        document.querySelectorAll('.pos-card-field').forEach(function (c) { c.addEventListener('change', sync); });
        sync();
    })();
</script>
@endpush
@endonce

@once
@push('scripts')
<script>
    // Alta/edición: si cambian la moneda arriba, las opciones de redondeo la siguen.
    (function () {
        const cur = document.getElementById('currency');
        const sel = document.getElementById('pos_rounding_step');
        if (!cur || !sel) return;
        cur.addEventListener('input', function () {
            const sym = cur.value.trim() || @json(config('inventory.currency', 'Bs'));
            Array.from(sel.options).forEach(function (o) { o.textContent = sym + ' ' + o.dataset.amount; });
        });
    })();
</script>
@endpush
@endonce
