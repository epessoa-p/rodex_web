@extends('layouts.app')

@section('title', 'Etiquetas de productos')

@section('page')
<div class="container-fluid">

    {{-- ── Configuración (no se imprime) ── --}}
    <div class="no-print">
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-upc-scan me-2 text-primary"></i>Etiquetas de productos</h1>
                <p class="text-muted mb-0 small">
                    Genera etiquetas con código escaneable para pegar en el estante.
                    El escáner del móvil las reconoce por <strong>código de barras</strong> o por <strong>SKU</strong>.
                </p>
            </div>
            <a href="{{ route('products.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left"></i> Volver a Productos
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="form-label small fw-semibold" for="productPicker">Agregar productos</label>
                        <select id="productPicker" class="form-select">
                            <option value="">Busca por nombre, SKU o código…</option>
                            @foreach($products as $p)
                                @php $code = $p->barcode ?: $p->sku; @endphp
                                <option value="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-sku="{{ $p->sku }}"
                                        data-barcode="{{ $p->barcode }}"
                                        data-price="{{ number_format($p->price, 2) }}"
                                        @disabled(!$code)>
                                    {{ $p->name }}{{ $code ? " · {$code}" : ' · (sin código)' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Se agrega al tocarlo. Los productos sin SKU ni código de barras no se pueden etiquetar.</div>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold" for="optFormat">Formato</label>
                        <select id="optFormat" class="form-select">
                            <option value="qr">QR</option>
                            <option value="barcode">Código de barras</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold" for="optSource">Codificar</label>
                        <select id="optSource" class="form-select">
                            <option value="auto">Cód. barras (o SKU)</option>
                            <option value="sku">SKU siempre</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label small fw-semibold" for="optCols">Por fila</label>
                        <select id="optCols" class="form-select">
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-3 flex-wrap align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="optPrice" checked>
                        <label class="form-check-label small" for="optPrice">Mostrar precio</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="optName" checked>
                        <label class="form-check-label small" for="optName">Mostrar nombre</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="optText" checked>
                        <label class="form-check-label small" for="optText">Mostrar el código en texto</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seleccionados --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">Seleccionados (<span id="selCount">0</span>)</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border" id="btnClear">
                        <i class="bi bi-x-lg"></i> Vaciar
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnPrint" disabled>
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th style="width:170px;">Código a imprimir</th>
                            <th class="text-center" style="width:110px;">Copias</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="selBody">
                        <tr id="selEmpty"><td colspan="4" class="text-center text-muted py-4">
                            Aún no agregaste productos.
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Hoja de etiquetas (lo único que se imprime) ── --}}
    <div id="sheet" class="label-sheet"></div>
</div>
@endsection

