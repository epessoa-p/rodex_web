@extends('layouts.app')

@section('title', 'Importar productos desde Excel')

@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-file-earmark-spreadsheet"></i> Importar productos</h1>
            <p class="text-muted mb-0">Carga masiva desde archivo Excel (.xlsx / .xls).</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    {{-- Resultado de la importación --}}
    @if(session('import_result'))
    @php $r = session('import_result'); @endphp
    <div class="alert border-0 shadow-sm mb-4 {{ $r['imported'] > 0 ? 'alert-success' : 'alert-secondary' }}">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <i class="bi {{ $r['imported'] > 0 ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }} fs-4"></i>
            <div>
                <div class="fw-semibold mb-1">Importación completada</div>
                <div class="d-flex gap-3 flex-wrap" style="font-size:.88rem;">
                    <span class="badge bg-success fs-6 fw-normal">
                        <i class="bi bi-box-arrow-in-down me-1"></i>{{ $r['imported'] }} importados
                    </span>
                    <span class="badge bg-secondary fs-6 fw-normal">
                        <i class="bi bi-skip-forward me-1"></i>{{ $r['skipped'] }} omitidos (ya existían)
                    </span>
                </div>
            </div>
        </div>
        @if(!empty($r['errors']))
        <hr class="my-2">
        <div class="small text-danger">
            <strong>Filas con error:</strong>
            <ul class="mb-0 ps-3 mt-1">
                @foreach($r['errors'] as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-4">

        {{-- Formulario de carga --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-upload me-2 text-muted"></i>Seleccionar archivo</h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.import.process') }}" method="POST" enctype="multipart/form-data"
                          id="importForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="file">Archivo Excel</label>
                            <div id="dropZone" class="rounded-3 p-5 text-center"
                                 style="border:2px dashed #ddd;cursor:pointer;background:#fafafa;transition:border-color .2s;">
                                <i class="bi bi-file-earmark-spreadsheet d-block fs-1 mb-2 text-muted opacity-50"></i>
                                <div class="fw-semibold mb-1">Arrastra el archivo aquí</div>
                                <div class="text-muted small mb-3">o haz clic para seleccionarlo</div>
                                <input type="file" id="file" name="file" class="d-none" accept=".xlsx,.xls">
                                <button type="button" class="btn btn-light border px-4" onclick="document.getElementById('file').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Explorar archivos
                                </button>
                            </div>
                            <div id="fileSelected" class="mt-2 d-none">
                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-light">
                                    <i class="bi bi-file-earmark-spreadsheet text-success fs-5"></i>
                                    <span id="fileName" class="fw-semibold flex-grow-1 text-truncate"></span>
                                    <button type="button" class="btn btn-sm btn-light border" id="clearFile">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="text-muted mt-1" style="font-size:.78rem;">
                                Formatos aceptados: .xlsx, .xls — Tamaño máximo: 5 MB
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="btnImport" disabled>
                            <span id="btnIcon"><i class="bi bi-cloud-upload me-1"></i></span>
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpinner"></span>
                            <span id="btnText">Selecciona un archivo para continuar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instrucciones --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2 text-muted"></i>Formato esperado</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Columna</th>
                                <th>Campo</th>
                                <th>Ejemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-3"><span class="badge bg-primary">A</span></td>
                                <td class="fw-semibold">Nombre <span class="text-danger">*</span></td>
                                <td class="text-muted small">(01)Carburador TRUENO</td>
                            </tr>
                            <tr>
                                <td class="ps-3"><span class="badge bg-primary">B</span></td>
                                <td class="fw-semibold">Categoría</td>
                                <td class="text-muted small">Carburacion y aire(999)</td>
                            </tr>
                            <tr>
                                <td class="ps-3"><span class="badge bg-primary">C</span></td>
                                <td class="fw-semibold">Notas</td>
                                <td class="text-muted small">moto cg150, cg200</td>
                            </tr>
                            <tr>
                                <td class="ps-3"><span class="badge bg-primary">D</span></td>
                                <td class="fw-semibold">Costo</td>
                                <td class="text-muted small">104</td>
                            </tr>
                            <tr>
                                <td class="ps-3"><span class="badge bg-primary">E</span></td>
                                <td class="fw-semibold">Precio venta</td>
                                <td class="text-muted small">140</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-lightbulb me-2 text-warning"></i>Comportamiento del importador</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0" style="font-size:.85rem;">
                        <li class="d-flex gap-2 mb-2">
                            <i class="bi bi-eraser text-primary flex-shrink-0 mt-1"></i>
                            <span>Los códigos entre paréntesis como <code>(01)</code> o <code>(999)</code> se eliminan automáticamente del nombre y la categoría.</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="bi bi-skip-forward text-secondary flex-shrink-0 mt-1"></i>
                            <span>Si ya existe un producto con el mismo nombre, la fila se <strong>omite</strong> sin generar error.</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="bi bi-tags text-success flex-shrink-0 mt-1"></i>
                            <span>Si la categoría no existe, se crea automáticamente con el nombre de la columna B.</span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-upc-scan text-info flex-shrink-0 mt-1"></i>
                            <span>El SKU se genera automáticamente a partir del nombre del producto.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const input     = document.getElementById('file');
    const dropZone  = document.getElementById('dropZone');
    const selected  = document.getElementById('fileSelected');
    const fileName  = document.getElementById('fileName');
    const clearBtn  = document.getElementById('clearFile');
    const btn       = document.getElementById('btnImport');
    const btnText   = document.getElementById('btnText');
    const btnIcon   = document.getElementById('btnIcon');
    const btnSpinner= document.getElementById('btnSpinner');

    function setFile(file) {
        if (!file) return;
        fileName.textContent = file.name;
        selected.classList.remove('d-none');
        dropZone.style.borderColor = 'var(--brand-red)';
        btn.disabled = false;
        btnText.textContent = 'Importar productos';
    }

    function clearFile() {
        input.value = '';
        selected.classList.add('d-none');
        dropZone.style.borderColor = '#ddd';
        btn.disabled = true;
        btnText.textContent = 'Selecciona un archivo para continuar';
    }

    input.addEventListener('change', () => setFile(input.files[0]));
    clearBtn.addEventListener('click', clearFile);
    dropZone.addEventListener('click', () => input.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--brand-red)'; });
    dropZone.addEventListener('dragleave', () => { if (!input.files[0]) dropZone.style.borderColor = '#ddd'; });
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        const f = e.dataTransfer.files[0];
        if (f) {
            const dt = new DataTransfer();
            dt.items.add(f);
            input.files = dt.files;
            setFile(f);
        }
    });

    document.getElementById('importForm').addEventListener('submit', function () {
        btn.disabled  = true;
        btnIcon.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Procesando...';
    });
})();
</script>
@endpush
@endsection
