@extends('layouts.app')

@section('title', 'Catálogo público')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1"><i class="bi bi-qr-code"></i> Catálogo público</h1>
            <p class="text-muted mb-0 small">
                Comparte el enlace o QR de cada sucursal. Los clientes ven productos y precios (solo consulta),
                y en qué otra sucursal hay disponibilidad.
            </p>
        </div>
    </div>

    @if($branches->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>No tienes sucursales activas. Crea una sucursal para publicar su catálogo.
        </div>
    @else
    <div class="row g-3">
        @foreach($branches as $branch)
            @php $url = route('catalog.public', $branch->public_token); @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1"><i class="bi bi-shop me-1"></i>{{ $branch->name }}</h6>
                        <p class="text-muted small mb-3">{{ $branch->address ?: 'Catálogo de esta sucursal' }}</p>

                        <div class="d-flex justify-content-center mb-3">
                            <div class="p-2 border rounded bg-white qr-box" data-url="{{ $url }}"></div>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control catalog-link" value="{{ $url }}" readonly>
                            <button class="btn btn-outline-secondary btn-copy" type="button" title="Copiar enlace">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Ver
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-build"
                                    data-branch="{{ $branch->id }}" data-name="{{ $branch->name }}"
                                    data-url="{{ $url }}" data-pdf="{{ route('catalog.public.pdf', $branch->public_token) }}">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark btn-qr-download"
                                    data-name="{{ \Illuminate\Support\Str::slug($branch->name) }}">
                                <i class="bi bi-download"></i> QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Armar catálogo: elegir qué parte del catálogo mandar (PDF o enlace) --}}