@push('styles')
<style>
    .label-sheet {
        display: grid;
        gap: 4mm;
        grid-template-columns: repeat(3, 1fr);
    }
    .label {
        border: 1px dashed #bbb;
        border-radius: 4px;
        padding: 3mm;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1mm;
        break-inside: avoid;
        page-break-inside: avoid;
        background: #fff;
    }
    .label-name {
        font-size: 9pt;
        font-weight: 600;
        line-height: 1.15;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .label-price { font-size: 12pt; font-weight: 800; }
    .label-code  { font-size: 7.5pt; font-family: monospace; color: #444; word-break: break-all; }
    .label-img svg { display: block; margin: 0 auto; max-width: 100%; height: auto; }
    .label-img.qr svg { width: 22mm; height: 22mm; }

    @media print {
        /* Solo la hoja de etiquetas: fuera menú, barras y formulario. */
        .no-print, .app-sidebar, .app-topbar, .offcanvas, nav, footer { display: none !important; }
        .app-shell, .app-main, .container-fluid { display: block !important; margin: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
        .label { border: 1px dashed #ddd; }
        @page { margin: 8mm; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
(function () {
    // Estado: id -> { name, sku, barcode, price, copies }
    const selected = new Map();

    const $picker = $('#productPicker');
    const sheet   = document.getElementById('sheet');
    const selBody = document.getElementById('selBody');

    $picker.select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Busca por nombre, SKU o código…' });

    /** Valor que se codifica en la etiqueta, según la opción elegida. */
    function codeFor(item) {
        return document.getElementById('optSource').value === 'sku'
            ? (item.sku || '')
            : (item.barcode || item.sku || '');
    }

    function render() {
        renderSelection();
        renderSheet();
    }

    function renderSelection() {
        document.getElementById('selCount').textContent = selected.size;
        document.getElementById('btnPrint').disabled = selected.size === 0;

        if (selected.size === 0) {
            selBody.innerHTML = '<tr id="selEmpty"><td colspan="4" class="text-center text-muted py-4">Aún no agregaste productos.</td></tr>';
            return;
        }

        let html = '';
        selected.forEach(function (item, id) {
            html += '<tr>'
                + '<td><div class="fw-semibold">' + esc(item.name) + '</div></td>'
                + '<td><code>' + esc(codeFor(item) || '—') + '</code></td>'
                + '<td class="text-center"><input type="number" min="1" max="200" value="' + item.copies
                + '" class="form-control form-control-sm text-center mx-auto copies" data-id="' + id + '" style="width:80px;"></td>'
                + '<td class="text-end"><button type="button" class="btn btn-sm btn-light border text-danger remove" data-id="' + id + '"><i class="bi bi-trash"></i></button></td>'
                + '</tr>';
        });
        selBody.innerHTML = html;
    }

    function renderSheet() {
        const cols     = document.getElementById('optCols').value;
        const format   = document.getElementById('optFormat').value;
        const showName = document.getElementById('optName').checked;
        const showPrice= document.getElementById('optPrice').checked;
        const showText = document.getElementById('optText').checked;

        sheet.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
        sheet.innerHTML = '';

        selected.forEach(function (item) {
            const value = codeFor(item);
            if (!value) return;

            for (let i = 0; i < item.copies; i++) {
                const el = document.createElement('div');
                el.className = 'label';

                if (showName) {
                    const n = document.createElement('div');
                    n.className = 'label-name';
                    n.textContent = item.name;
                    el.appendChild(n);
                }

                const img = document.createElement('div');
                img.className = 'label-img ' + (format === 'qr' ? 'qr' : 'bc');
                el.appendChild(img);

                if (format === 'qr') {
                    const qr = qrcode(0, 'M');
                    qr.addData(value);
                    qr.make();
                    img.innerHTML = qr.createSvgTag({ scalable: true });
                } else {
                    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                    img.appendChild(svg);
                    try {
                        JsBarcode(svg, value, {
                            format: 'CODE128', displayValue: false,
                            height: 45, width: 1.6, margin: 0,
                        });
                    } catch (e) {
                        img.textContent = 'Código no válido';
                    }
                }

                if (showText) {
                    const c = document.createElement('div');
                    c.className = 'label-code';
                    c.textContent = value;
                    el.appendChild(c);
                }
                if (showPrice) {
                    const p = document.createElement('div');
                    p.className = 'label-price';
                    p.textContent = '{{ config('inventory.currency', 'Bs') }} ' + item.price;
                    el.appendChild(p);
                }

                sheet.appendChild(el);
            }
        });
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ── Eventos ───────────────────────────────────────────────
    $picker.on('select2:select', function (e) {
        const o  = e.params.data.element;
        const id = o.value;
        if (!selected.has(id)) {
            selected.set(id, {
                name:    o.dataset.name,
                sku:     o.dataset.sku,
                barcode: o.dataset.barcode,
                price:   o.dataset.price,
                copies:  1,
            });
        } else {
            selected.get(id).copies += 1;
        }
        $picker.val('').trigger('change');
        render();
    });

    selBody.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.remove');
        if (!btn) return;
        selected.delete(btn.dataset.id);
        render();
    });

    selBody.addEventListener('input', function (ev) {
        const inp = ev.target.closest('.copies');
        if (!inp) return;
        const n = Math.max(1, Math.min(200, parseInt(inp.value, 10) || 1));
        selected.get(inp.dataset.id).copies = n;
        renderSheet();
    });

    ['optFormat', 'optSource', 'optCols', 'optPrice', 'optName', 'optText'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', render);
    });

    document.getElementById('btnClear').addEventListener('click', function () {
        selected.clear();
        render();
    });

    document.getElementById('btnPrint').addEventListener('click', function () {
        window.print();
    });
})();
</script>
@endpush
