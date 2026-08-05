@extends('layouts.app')

@section('title', 'Migrar inventario desde Excel')

@section('page')
<div class="container-fluid">

    {{-- ── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-5">
                <i class="bi bi-cloud-upload me-2 text-danger"></i>Migrar inventario
            </h1>
            <p class="text-muted mb-0 small">
                Carga masiva con verificación previa: revisa y ajusta antes de confirmar.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory.stock.template') }}" class="btn btn-sm btn-light border">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>Descargar plantilla
            </a>
            <a href="{{ route('inventory.stock') }}" class="btn btn-sm btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver al inventario
            </a>
        </div>
    </div>

    {{-- ── STEPPER ───────────────────────────────────────────────────── --}}
    <div class="wiz-steps mb-4">
        @foreach(['Cargar archivo', 'Verificar y ajustar', 'Confirmar'] as $i => $label)
        <div class="wiz-step {{ $i === 0 ? 'active' : '' }}" data-step="{{ $i + 1 }}">
            <span class="wiz-num">{{ $i + 1 }}</span>
            <span class="wiz-label">{{ $label }}</span>
        </div>
        @if(!$loop->last)<div class="wiz-line"></div>@endif
        @endforeach
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- PASO 1: CARGAR                                                    --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="wizPanel1">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-upload me-2 text-muted"></i>Seleccionar archivo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="warehouse_id">Almacén destino <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select" data-no-search required>
                                <option value="" disabled selected>Selecciona el almacén destino…</option>
                                @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-muted mt-1" style="font-size:.78rem;">La columna "Cantidad" fijará el stock en este almacén.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Archivo Excel</label>
                            <div id="dropZone" class="rounded-3 p-5 text-center"
                                 style="border:2px dashed #ddd;cursor:pointer;background:#fafafa;transition:border-color .2s,background .2s;">
                                <i class="bi bi-file-earmark-spreadsheet d-block mb-2 text-muted opacity-50" style="font-size:2.4rem;"></i>
                                <div class="fw-semibold mb-1">Arrastra el archivo aquí</div>
                                <div class="text-muted small mb-3">o haz clic para seleccionarlo</div>
                                <input type="file" id="file" class="d-none" accept=".xlsx,.xls">
                                <button type="button" class="btn btn-light border px-4" onclick="document.getElementById('file').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Explorar archivos
                                </button>
                            </div>
                            <div id="fileSelected" class="mt-2 d-none">
                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-light">
                                    <i class="bi bi-file-earmark-spreadsheet text-success fs-5"></i>
                                    <span id="fileName" class="fw-semibold flex-grow-1 text-truncate" style="font-size:.88rem;"></span>
                                    <button type="button" class="btn btn-sm btn-light border" id="clearFile" title="Quitar archivo"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.78rem;">Formatos: .xlsx, .xls — Máx. 5 MB</div>
                        </div>

                        <div id="previewError" class="alert alert-danger d-none py-2 small mb-3"></div>

                        <button type="button" class="btn btn-primary w-100 py-2" id="btnVerify" disabled>
                            <span id="verIcon"><i class="bi bi-search me-1"></i></span>
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="verSpinner"></span>
                            <span id="verText">Selecciona un archivo para continuar</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-table me-2 text-muted"></i>Columnas del archivo</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light"><tr><th class="ps-4" style="width:46px;">Col.</th><th>Campo</th><th class="pe-4">Ejemplo</th></tr></thead>
                            <tbody>
                                @foreach([['A','Nombre producto *','(01) Carburador Trueno'],['B','Categoría','Carburación (999)'],['C','Marca','Trueno'],['D','Cantidad','25'],['E','Costo','104'],['F','Precio','140'],['G','Modelo(s)','CG150, CG200'],['H','Detalle','Incluye filtro'],['I','Código','CARB-010']] as $c)
                                <tr>
                                    <td class="ps-4"><span class="badge bg-dark" style="font-size:.72rem;">{{ $c[0] }}</span></td>
                                    <td class="fw-semibold small">{{ $c[1] }}</td>
                                    <td class="text-muted small pe-4">{{ $c[2] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-lightbulb me-2 text-warning"></i>Cómo funciona</h6>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-unstyled mb-0" style="font-size:.84rem;">
                            <li class="d-flex gap-2 mb-2"><i class="bi bi-1-circle text-primary mt-1"></i><span>Subes el Excel y lo <strong>verificas</strong> sin guardar nada.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="bi bi-2-circle text-primary mt-1"></i><span>Revisas y <strong>ajustas</strong> las filas en pantalla.</span></li>
                            <li class="d-flex gap-2"><i class="bi bi-3-circle text-primary mt-1"></i><span>Ves el <strong>resumen</strong> (unidades y valor) y <strong>confirmas</strong>.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- PASO 2: VERIFICAR Y AJUSTAR                                       --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="wizPanel2" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2 text-muted"></i>Revisar y ajustar
                    <span class="badge bg-light text-muted border ms-1" id="rowCountBadge">0 filas</span>
                </h6>
                <div class="input-group input-group-sm" style="width:240px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" style="font-size:.78rem;"></i></span>
                    <input type="text" id="prevSearch" class="form-control border-start-0 ps-1" placeholder="Buscar en filas…" autocomplete="off">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:60vh;">
                    <table class="table table-hover align-middle mb-0" style="font-size:.78rem;" id="prevTable">
                        <thead class="table-light position-sticky top-0" style="z-index:2;">
                            <tr>
                                <th class="ps-3 py-2" style="font-size:.66rem;">Estado</th>
                                <th class="py-2" style="font-size:.66rem;min-width:180px;">Nombre</th>
                                <th class="py-2" style="font-size:.66rem;min-width:120px;">Categoría</th>
                                <th class="py-2" style="font-size:.66rem;min-width:100px;">Marca</th>
                                <th class="py-2 text-center" style="font-size:.66rem;width:80px;">Cant.</th>
                                <th class="py-2 text-end" style="font-size:.66rem;width:90px;">Costo</th>
                                <th class="py-2 text-end" style="font-size:.66rem;width:90px;">Precio</th>
                                <th class="py-2" style="font-size:.66rem;min-width:120px;">Modelo(s)</th>
                                <th class="py-2" style="font-size:.66rem;min-width:120px;">Detalle</th>
                                <th class="py-2" style="font-size:.66rem;min-width:110px;">Código</th>
                                <th class="py-2 pe-3" style="width:32px;"></th>
                            </tr>
                        </thead>
                        <tbody id="prevBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top d-flex justify-content-between p-3">
                <button type="button" class="btn btn-light border btn-sm" id="btnBackTo1"><i class="bi bi-arrow-left me-1"></i>Atrás</button>
                <button type="button" class="btn btn-primary btn-sm px-3" id="btnToStep3">Continuar<i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- PASO 3: CONFIRMAR                                                 --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="wizPanel3" class="d-none">
        <div class="row g-2 mb-3">
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.66rem;">FILAS</div><div class="fw-bold fs-5" id="sumRows">0</div>
                </div></div>
            </div>
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-success" style="font-size:.66rem;"><i class="bi bi-plus-circle me-1"></i>NUEVOS</div><div class="fw-bold fs-5 text-success" id="sumNew">0</div>
                </div></div>
            </div>
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-primary" style="font-size:.66rem;"><i class="bi bi-arrow-repeat me-1"></i>ACTUALIZA</div><div class="fw-bold fs-5 text-primary" id="sumUpd">0</div>
                </div></div>
            </div>
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.66rem;"><i class="bi bi-stack me-1"></i>UNIDADES</div><div class="fw-bold fs-5" id="sumUnits">0</div>
                </div></div>
            </div>
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-muted" style="font-size:.66rem;">VALOR A COSTO</div><div class="fw-bold fs-6">Bs. <span id="sumCost">0.00</span></div>
                </div></div>
            </div>
            <div class="col-6 col-lg">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 px-3">
                    <div class="text-success" style="font-size:.66rem;">VALOR A VENTA</div><div class="fw-bold fs-6 text-success">Bs. <span id="sumPrice">0.00</span></div>
                </div></div>
            </div>
        </div>

        <div class="alert alert-info border-0 d-flex align-items-center gap-2" style="font-size:.85rem;">
            <i class="bi bi-info-circle-fill text-primary"></i>
            Destino: <strong id="confWarehouse">—</strong>. La cantidad <strong>fija</strong> el stock (reemplaza, no acumula).
        </div>

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-light border" id="btnBackTo2"><i class="bi bi-arrow-left me-1"></i>Atrás</button>
            <button type="button" class="btn btn-primary px-4" id="btnConfirm">
                <span id="confIcon"><i class="bi bi-check-lg me-1"></i></span>
                <span class="spinner-border spinner-border-sm me-1 d-none" id="confSpinner"></span>
                <span id="confText">Confirmar importación</span>
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════ --}}
    {{-- RESULTADO                                                         --}}
    {{-- ════════════════════════════════════════════════════════════════ --}}
    <div id="wizResult" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle bg-success-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;">
                    <i class="bi bi-check-lg text-success" style="font-size:1.8rem;"></i>
                </div>
                <h5 class="fw-bold mb-1">Importación completada</h5>
                <div class="d-flex justify-content-center gap-2 flex-wrap my-3">
                    <span class="badge bg-success fs-6 fw-normal px-3 py-2"><i class="bi bi-plus-circle me-1"></i><span id="resCreated">0</span> creados</span>
                    <span class="badge bg-primary fs-6 fw-normal px-3 py-2"><i class="bi bi-arrow-repeat me-1"></i><span id="resUpdated">0</span> actualizados</span>
                </div>
                <div id="resErrors" class="text-start small text-danger d-none mx-auto" style="max-width:560px;"></div>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="{{ route('inventory.stock') }}" class="btn btn-primary"><i class="bi bi-clipboard-data me-1"></i>Ir al inventario</a>
                    <a href="{{ route('inventory.stock.import') }}" class="btn btn-light border"><i class="bi bi-arrow-repeat me-1"></i>Importar otro</a>
                </div>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
.wiz-steps { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.wiz-step { display:flex; align-items:center; gap:.5rem; opacity:.5; transition:opacity .2s; }
.wiz-step.active, .wiz-step.done { opacity:1; }
.wiz-num {
    width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
    background:#e9ecef; color:#6c757d; font-weight:700; font-size:.8rem; flex-shrink:0;
}
.wiz-step.active .wiz-num { background:var(--brand-black,#22242e); color:#fff; }
.wiz-step.done .wiz-num { background:#198754; color:#fff; }
.wiz-label { font-size:.82rem; font-weight:600; color:#495057; }
.wiz-line { flex:1; height:2px; background:#e9ecef; min-width:24px; max-width:80px; }
#prevTable input { font-size:.76rem; padding:.15rem .4rem; }
#prevTable .form-control-sm { min-width:60px; }
#prevTable .r-code.code-missing {
    border-color: #dc3545;
    box-shadow: 0 0 0 .15rem rgba(220,53,69,.12);
    background: #fff5f5;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const PREVIEW_URL = '{{ route('inventory.stock.import.preview') }}';
    const CONFIRM_URL = '{{ route('inventory.stock.import.confirm') }}';
    const CSRF = '{{ csrf_token() }}';

    const fileInput = document.getElementById('file');
    const whSelect  = document.getElementById('warehouse_id');
    const dropZone  = document.getElementById('dropZone');
    const selected  = document.getElementById('fileSelected');
    const fileName  = document.getElementById('fileName');
    const btnVerify = document.getElementById('btnVerify');
    const verText   = document.getElementById('verText');
    const verIcon   = document.getElementById('verIcon');
    const verSpin   = document.getElementById('verSpinner');
    const prevError = document.getElementById('previewError');
    const MAX = 5 * 1024 * 1024;

    function money(n) { return Number(n).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(s) { return String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

    // ── Stepper ──────────────────────────────────────────────────────
    function goStep(n) {
        [1, 2, 3].forEach(function (i) {
            document.getElementById('wizPanel' + i).classList.toggle('d-none', i !== n);
        });
        document.getElementById('wizResult').classList.add('d-none');
        document.querySelectorAll('.wiz-step').forEach(function (s) {
            const step = parseInt(s.dataset.step, 10);
            s.classList.toggle('active', step === n);
            s.classList.toggle('done', step < n);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ── Step 1: file selection ───────────────────────────────────────
    function canVerify() { return fileInput.files.length > 0 && whSelect.value; }
    function refreshVerify() {
        btnVerify.disabled = !canVerify();
        verText.textContent = canVerify() ? 'Verificar archivo' : 'Selecciona almacén y archivo';
    }
    function setFile(f) {
        if (!f) return;
        if (f.size > MAX) { alert('El archivo supera los 5 MB.'); return; }
        const ext = f.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(ext)) { alert('Solo .xlsx o .xls'); return; }
        const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files;
        fileName.textContent = f.name;
        selected.classList.remove('d-none');
        dropZone.style.borderColor = 'var(--brand-black,#22242e)';
        dropZone.style.background = '#f5f5f5';
        refreshVerify();
    }
    fileInput.addEventListener('change', function () { setFile(fileInput.files[0]); });
    whSelect.addEventListener('change', refreshVerify);
    document.getElementById('clearFile').addEventListener('click', function () {
        fileInput.value = ''; selected.classList.add('d-none');
        dropZone.style.borderColor = '#ddd'; dropZone.style.background = '#fafafa'; refreshVerify();
    });
    dropZone.addEventListener('click', function (e) {
        // Los botones internos (Explorar / Quitar) manejan su propia acción;
        // evita abrir el explorador dos veces.
        if (e.target.closest('button')) return;
        fileInput.click();
    });
    dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.style.borderColor = '#22242e'; dropZone.style.background = '#f0f0f0'; });
    dropZone.addEventListener('dragleave', function () { if (!fileInput.files[0]) { dropZone.style.borderColor = '#ddd'; dropZone.style.background = '#fafafa'; } });
    dropZone.addEventListener('drop', function (e) { e.preventDefault(); if (e.dataTransfer.files[0]) setFile(e.dataTransfer.files[0]); });

    // ── Step 1 → 2: preview ──────────────────────────────────────────
    btnVerify.addEventListener('click', function () {
        if (!canVerify()) return;
        prevError.classList.add('d-none');
        btnVerify.disabled = true; verIcon.classList.add('d-none'); verSpin.classList.remove('d-none'); verText.textContent = 'Leyendo…';

        const fd = new FormData();
        fd.append('file', fileInput.files[0]);
        fd.append('warehouse_id', whSelect.value);

        fetch(PREVIEW_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.d.ok) throw new Error(res.d.message || 'No se pudo leer el archivo.');
                document.getElementById('confWarehouse').textContent = res.d.warehouse || '—';
                renderRows(res.d.rows || []);
                goStep(2);
            })
            .catch(function (e) { prevError.textContent = e.message; prevError.classList.remove('d-none'); })
            .finally(function () { btnVerify.disabled = false; verIcon.classList.remove('d-none'); verSpin.classList.add('d-none'); refreshVerify(); });
    });

    // ── Render editable rows ─────────────────────────────────────────
    function renderRows(rows) {
        const body = document.getElementById('prevBody');
        body.innerHTML = rows.map(function (r) {
            const badge = r.status === 'update'
                ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.62rem;">Actualiza</span>'
                : '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.62rem;">Nuevo</span>';
            return '<tr class="prev-row" data-status="' + r.status + '" data-catcode="' + esc(r.category_code) + '">' +
                '<td class="ps-3">' + badge + '</td>' +
                '<td><input class="form-control form-control-sm r-name" value="' + esc(r.name) + '"></td>' +
                '<td><input class="form-control form-control-sm r-category" value="' + esc(r.category) + '"></td>' +
                '<td><input class="form-control form-control-sm r-brand" value="' + esc(r.brand) + '"></td>' +
                '<td><input type="number" step="1" min="0" class="form-control form-control-sm text-center r-qty" value="' + (r.qty ?? 0) + '"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end r-cost" value="' + (r.cost ?? 0) + '"></td>' +
                '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end r-price" value="' + (r.price ?? 0) + '"></td>' +
                '<td><input class="form-control form-control-sm r-models" value="' + esc(r.models) + '"></td>' +
                '<td><input class="form-control form-control-sm r-notes" value="' + esc(r.notes) + '"></td>' +
                '<td><input class="form-control form-control-sm r-code ' + (r.code ? '' : 'code-missing') + '" value="' + esc(r.code) + '" placeholder="Sin código" title="Producto sin código"></td>' +
                '<td class="pe-3 text-center"><button type="button" class="btn btn-sm btn-light border text-danger p-0 px-1 row-del" title="Quitar"><i class="bi bi-x"></i></button></td>' +
                '</tr>';
        }).join('');
        updateRowCount();
        body.querySelectorAll('.row-del').forEach(function (b) {
            b.addEventListener('click', function () { this.closest('tr').remove(); updateRowCount(); });
        });
        // Alerta visual: marcar en rojo los códigos vacíos (actualiza al escribir)
        body.querySelectorAll('.r-code').forEach(function (inp) {
            inp.addEventListener('input', function () {
                this.classList.toggle('code-missing', this.value.trim() === '');
            });
        });
    }
    function updateRowCount() {
        const n = document.querySelectorAll('#prevBody .prev-row').length;
        document.getElementById('rowCountBadge').textContent = n + (n === 1 ? ' fila' : ' filas');
    }

    // Search in preview
    document.getElementById('prevSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#prevBody .prev-row').forEach(function (row) {
            const name = (row.querySelector('.r-name').value || '').toLowerCase();
            row.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    });

    // ── Collect rows from DOM ────────────────────────────────────────
    function collectRows() {
        return Array.from(document.querySelectorAll('#prevBody .prev-row')).map(function (row) {
            return {
                name: row.querySelector('.r-name').value.trim(),
                category: row.querySelector('.r-category').value.trim(),
                category_code: row.dataset.catcode || null,
                code: row.querySelector('.r-code').value.trim() || null,
                brand: row.querySelector('.r-brand').value.trim(),
                models: row.querySelector('.r-models').value.trim(),
                notes: row.querySelector('.r-notes').value.trim(),
                cost: parseFloat(row.querySelector('.r-cost').value) || 0,
                price: parseFloat(row.querySelector('.r-price').value) || 0,
                qty: parseFloat(row.querySelector('.r-qty').value) || 0,
                status: row.dataset.status,
            };
        }).filter(function (r) { return r.name !== ''; });
    }

    // ── Step 2 → 3: totals ───────────────────────────────────────────
    document.getElementById('btnToStep3').addEventListener('click', function () {
        const rows = collectRows();
        if (rows.length === 0) { alert('No hay filas válidas (con nombre) para importar.'); return; }
        let nw = 0, up = 0, units = 0, cost = 0, price = 0;
        rows.forEach(function (r) {
            if (r.status === 'update') up++; else nw++;
            units += r.qty; cost += r.qty * r.cost; price += r.qty * r.price;
        });
        document.getElementById('sumRows').textContent = rows.length;
        document.getElementById('sumNew').textContent = nw;
        document.getElementById('sumUpd').textContent = up;
        document.getElementById('sumUnits').textContent = units.toLocaleString('es');
        document.getElementById('sumCost').textContent = money(cost);
        document.getElementById('sumPrice').textContent = money(price);
        goStep(3);
    });

    document.getElementById('btnBackTo1').addEventListener('click', function () { goStep(1); });
    document.getElementById('btnBackTo2').addEventListener('click', function () { goStep(2); });

    // ── Step 3: confirm ──────────────────────────────────────────────
    const btnConfirm = document.getElementById('btnConfirm');
    btnConfirm.addEventListener('click', function () {
        const rows = collectRows();
        if (rows.length === 0) { alert('No hay filas para importar.'); return; }
        btnConfirm.disabled = true;
        document.getElementById('confIcon').classList.add('d-none');
        document.getElementById('confSpinner').classList.remove('d-none');
        document.getElementById('confText').textContent = 'Importando…';

        fetch(CONFIRM_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ warehouse_id: whSelect.value, rows: rows }),
        })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.d.ok) throw new Error(res.d.message || 'No se pudo importar.');
                document.getElementById('resCreated').textContent = res.d.created;
                document.getElementById('resUpdated').textContent = res.d.updated;
                const errBox = document.getElementById('resErrors');
                if (res.d.errors && res.d.errors.length) {
                    errBox.innerHTML = '<div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Filas con error (' + res.d.errors.length + '):</div><ul class="mb-0 ps-3">' + res.d.errors.map(function (e) { return '<li>' + esc(e) + '</li>'; }).join('') + '</ul>';
                    errBox.classList.remove('d-none');
                } else {
                    errBox.classList.add('d-none');
                }
                [1, 2, 3].forEach(function (i) { document.getElementById('wizPanel' + i).classList.add('d-none'); });
                document.querySelectorAll('.wiz-step').forEach(function (s) { s.classList.add('done'); s.classList.remove('active'); });
                document.getElementById('wizResult').classList.remove('d-none');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function (e) { alert(e.message); })
            .finally(function () {
                btnConfirm.disabled = false;
                document.getElementById('confIcon').classList.remove('d-none');
                document.getElementById('confSpinner').classList.add('d-none');
                document.getElementById('confText').textContent = 'Confirmar importación';
            });
    });

    refreshVerify();
})();
</script>
@endpush

@endsection