<div class="modal fade" id="buildModal" tabindex="-1" aria-labelledby="buildTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="buildTitle"><i class="bi bi-funnel me-2 text-danger"></i>Armar catálogo · <span id="bmBranch"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Elige qué productos incluir. Sin elegir nada va <b>todo el catálogo</b>.
                    Puedes combinar: por ejemplo, Aceites compatibles con CG.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="bmCat">Categorías</label>
                        <select id="bmCat" class="form-select" multiple data-placeholder="Todas las categorías">
                            @foreach($filterOptions['categories'] as $o)<option value="{{ $o['id'] }}">{{ $o['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="bmBrand">Marcas</label>
                        <select id="bmBrand" class="form-select" multiple data-placeholder="Todas las marcas">
                            @foreach($filterOptions['brands'] as $o)<option value="{{ $o['id'] }}">{{ $o['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="bmModel">Modelos de vehículo compatibles</label>
                        <select id="bmModel" class="form-select" multiple data-placeholder="Todos los modelos">
                            @foreach($filterOptions['models'] as $o)<option value="{{ $o['id'] }}">{{ $o['name'] }}{{ $o['brand'] ? ' · ' . $o['brand'] : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="bmStock">
                            <label class="form-check-label" for="bmStock">Solo productos disponibles en esta sucursal</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 p-2 rounded-3 bg-light" id="bmCountBox">
                    <i class="bi bi-box-seam text-muted"></i>
                    <span id="bmCount" class="fw-semibold">Contando…</span>
                </div>
                <div id="bmWarn" class="alert alert-warning small py-2 mt-2 mb-0 d-none">
                    <i class="bi bi-hourglass-split me-1"></i>Son muchos productos: el PDF puede tardar en generarse. Conviene filtrar.
                </div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <a href="#" target="_blank" rel="noopener" class="btn btn-light border" id="bmView"><i class="bi bi-box-arrow-up-right me-1"></i>Ver</a>
                <button type="button" class="btn btn-outline-secondary" id="bmCopy"><i class="bi bi-clipboard me-1"></i>Copiar enlace</button>
                <a href="#" class="btn btn-danger" id="bmPdf"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    if (typeof QRCode === 'undefined') return;

    // Genera el QR de cada sucursal.
    document.querySelectorAll('.qr-box').forEach(function (box) {
        new QRCode(box, { text: box.dataset.url, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
    });

    // Copiar enlace.
    document.querySelectorAll('.btn-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = btn.closest('.input-group').querySelector('.catalog-link');
            navigator.clipboard.writeText(input.value).then(function () {
                const icon = btn.querySelector('i');
                icon.className = 'bi bi-check-lg text-success';
                setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 1500);
            });
        });
    });

    // Descargar el QR como PNG.
    document.querySelectorAll('.btn-qr-download').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = btn.closest('.card');
            const canvas = card.querySelector('.qr-box canvas');
            const img = card.querySelector('.qr-box img');
            const src = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
            if (!src) return;
            const a = document.createElement('a');
            a.href = src;
            a.download = 'qr-catalogo-' + btn.dataset.name + '.png';
            a.click();
        });
    });

    // ── Armar catálogo (PDF / enlace con filtros) ───────────────────────────
    const bm = document.getElementById('buildModal');
    if (bm) {
        const modal = new bootstrap.Modal(bm);
        const countUrl = "{{ route('catalog.count') }}";
        const WARN = {{ (int) $pdfWarn }};
        const sel = id => document.getElementById(id);
        let cur = null, timer = null, seq = 0;

        const values = id => Array.from(sel(id).selectedOptions).map(o => o.value);
        function query() {
            const p = new URLSearchParams();
            const cat = values('bmCat'), mar = values('bmBrand'), mod = values('bmModel');
            if (cat.length) p.set('cat', cat.join(','));
            if (mar.length) p.set('mar', mar.join(','));
            if (mod.length) p.set('mod', mod.join(','));
            if (sel('bmStock').checked) p.set('stock', '1');
            return p;
        }
        function withQuery(url, p) { const s = p.toString(); return s ? url + '?' + s : url; }

        function refresh() {
            if (!cur) return;
            const p = query();
            const pageUrl = withQuery(cur.url, p);
            sel('bmView').href = pageUrl;
            sel('bmPdf').href = withQuery(cur.pdf, p);
            sel('bmCopy').dataset.url = pageUrl;

            sel('bmCount').textContent = 'Contando…';
            clearTimeout(timer);
            const mine = ++seq;
            timer = setTimeout(function () {
                const cp = new URLSearchParams(p); cp.set('branch_id', cur.branch);
                fetch(countUrl + '?' + cp.toString(), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : null)
                    .then(res => {
                        if (mine !== seq || !res) return;
                        const n = res.count;
                        sel('bmCount').textContent = n === 1 ? '1 producto' : n + ' productos';
                        sel('bmWarn').classList.toggle('d-none', n <= WARN);
                        sel('bmPdf').classList.toggle('disabled', n === 0);
                    })
                    .catch(() => { if (mine === seq) sel('bmCount').textContent = '—'; });
            }, 250);
        }

        document.querySelectorAll('.btn-build').forEach(function (btn) {
            btn.addEventListener('click', function () {
                cur = { branch: btn.dataset.branch, url: btn.dataset.url, pdf: btn.dataset.pdf };
                sel('bmBranch').textContent = btn.dataset.name;
                ['bmCat', 'bmBrand', 'bmModel'].forEach(id => Array.from(sel(id).options).forEach(o => o.selected = false));
                sel('bmStock').checked = false;
                if (window.jQuery && jQuery.fn.select2) jQuery('#buildModal select').trigger('change.select2');
                refresh();
                modal.show();
            });
        });

        // Select2 avisa los cambios con jQuery; la casilla, con el evento normal.
        if (window.jQuery) jQuery('#buildModal select').on('change', refresh);
        else ['bmCat', 'bmBrand', 'bmModel'].forEach(id => sel(id).addEventListener('change', refresh));
        sel('bmStock').addEventListener('change', refresh);

        sel('bmCopy').addEventListener('click', function () {
            const url = this.dataset.url || '';
            const done = () => { const i = this.querySelector('i'); i.className = 'bi bi-check-lg me-1 text-success'; setTimeout(() => { i.className = 'bi bi-clipboard me-1'; }, 1500); };
            if (navigator.clipboard) navigator.clipboard.writeText(url).then(done).catch(() => window.prompt('Copia el enlace:', url));
            else window.prompt('Copia el enlace:', url);
        });
    }})();
</script>
@endpush
