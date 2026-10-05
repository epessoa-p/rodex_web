@extends('layouts.app')

@section('title', 'Cargar productos en tabla')

@section('page')
@php
    $companyId  = auth()->user()?->getCurrentCompany()?->id ?? 0;
    $singleWh   = $warehouses->count() === 1 ? $warehouses->first() : null;
@endphp
<div class="container-fluid">

    {{-- ── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5">
                <i class="bi bi-cloud-upload me-2 text-danger"></i>Importar productos
            </h1>
            <p class="text-muted mb-0 small">Carga muchos productos de una vez y revisa antes de guardar.</p>
        </div>
        <a href="{{ route('inventory.stock') }}" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver al inventario
        </a>
    </div>

    @include('inventory.stock._import-modes', ['active' => 'table'])

    {{-- Borrador recuperable --}}
    <div id="draftBanner" class="alert alert-warning border-0 shadow-sm d-none d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-clock-history fs-5"></i>
        <span class="flex-grow-1">Tienes <strong id="draftCount">0</strong> filas sin guardar de la última vez.</span>
        <button type="button" class="btn btn-sm btn-dark" id="draftRestore"><i class="bi bi-arrow-counterclockwise me-1"></i>Recuperar</button>
        <button type="button" class="btn btn-sm btn-light border" id="draftDiscard">Descartar</button>
    </div>

    {{-- ── TABLA ──────────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm" id="gridCard">
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2 me-auto">
                <i class="bi bi-warehouse text-muted"></i>
                @if($singleWh)
                    <input type="hidden" id="warehouse" value="{{ $singleWh->id }}">
                    <span class="small">Se guarda en <strong>{{ $singleWh->name }}</strong></span>
                @else
                    <label for="warehouse" class="small text-muted mb-0">Almacén</label>
                    <select id="warehouse" class="form-select form-select-sm" style="width:auto;min-width:180px;" data-no-search>
                        <option value="">Elige el almacén…</option>
                        @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                    </select>
                @endif
            </div>
            <div class="form-check form-switch mb-0 small">
                <input class="form-check-input" type="checkbox" role="switch" id="toggleExtra">
                <label class="form-check-label" for="toggleExtra">Más columnas</label>
            </div>
            <button type="button" class="btn btn-sm btn-light border" id="btnAddRows" title="Agregar 5 filas vacías">
                <i class="bi bi-plus-lg me-1"></i>Filas
            </button>
            <button type="button" class="btn btn-sm btn-light border text-danger" id="btnClear">
                <i class="bi bi-eraser me-1"></i>Vaciar
            </button>
        </div>

        <div class="gt-tips small text-muted px-3 py-2 border-bottom">
            <span><kbd>Enter</kbd> baja a la fila siguiente</span>
            <span><kbd>Tab</kbd> pasa a la otra columna</span>
            <span><kbd>Ctrl</kbd>+<kbd>V</kbd> pega varias filas copiadas de Excel, Google Sheets o WhatsApp</span>
            <span class="text-muted">Categoría, marca y unidad nuevas se crean solas.</span>
        </div>

        <div class="table-responsive gt-wrap">
            <table class="table mb-0 gt" id="grid">
                <thead><tr id="gridHead"></tr></thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>

        {{-- Pie: resumen en vivo + guardar --}}
        <div class="card-footer bg-white border-top py-3 px-3 d-flex align-items-center gap-3 flex-wrap gt-foot">
            <div class="gt-kpi"><span>Productos</span><strong id="kCount">0</strong></div>
            <div class="gt-kpi"><span>Unidades</span><strong id="kUnits">0</strong></div>
            <div class="gt-kpi"><span>Valor a costo</span><strong id="kCost">{{ currency_symbol() }} 0.00</strong></div>
            <div class="gt-kpi"><span>Valor a venta</span><strong id="kPrice">{{ currency_symbol() }} 0.00</strong></div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="small text-danger d-none" id="kErrors"></span>
                <button type="button" class="btn btn-primary px-4" id="btnSave" disabled>
                    <i class="bi bi-check2-circle me-1"></i><span id="btnSaveLabel">Guardar</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── RESULTADO ──────────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm d-none" id="resultCard">
        <div class="card-body p-5 text-center">
            <div class="gt-ok mb-3"><i class="bi bi-check-lg"></i></div>
            <h5 class="fw-bold mb-1">¡Productos guardados!</h5>
            <p class="text-muted mb-3" id="resultText"></p>
            <div id="resultErrors" class="text-start mx-auto d-none" style="max-width:560px;"></div>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <a href="{{ route('inventory.stock') }}" class="btn btn-primary"><i class="bi bi-box-seam me-1"></i>Ver inventario</a>
                <a href="{{ route('inventory.stock.import.table') }}" class="btn btn-light border"><i class="bi bi-plus-lg me-1"></i>Cargar más</a>
            </div>
        </div>
    </div>

    {{-- Autocompletar con los catálogos de la empresa --}}
    <datalist id="dlCategory">@foreach($categories as $v)<option value="{{ $v }}"></option>@endforeach</datalist>
    <datalist id="dlBrand">@foreach($brands as $v)<option value="{{ $v }}"></option>@endforeach</datalist>
    <datalist id="dlUnit">@foreach($units as $v)<option value="{{ $v }}"></option>@endforeach</datalist>
    <datalist id="dlOrigin">@foreach($origins as $v)<option value="{{ $v }}"></option>@endforeach</datalist>
</div>

{{-- Confirmación --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="gt-ok gt-ok-soft mb-3"><i class="bi bi-box-seam"></i></div>
                <h6 class="fw-bold mb-2" id="confTitle"></h6>
                <p class="small text-muted mb-0" id="confText"></p>
                <div class="alert alert-warning small mt-3 mb-0 text-start d-none" id="confWarn">
                    <div class="d-flex gap-2"><i class="bi bi-exclamation-triangle-fill"></i><span id="confWarnText"></span></div>
                    <button type="button" class="btn btn-sm btn-warning mt-2" id="confFix"><i class="bi bi-pencil me-1"></i>Completar ahora</button>
                </div>
                <div class="alert alert-danger small mt-3 mb-0 d-none" id="confError"></div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Seguir editando</button>
                <button type="button" class="btn btn-primary px-4" id="confGo">
                    <i class="bi bi-check2 me-1"></i>Sí, guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.gt-tips { display:flex; flex-wrap:wrap; gap:.4rem 1.1rem; background:#fafbfc; }
.gt-tips kbd { font-size:.68rem; padding:.05rem .3rem; background:#fff; color:#495057; border:1px solid #dee2e6; box-shadow:0 1px 0 #dee2e6; }
.gt-wrap { max-height:62vh; }
.gt { font-size:.84rem; border-collapse:separate; border-spacing:0; }
.gt thead th {
    position:sticky; top:0; z-index:3; background:#f8f9fa; white-space:nowrap;
    font-size:.68rem; text-transform:uppercase; letter-spacing:.03em; color:#6c757d;
    border-bottom:1px solid #dee2e6; padding:.55rem .5rem; font-weight:600;
}
.gt thead th .req { color:#dc3545; }
.gt td { padding:0; border-bottom:1px solid #f1f3f5; border-right:1px solid #f1f3f5; vertical-align:middle; background:#fff; }
.gt td.gt-num { width:42px; text-align:center; color:#adb5bd; font-size:.72rem; background:#fafbfc; position:sticky; left:0; z-index:1; }
.gt td.gt-st { width:150px; padding:0 .5rem; white-space:nowrap; }
.gt td.gt-del { width:34px; text-align:center; }
.gt input.gt-in {
    width:100%; border:0; outline:none; background:transparent;
    padding:.5rem .55rem; font-size:.84rem; min-width:var(--w,90px);
}
.gt input.gt-in.num { text-align:right; font-variant-numeric:tabular-nums; }
.gt td:focus-within { box-shadow:inset 0 0 0 2px var(--bs-primary,#0d6efd); background:#f5f9ff; }
.gt tr.has-error td:not(.gt-num) { background:#fff7f7; }
.gt tr:hover .gt-del button { opacity:1; }
.gt .gt-del button { opacity:.25; border:0; background:none; color:#dc3545; padding:.2rem .4rem; }
.gt td.miss-err  { box-shadow:inset 0 0 0 1.5px rgba(220,53,69,.55); background:#fff1f1 !important; }
.gt td.miss-warn { box-shadow:inset 0 0 0 1.5px rgba(245,158,11,.6); background:#fffaf0 !important; }
.gt-suggest { position:absolute; z-index:1060; background:#fff; border:1px solid #dee2e6; border-radius:.5rem; box-shadow:0 8px 24px rgba(0,0,0,.12); padding:.25rem; font-size:.82rem; max-height:260px; overflow:auto; }
.gt-sug-item { padding:.35rem .6rem; border-radius:.35rem; cursor:pointer; }
.gt-sug-item.active, .gt-sug-item:hover { background:#e7f1ff; color:#0b5ed7; }
.gt-sug-hint { font-size:.68rem; color:#adb5bd; padding:.25rem .6rem 0; border-top:1px solid #f1f3f5; margin-top:.25rem; }
.gt .extra { display:none; }
.gt.show-extra .extra { display:table-cell; }
.st-pill { display:inline-flex; align-items:center; gap:.25rem; font-size:.68rem; font-weight:600; padding:.15rem .5rem; border-radius:999px; }
.st-new  { background:#e8f5e9; color:#1b7a3a; }
.st-upd  { background:#e7f1ff; color:#0b5ed7; }
.st-warn { background:#fff4e5; color:#b45309; }
.st-err  { background:#fdecec; color:#c62828; }
.gt-foot { position:sticky; bottom:0; z-index:4; }
.gt-kpi { display:flex; flex-direction:column; line-height:1.15; }
.gt-kpi span { font-size:.68rem; color:#6c757d; text-transform:uppercase; letter-spacing:.03em; }
.gt-kpi strong { font-size:1rem; font-variant-numeric:tabular-nums; }
.gt-ok { width:64px; height:64px; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:2rem; color:#fff; background:#198754; }
.gt-ok-soft { width:52px; height:52px; font-size:1.4rem; background:rgba(13,110,253,.1); color:#0d6efd; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const CONFIRM_URL = @json(route('inventory.stock.import.confirm'));
    const CSRF        = @json(csrf_token());
    const EXISTING    = new Set(@json($existing));
    const DEF_UNIT    = @json(mb_strtoupper(config('inventory.default_unit', 'Unidad')));
    const MODELS      = @json($models);
    const DRAFT_KEY   = 'rodex.importTable.' + @json($companyId);
    const XTRA_KEY    = 'rodex.importTable.extra';
    const money = n => (window.money ? window.money(n, 2) : Number(n || 0).toFixed(2));

    // Columnas: como la plantilla Excel. extra = se muestran con "Más columnas".
    const COLS = [
        { key: 'name',     label: 'Nombre del producto', req: true, upper: true, w: 230 },
        { key: 'price',    label: 'Precio de venta', req: true, num: true, w: 100 },
        { key: 'cost',     label: 'Precio de compra', num: true, w: 100 },
        { key: 'qty',      label: 'Stock', num: true, w: 70 },
        { key: 'category', label: 'Categoría', upper: true, list: 'dlCategory', w: 130 },
        { key: 'brand',    label: 'Marca', upper: true, list: 'dlBrand', w: 120 },
        { key: 'unit',     label: 'Unidad', upper: true, list: 'dlUnit', w: 90 },
        { key: 'code',     label: 'Código de referencia', upper: true, w: 130 },
        { key: 'origin',   label: 'Origen', upper: true, list: 'dlOrigin', w: 100, extra: true },
        { key: 'models',   label: 'Modelos compatibles', upper: true, multi: true, w: 190, extra: true, hint: 'Ej. CG 150, COROLLA' },
        { key: 'notes',    label: 'Descripción', w: 180, extra: true },
    ];
    const KEYS = COLS.map(c => c.key);

    const grid  = document.getElementById('grid');
    const head  = document.getElementById('gridHead');
    const body  = document.getElementById('gridBody');
    let rows = [];          // estado: [{name, price, ...}]
    let saveTimer = null;

    const blank = () => Object.assign(Object.fromEntries(KEYS.map(k => [k, ''])), { unit: DEF_UNIT });
    // La unidad por defecto no cuenta: una fila solo con "UNIDAD" sigue vacía.
    const isEmpty = r => KEYS.every(k => {
        const v = String(r[k] ?? '').trim();
        return v === '' || (k === 'unit' && v.toUpperCase() === DEF_UNIT);
    });

    /** "1.234,50" / "1,234.50" / "Bs 12,5" → número (o null si está vacío). */
    function parseNum(v) {
        let s = String(v ?? '').trim().replace(/[^\d.,-]/g, '');
        if (s === '') return null;
        if (s.includes(',') && s.includes('.')) {
            s = s.lastIndexOf(',') > s.lastIndexOf('.') ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (s.includes(',')) {
            s = s.replace(',', '.');
        }
        const n = parseFloat(s);
        return isNaN(n) ? null : n;
    }

    // ── Dibujo ─────────────────────────────────────────────────────────
    head.innerHTML = '<th class="text-center">#</th>' + COLS.map(c =>
        '<th class="' + (c.extra ? 'extra' : '') + (c.num ? ' text-end' : '') + '" title="' + (c.hint || '') + '">' +
        c.label + (c.req ? ' <span class="req">*</span>' : '') + '</th>').join('') +
        '<th>Estado</th><th></th>';

    function cellHtml(c, r, i) {
        const v = String(r[c.key] ?? '').replace(/"/g, '&quot;');
        return '<td class="' + (c.extra ? 'extra' : '') + '"><input class="gt-in' + (c.num ? ' num' : '') + '"' +
            ' style="--w:' + c.w + 'px" data-r="' + i + '" data-k="' + c.key + '" value="' + v + '"' +
            (c.list ? ' list="' + c.list + '"' : '') + (c.num ? ' inputmode="decimal"' : '') +
            (c.hint ? ' placeholder="' + c.hint + '"' : '') + ' autocomplete="off"></td>';
    }

    function rowHtml(r, i) {
        return '<tr data-r="' + i + '"><td class="gt-num">' + (i + 1) + '</td>' +
            COLS.map(c => cellHtml(c, r, i)).join('') +
            '<td class="gt-st"></td><td class="gt-del"><button type="button" tabindex="-1" title="Quitar fila" data-del="' + i + '"><i class="bi bi-x-lg"></i></button></td></tr>';
    }

    function render() {
        body.innerHTML = rows.map(rowHtml).join('');
        refresh();
    }

    function addRows(n) {
        const start = rows.length;
        for (let k = 0; k < n; k++) rows.push(blank());
        body.insertAdjacentHTML('beforeend', rows.slice(start).map((r, j) => rowHtml(r, start + j)).join(''));
    }

    /** Siempre hay al menos una fila vacía al final para seguir escribiendo. */
    function ensureTail() {
        if (rows.length === 0 || !isEmpty(rows[rows.length - 1])) addRows(1);
    }

    // ── Estado por fila + resumen ──────────────────────────────────────
    function rowStatus(r, dupes) {
        if (isEmpty(r)) return null;
        const name = String(r.name).trim();
        if (!name) return { cls: 'st-err', icon: 'x-circle', text: 'Falta el nombre', error: true };
        const key = name.toLowerCase();
        if (dupes.has(key)) return { cls: 'st-warn', icon: 'exclamation-triangle', text: 'Repetido', error: true };
        // Ya existe: los vacíos no se tocan, así que no se exige nada más.
        if (EXISTING.has(key)) return { cls: 'st-upd', icon: 'arrow-repeat', text: 'Ya existe: se actualiza' };
        if (parseNum(r.price) === null) return { cls: 'st-err', icon: 'x-circle', text: 'Falta el precio', error: true, miss: ['price'] };
        // Sin costo se puede guardar, pero la ganancia saldría mal: se avisa.
        if (parseNum(r.cost) === null) return { cls: 'st-warn', icon: 'exclamation-circle', text: 'Sin precio de compra', miss: ['cost'], noCost: true };
        return { cls: 'st-new', icon: 'plus-circle', text: 'Nuevo' };
    }

    function refresh() {
        const seen = new Map();
        rows.forEach(r => { const k = String(r.name).trim().toLowerCase(); if (k) seen.set(k, (seen.get(k) || 0) + 1); });
        const dupes = new Set([...seen].filter(([, n]) => n > 1).map(([k]) => k));

        let count = 0, errs = 0, units = 0, cost = 0, price = 0, nNew = 0, nUpd = 0, noCost = 0;
        rows.forEach((r, i) => {
            const st  = rowStatus(r, dupes);
            const tr  = body.querySelector('tr[data-r="' + i + '"]');
            if (!tr) return;
            tr.classList.toggle('has-error', !!(st && st.error));
            // Resalta la celda que falta (rojo = obligatoria, ámbar = aviso).
            ['price', 'cost'].forEach(function (k) {
                const td = tr.querySelector('input[data-k="' + k + '"]').closest('td');
                const miss = !!(st && st.miss && st.miss.includes(k));
                td.classList.toggle('miss-err', miss && !!st.error);
                td.classList.toggle('miss-warn', miss && !st.error);
            });
            tr.querySelector('.gt-st').innerHTML = st
                ? '<span class="st-pill ' + st.cls + '"><i class="bi bi-' + st.icon + '"></i>' + st.text + '</span>' : '';
            if (!st) return;
            if (st.error) { errs++; return; }
            count++;
            if (st.noCost) noCost++;
            st.cls === 'st-upd' ? nUpd++ : nNew++;
            const q = parseNum(r.qty) || 0;
            units += q; cost += q * (parseNum(r.cost) || 0); price += q * (parseNum(r.price) || 0);
        });

        document.getElementById('kCount').textContent = count;
        document.getElementById('kUnits').textContent = units.toLocaleString('es');
        document.getElementById('kCost').textContent  = money(cost);
        document.getElementById('kPrice').textContent = money(price);
        const kErr = document.getElementById('kErrors');
        kErr.classList.toggle('d-none', errs === 0);
        kErr.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Corrige ' + errs + ' fila' + (errs === 1 ? '' : 's');
        const btn = document.getElementById('btnSave');
        btn.disabled = count === 0 || errs > 0;
        document.getElementById('btnSaveLabel').textContent =
            count ? 'Guardar ' + count + ' producto' + (count === 1 ? '' : 's') : 'Guardar';
        btn.dataset.new = nNew; btn.dataset.upd = nUpd; btn.dataset.nocost = noCost;
        scheduleDraft();
    }

    // ── Edición ────────────────────────────────────────────────────────
    body.addEventListener('input', function (e) {
        const el = e.target; if (!el.classList.contains('gt-in')) return;
        const col = COLS.find(c => c.key === el.dataset.k);
        if (col && col.upper) {
            const p = el.selectionStart; el.value = el.value.toUpperCase(); el.setSelectionRange(p, p);
        }
        rows[+el.dataset.r][el.dataset.k] = el.value;
        ensureTail();
        refresh();
        if (col && col.multi) showSuggest(el); else hideSuggest();
    });

    body.addEventListener('click', function (e) {
        const b = e.target.closest('[data-del]'); if (!b) return;
        rows.splice(+b.dataset.del, 1);
        if (rows.length === 0) rows.push(blank());
        render(); ensureTail(); refresh();
    });

    // ── Modelos compatibles: una sugerencia por cada modelo ─────────────
    // El <datalist> del navegador solo sugiere para todo el texto; aquí se
    // sugiere el modelo que se está escribiendo después de la última coma.
    const sug = document.createElement('div');
    sug.className = 'gt-suggest d-none';
    document.body.appendChild(sug);
    let sugEl = null, sugIdx = 0, sugItems = [];

    function currentToken(v) { return v.split(',').pop().trim().toUpperCase(); }

    function showSuggest(el) {
        const chosen = el.value.split(',').slice(0, -1).map(t => t.trim().toUpperCase()).filter(Boolean);
        const tok = currentToken(el.value);
        sugItems = MODELS.filter(m => !chosen.includes(m.toUpperCase()) && (!tok || m.toUpperCase().includes(tok))).slice(0, 8);
        if (!sugItems.length) { hideSuggest(); return; }
        sugEl = el; sugIdx = 0;
        sug.innerHTML = sugItems.map((m, i) => '<div class="gt-sug-item' + (i === 0 ? ' active' : '') + '" data-i="' + i + '">' +
            m.replace(/</g, '&lt;') + '</div>').join('') +
            '<div class="gt-sug-hint">Enter elige · sigue con una coma</div>';
        const r = el.getBoundingClientRect();
        sug.style.left = (r.left + window.scrollX) + 'px';
        sug.style.top = (r.bottom + window.scrollY + 2) + 'px';
        sug.style.minWidth = r.width + 'px';
        sug.classList.remove('d-none');
    }

    function hideSuggest() { sug.classList.add('d-none'); sugEl = null; }

    function pickSuggest(i) {
        if (!sugEl || !sugItems[i]) return;
        const parts = sugEl.value.split(',').slice(0, -1).map(t => t.trim()).filter(Boolean);
        parts.push(sugItems[i]);
        sugEl.value = parts.join(', ') + ', ';
        rows[+sugEl.dataset.r][sugEl.dataset.k] = sugEl.value;
        const el = sugEl; hideSuggest(); refresh();
        el.focus(); el.setSelectionRange(el.value.length, el.value.length);
        showSuggest(el);   // listo para el siguiente modelo
    }

    function suggestKeydown(e) {
        if (!sugEl || e.target !== sugEl || sug.classList.contains('d-none')) return false;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            sugIdx = (sugIdx + (e.key === 'ArrowDown' ? 1 : sugItems.length - 1)) % sugItems.length;
            sug.querySelectorAll('.gt-sug-item').forEach((d, i) => d.classList.toggle('active', i === sugIdx));
            return true;
        }
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (!currentToken(sugEl.value) && e.key === 'Tab') { hideSuggest(); return false; }
            e.preventDefault(); pickSuggest(sugIdx); return true;
        }
        if (e.key === 'Escape') { hideSuggest(); return true; }
        return false;
    }

    sug.addEventListener('mousedown', function (e) {
        const it = e.target.closest('.gt-sug-item'); if (!it) return;
        e.preventDefault(); pickSuggest(+it.dataset.i);
    });
    body.addEventListener('focusin', function (e) {
        const col = COLS.find(c => c.key === e.target.dataset?.k);
        if (col && col.multi) showSuggest(e.target); else hideSuggest();
    });
    body.addEventListener('focusout', function () { setTimeout(() => { if (!sug.matches(':hover')) hideSuggest(); }, 120); });
    document.querySelector('.gt-wrap').addEventListener('scroll', hideSuggest);

    /** Mueve el foco a la celda (fila r, misma columna k); crea filas si hace falta. */
    function focusCell(r, k) {
        while (r >= rows.length) addRows(1);
        const el = body.querySelector('input[data-r="' + r + '"][data-k="' + k + '"]');
        if (el) { el.focus(); el.select(); }
    }

    body.addEventListener('keydown', function (e) {
        const el = e.target; if (!el.classList.contains('gt-in')) return;
        if (suggestKeydown(e)) return;
        const r = +el.dataset.r, k = el.dataset.k;
        // Las flechas en columnas con autocompletar se dejan para elegir sugerencias.
        if (e.key === 'Enter' || (e.key === 'ArrowDown' && !el.list)) {
            e.preventDefault(); focusCell(r + 1, k);
        } else if (e.key === 'ArrowUp' && !el.list) {
            e.preventDefault(); if (r > 0) focusCell(r - 1, k);
        }
    });

    // ── Pegar varias filas/columnas (Excel, Google Sheets, WhatsApp…) ──
    body.addEventListener('paste', function (e) {
        const el = e.target; if (!el.classList.contains('gt-in')) return;
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!/[\t\n]/.test(text)) return;                           // un solo valor: pegado normal
        e.preventDefault();
        const lines = text.replace(/\r/g, '').split('\n');
        if (lines.length && lines[lines.length - 1] === '') lines.pop();
        const visible = COLS.filter(c => !c.extra || grid.classList.contains('show-extra')).map(c => c.key);
        const startC  = visible.indexOf(el.dataset.k);
        const startR  = +el.dataset.r;
        lines.forEach((line, i) => {
            while (startR + i >= rows.length) rows.push(blank());
            line.split('\t').forEach((val, j) => {
                const key = visible[startC + j]; if (!key) return;
                const col = COLS.find(c => c.key === key);
                rows[startR + i][key] = col.upper ? val.trim().toUpperCase() : val.trim();
            });
        });
        render(); ensureTail(); refresh();
        window.showToast?.(lines.length + ' fila' + (lines.length === 1 ? '' : 's') + ' pegada' + (lines.length === 1 ? '' : 's') + '.', 'success');
        focusCell(startR + lines.length, el.dataset.k);
    });

    // ── Barra ──────────────────────────────────────────────────────────
    const toggleExtra = document.getElementById('toggleExtra');
    try { toggleExtra.checked = localStorage.getItem(XTRA_KEY) === '1'; } catch (e) {}
    grid.classList.toggle('show-extra', toggleExtra.checked);
    toggleExtra.addEventListener('change', function () {
        grid.classList.toggle('show-extra', this.checked);
        try { localStorage.setItem(XTRA_KEY, this.checked ? '1' : '0'); } catch (e) {}
    });
    document.getElementById('btnAddRows').addEventListener('click', () => { addRows(5); refresh(); });
    document.getElementById('btnClear').addEventListener('click', function () {
        if (rows.some(r => !isEmpty(r)) && !confirm('¿Vaciar la tabla? Se borrará lo que escribiste.')) return;
        rows = []; addRows(8); render(); clearDraft();
    });

    // ── Borrador (solo en este navegador) ──────────────────────────────
    function filled() { return rows.filter(r => !isEmpty(r)); }
    function scheduleDraft() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(function () {
            try {
                const f = filled();
                f.length ? localStorage.setItem(DRAFT_KEY, JSON.stringify(f)) : localStorage.removeItem(DRAFT_KEY);
            } catch (e) {}
        }, 400);
    }
    function clearDraft() { try { localStorage.removeItem(DRAFT_KEY); } catch (e) {} }

    let draft = [];
    try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || '[]') || []; } catch (e) { draft = []; }
    const banner = document.getElementById('draftBanner');
    if (Array.isArray(draft) && draft.length) {
        document.getElementById('draftCount').textContent = draft.length;
        banner.classList.remove('d-none');
    }
    document.getElementById('draftRestore').addEventListener('click', function () {
        rows = draft.map(d => Object.assign(blank(), d)); render(); ensureTail(); refresh();
        banner.classList.add('d-none');
    });
    document.getElementById('draftDiscard').addEventListener('click', function () {
        clearDraft(); banner.classList.add('d-none');
    });

    // ── Guardar ────────────────────────────────────────────────────────
    const modalEl = document.getElementById('confirmModal');
    document.getElementById('btnSave').addEventListener('click', function () {
        const wh = document.getElementById('warehouse');
        if (!wh.value) { wh.focus(); window.showToast?.('Elige el almacén donde se guardará el stock.', 'warning'); return; }
        const nNew = +this.dataset.new, nUpd = +this.dataset.upd;
        document.getElementById('confTitle').textContent =
            '¿Guardar ' + (nNew + nUpd) + ' producto' + (nNew + nUpd === 1 ? '' : 's') + '?';
        document.getElementById('confText').innerHTML =
            (nNew ? '<strong>' + nNew + '</strong> nuevo' + (nNew === 1 ? '' : 's') : '') +
            (nNew && nUpd ? ' y ' : '') +
            (nUpd ? '<strong>' + nUpd + '</strong> que ya existe' + (nUpd === 1 ? '' : 'n') + ' (solo se cambia lo que escribiste; el stock, solo si pusiste cantidad)' : '') + '.';
        document.getElementById('confError').classList.add('d-none');
        const noCost = +this.dataset.nocost;
        const warn = document.getElementById('confWarn');
        warn.classList.toggle('d-none', noCost === 0);
        document.getElementById('confWarnText').textContent =
            noCost + ' producto' + (noCost === 1 ? ' no tiene' : 's no tienen') +
            ' precio de compra: se guardará' + (noCost === 1 ? '' : 'n') + ' con costo 0 y la ganancia saldrá mal.';
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    document.getElementById('confGo').addEventListener('click', function () {
        const btn = this, original = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';
        const payload = filled().filter(r => String(r.name).trim() !== '').map(r => ({
            name: String(r.name).trim(), price: parseNum(r.price), cost: parseNum(r.cost), qty: parseNum(r.qty),
            category: r.category.trim(), brand: r.brand.trim(), unit: r.unit.trim(), code: r.code.trim() || null,
            origin: r.origin.trim(), models: r.models.split(',').map(t => t.trim()).filter(Boolean).join(', '), notes: r.notes.trim(),
        }));
        fetch(CONFIRM_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ warehouse_id: document.getElementById('warehouse').value, mode: 'table', rows: payload }),
        })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(function (res) {
                if (!res.ok || !res.d.ok) {
                    const err = document.getElementById('confError');
                    err.textContent = res.d.message || 'No se pudo guardar.';
                    err.classList.remove('d-none');
                    return;
                }
                bootstrap.Modal.getInstance(modalEl)?.hide();
                clearDraft();
                document.getElementById('gridCard').classList.add('d-none');
                document.getElementById('resultCard').classList.remove('d-none');
                document.getElementById('resultText').textContent =
                    res.d.created + ' creado' + (res.d.created === 1 ? '' : 's') + ' · ' +
                    res.d.updated + ' actualizado' + (res.d.updated === 1 ? '' : 's');
                if ((res.d.errors || []).length) {
                    const box = document.getElementById('resultErrors');
                    box.innerHTML = '<div class="alert alert-warning small mb-0"><strong>Con problemas:</strong><ul class="mb-0 mt-1">' +
                        res.d.errors.map(e => '<li>' + String(e).replace(/</g, '&lt;') + '</li>').join('') + '</ul></div>';
                    box.classList.remove('d-none');
                }
            })
            .catch(function () {
                const err = document.getElementById('confError');
                err.textContent = 'Error de conexión. Tus filas siguen guardadas en este navegador.';
                err.classList.remove('d-none');
            })
            .finally(function () { btn.disabled = false; btn.innerHTML = original; });
    });

    document.getElementById('confFix').addEventListener('click', function () {
        bootstrap.Modal.getInstance(modalEl)?.hide();
        const td = body.querySelector('td.miss-warn');
        if (td) setTimeout(() => { td.scrollIntoView({ block: 'center' }); td.querySelector('input').focus(); }, 250);
    });

    // Inicio: 8 filas vacías, foco en la primera celda.
    addRows(8); refresh();
    setTimeout(() => focusCell(0, 'name'), 50);
})();
</script>
@endpush
