<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo — {{ $company?->name ?? config('brand.name') }} · {{ $branch->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @php
        $logo  = $company?->logo_url ?? asset(config('brand.logo'));
        $money = fn ($n) => money($n, $company?->currency);
    @endphp
    <style>
        :root { --brand:#e63946; }
        * { box-sizing: border-box; }
        body { background:#f3f4f6; color:#111; font-family:"Segoe UI",Arial,sans-serif; margin:0; }
        .sheet { max-width: 980px; margin: 0 auto; background:#fff; min-height:100vh; }
        .cat-hero {
            background: linear-gradient(120deg,#22242e 0%, #1a1a1a 60%, var(--brand) 150%);
            color:#fff; padding: 26px 30px; display:flex; align-items:center; gap:20px;
        }
        .cat-hero img { width:78px; height:78px; object-fit:contain; background:#fff; border-radius:12px; padding:6px; }
        .cat-hero .titles { flex:1; }
        .cat-hero h1 { font-size:1.5rem; font-weight:800; margin:0; }
        .cat-hero .sub { opacity:.85; font-size:.9rem; }
        .cat-hero .branch { display:inline-block; margin-top:6px; background:rgba(255,255,255,.15); border-radius:50rem; padding:3px 12px; font-size:.82rem; }
        .searchbar { padding:14px 30px; border-bottom:1px solid #eee; background:#fafafa; position:sticky; top:0; z-index:5; }
        .cat-body { padding: 20px 30px 40px; }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; }
        .prod {
            border:1.5px solid #ececec; border-radius:14px; overflow:hidden; background:#fff;
            display:flex; flex-direction:column;
        }
        .prod .thumb { height:140px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; }
        .prod .thumb img { width:100%; height:100%; object-fit:cover; }
        .prod .thumb i { font-size:2.6rem; color:#cfcfcf; }
        .prod .info { padding:12px 14px; flex:1; display:flex; flex-direction:column; }
        .prod .name { font-weight:700; font-size:.95rem; margin-bottom:2px; }
        .prod .meta { color:#888; font-size:.74rem; margin-bottom:8px; }
        .prod .price { font-weight:800; font-size:1.05rem; color:#111; }
        .stock { margin-top:8px; display:flex; align-items:center; gap:6px; font-size:.82rem; font-weight:600; }
        .stock.ok  { color:#15803d; }
        .stock.no  { color:#b91c1c; }
        .others { margin-top:6px; font-size:.72rem; color:#555; }
        .others .b { display:inline-block; background:#eef2ff; color:#3730a3; border-radius:50rem; padding:1px 8px; margin:2px 2px 0 0; }
        .empty { text-align:center; color:#888; padding:60px 0; }
        .cat-foot { text-align:center; color:#888; font-size:.82rem; padding: 8px 30px 30px; }
        .count { color:#666; font-size:.82rem; margin-bottom:12px; display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        .count a { color:var(--brand); text-decoration:none; font-weight:600; }
        .pager { display:flex; justify-content:center; margin-top:26px; }
        .pager .pagination { margin:0; flex-wrap:wrap; gap:4px; }
        .pager .page-link { color:#111; border-radius:8px !important; min-width:38px; text-align:center; border-color:#e5e7eb; }
        .pager .page-item.active .page-link { background:var(--brand); border-color:var(--brand); color:#fff; }
        .pager .page-item.disabled .page-link { color:#bbb; }
        .toolbar { position:sticky; top:0; background:#111; color:#fff; padding:10px 16px; display:flex; gap:8px; justify-content:center; z-index:10; }
        .toolbar a { border:0; border-radius:8px; padding:8px 16px; font-size:.9rem; text-decoration:none; }
        .btn-pdf { background:var(--brand); color:#fff; }
        @media (max-width: 760px) { .grid { grid-template-columns: repeat(2, 1fr); } .cat-hero { padding:20px; } }
        @media (max-width: 460px) { .grid { grid-template-columns: 1fr; } }
        .toolbar button { border:0; border-radius:8px; padding:8px 16px; font-size:.9rem; }
        /* Armador del PDF */
        .pdfb-group { margin-bottom:14px; }
        .pdfb-group h6 { font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#555; margin-bottom:6px; }
        .pdfb-chips { display:flex; flex-wrap:wrap; gap:6px; max-height:150px; overflow:auto; padding:2px; }
        .pdfb-chip input { display:none; }
        .pdfb-chip span { display:inline-block; border:1px solid #d6d9de; border-radius:999px; padding:4px 11px; font-size:.82rem; cursor:pointer; background:#fff; user-select:none; }
        .pdfb-chip input:checked + span { background:#111; color:#fff; border-color:#111; }
        .pdfb-chip input:focus-visible + span { outline:2px solid var(--brand); outline-offset:2px; }
        .pdfb-search { font-size:.82rem; margin-bottom:6px; }
        .pdfb-count { background:#f4f5f7; border-radius:10px; padding:10px 12px; font-weight:600; }
        .pdfb-count.warn { background:#fff4e5; color:#9a4d00; }
        .pdfb-count.bad { background:#fdecec; color:#b42318; }
        .pdfb-photo { border:1px dashed #d6d9de; border-radius:12px; padding:10px 12px; }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn-pdf" data-bs-toggle="modal" data-bs-target="#pdfBuilder">
            <i class="bi bi-download"></i> Descargar PDF
        </button>
    </div>

    {{-- Armador del PDF: elegir qué incluir antes de descargar --}}
    @php
        $opts = $filterOptions ?? [];
        $sel = [
            'cat' => $filter->categories ?? [], 'mar' => $filter->brands ?? [], 'mod' => $filter->models ?? [],
        ];
        $groups = [
            'cat' => ['Categorías', $opts['categories'] ?? []],
            'mar' => ['Marcas', $opts['brands'] ?? []],
            'mod' => ['Modelos de vehículo compatibles', $opts['models'] ?? []],
        ];
    @endphp
    <div class="modal fade" id="pdfBuilder" tabindex="-1" aria-labelledby="pdfbTitle">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="pdfbTitle"><i class="bi bi-file-earmark-pdf me-2" style="color:var(--brand)"></i>Descargar catálogo en PDF</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Elige qué productos quieres en el PDF. Sin elegir nada va todo el catálogo (sin fotos).</p>
                    @foreach($groups as $key => [$title, $items])
                        @if(count($items))
                        <div class="pdfb-group" data-group="{{ $key }}">
                            <h6>{{ $title }}</h6>
                            @if(count($items) > 10)
                                <input type="search" class="form-control form-control-sm pdfb-search" placeholder="Buscar {{ mb_strtolower($title) }}…" aria-label="Buscar {{ mb_strtolower($title) }}">
                            @endif
                            <div class="pdfb-chips">
                                @foreach($items as $o)
                                    <label class="pdfb-chip" data-label="{{ mb_strtolower($o['name'] . ' ' . ($o['brand'] ?? '')) }}">
                                        <input type="checkbox" name="{{ $key }}" value="{{ $o['id'] }}" @checked(in_array($o['id'], $sel[$key]))>
                                        <span>{{ $o['name'] }}@if(!empty($o['brand'])) · {{ $o['brand'] }}@endif</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="pdfbStock" @checked($filter->onlyInStock ?? false)>
                        <label class="form-check-label" for="pdfbStock">Solo productos disponibles en esta sucursal</label>
                    </div>

                    <div class="pdfb-photo mb-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="pdfbPhotos">
                            <label class="form-check-label fw-semibold" for="pdfbPhotos"><i class="bi bi-images me-1"></i>Incluir fotos de los productos</label>
                        </div>
                        <div class="small text-muted mt-1">Con fotos el PDF pesa más: elige al menos una categoría, marca o modelo (máximo {{ \App\Services\Catalog\CatalogFilter::PHOTO_MAX }} productos).</div>
                    </div>

                    <div class="pdfb-count" id="pdfbCount" aria-live="polite">Contando…</div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-light border" id="pdfbView"><i class="bi bi-eye me-1"></i>Ver en la página</a>
                    <a href="#" class="btn btn-dark" id="pdfbDownload"><i class="bi bi-download me-1"></i>Descargar PDF</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sheet">
        <div class="cat-hero">
            <img src="{{ $logo }}" alt="Logo">
            <div class="titles">
                <h1>{{ $company?->name ?? config('brand.name') }}</h1>
                <div class="sub">Catálogo de productos y precios</div>
                <span class="branch"><i class="bi bi-shop"></i> {{ $branch->name }}</span>
                @if(!empty($filterLabel))
                <div class="sub mt-1"><i class="bi bi-funnel"></i> {{ $filterLabel }}</div>
                @endif
            </div>
        </div>

        <div class="searchbar">
            {{-- Búsqueda en el servidor (recorre todo el catálogo, no solo la página). --}}
            <form method="GET" action="{{ route('catalog.public', $branch->public_token) }}" id="catForm">
                @foreach(($filterQuery ?? []) as $fk => $fv)<input type="hidden" name="{{ $fk }}" value="{{ $fv }}">@endforeach
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" id="catSearch" class="form-control" value="{{ $q ?? '' }}"
                           placeholder="Buscar producto por nombre, categoría, marca o código…" autocomplete="off">
                    <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i><span class="d-none d-sm-inline ms-1">Buscar</span></button>
                </div>
            </form>
        </div>

        <div class="cat-body">
            @if($products->isEmpty())
                @if(($q ?? '') !== '')
                    <div class="empty"><i class="bi bi-search d-block fs-1 mb-2 opacity-25"></i>Sin resultados para «{{ $q }}».
                        <div class="mt-2"><a href="{{ route('catalog.public', array_merge(['token' => $branch->public_token], $filterQuery ?? [])) }}" class="btn btn-sm btn-outline-dark">Ver todo el catálogo</a></div>
                    </div>
                @else
                    <div class="empty"><i class="bi bi-box2 d-block fs-1 mb-2 opacity-25"></i>Aún no hay productos en el catálogo.</div>
                @endif
            @else
            <div class="count">
                <span>
                    @if($products->total() > $products->perPage())
                        Mostrando <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong> de <strong>{{ $products->total() }}</strong> productos
                    @else
                        <strong>{{ $products->total() }}</strong> {{ $products->total() === 1 ? 'producto' : 'productos' }}
                    @endif
                    @if(($q ?? '') !== '') para «{{ $q }}»@endif
                </span>
                @if(($q ?? '') !== '')
                    <a href="{{ route('catalog.public', $branch->public_token) }}"><i class="bi bi-x-circle"></i> Ver todo el catálogo</a>
                @elseif($products->hasPages())
                    <span>Página {{ $products->currentPage() }} de {{ $products->lastPage() }}</span>
                @endif
            </div>
            <div class="grid" id="catGrid">
                @foreach($products as $p)
                    @php $av = $availability[$p->id] ?? ['here' => false, 'others' => []]; @endphp
                    <div class="prod">
                        <div class="thumb">
                            @php $photo = $p->mainPhoto(); @endphp
                            @if($photo)<img src="{{ $photo->thumb_url }}" alt="{{ $p->name }}" loading="lazy">@else<i class="bi bi-box-seam"></i>@endif
                        </div>
                        <div class="info">
                            <div class="name">{{ $p->name }}</div>
                            <div class="meta">
                                {{ $p->brand?->name }}@if($p->brand && $p->category) · @endif{{ $p->category?->name }}
                            </div>
                            <div class="price">{{ $money($p->price) }}</div>

                            @if($av['here'])
                                <div class="stock ok"><i class="bi bi-check-circle-fill"></i> Disponible</div>
                            @else
                                <div class="stock no"><i class="bi bi-x-circle"></i> Agotado en esta sucursal</div>
                            @endif

                            @if(!empty($av['others']))
                                <div class="others">
                                    También en:
                                    @foreach($av['others'] as $bn)<span class="b">{{ $bn }}</span>@endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if($products->hasPages())
                <div class="pager">{{ $products->onEachSide(1)->links('catalog.pagination') }}</div>
            @endif
            @endif
        </div>

        <div class="cat-foot">
            @php $tel = $branch->phone ?: $company?->phone; @endphp
            {{ $company?->name ?? config('brand.name') }} · {{ $branch->name }}@if($tel) · Tel: {{ $tel }}@endif
            @if($branch->address) · {{ $branch->address }}@endif
            <div>Precios de referencia sujetos a disponibilidad.</div>
        </div>
    </div>

    <script>
        // Enviar la búsqueda al dejar de escribir (sin esperar Enter).
        (function () {
            const input = document.getElementById('catSearch');
            const form  = document.getElementById('catForm');
            if (!input || !form) return;
            let t = null;
            const initial = input.value;
            input.addEventListener('input', function () {
                clearTimeout(t);
                t = setTimeout(() => { if (input.value.trim() !== initial.trim()) form.submit(); }, 600);
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Armador del PDF ──
        (function () {
            const box = document.getElementById('pdfBuilder');
            if (!box || !window.bootstrap) return;
            const pdfUrl   = @json(route('catalog.public.pdf', $branch->public_token));
            const pageUrl  = @json(route('catalog.public', $branch->public_token));
            const countUrl = @json(route('catalog.public.count', $branch->public_token));
            const WARN = {{ \App\Services\Catalog\CatalogFilter::PDF_WARN }};
            const countEl = document.getElementById('pdfbCount');
            const dl = document.getElementById('pdfbDownload');
            let timer = null, seq = 0, blocked = null;

            function params(withPhotos) {
                const p = new URLSearchParams();
                ['cat', 'mar', 'mod'].forEach(k => {
                    const v = Array.from(box.querySelectorAll('input[name="' + k + '"]:checked')).map(i => i.value);
                    if (v.length) p.set(k, v.join(','));
                });
                if (document.getElementById('pdfbStock').checked) p.set('stock', '1');
                if (withPhotos && document.getElementById('pdfbPhotos').checked) p.set('fotos', '1');
                return p;
            }
            const url = (base, p) => p.toString() ? base + '?' + p.toString().replace(/%2C/g, ',') : base;

            function refresh() {
                const p = params(true);
                dl.href = url(pdfUrl, p);
                document.getElementById('pdfbView').href = url(pageUrl, params(false));
                countEl.textContent = 'Contando…';
                countEl.className = 'pdfb-count';
                clearTimeout(timer);
                const mine = ++seq;
                timer = setTimeout(() => {
                    fetch(url(countUrl, p), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.ok ? r.json() : null)
                        .then(res => {
                            if (mine !== seq || !res) return;
                            blocked = res.blocked;
                            const n = res.count;
                            if (blocked) {
                                countEl.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>' + blocked;
                                countEl.className = 'pdfb-count bad';
                            } else {
                                countEl.innerHTML = '<i class="bi bi-box-seam me-1"></i>' + (n === 1 ? '1 producto' : n + ' productos')
                                    + (n > WARN ? ' · el PDF puede tardar, conviene filtrar' : '');
                                countEl.className = 'pdfb-count' + (n > WARN ? ' warn' : '');
                            }
                            dl.classList.toggle('disabled', n === 0);
                        })
                        .catch(() => { blocked = null; countEl.textContent = '—'; });
                }, 250);
            }

            // Con fotos y sin filtro (o demasiados): avisar en vez de descargar.
            dl.addEventListener('click', function (e) {
                if (blocked) {
                    e.preventDefault();
                    countEl.classList.add('bad');
                    countEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });

            box.addEventListener('change', refresh);
            box.querySelectorAll('.pdfb-search').forEach(s => s.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                this.closest('.pdfb-group').querySelectorAll('.pdfb-chip').forEach(c => {
                    const on = c.querySelector('input').checked;
                    c.style.display = !q || on || c.dataset.label.includes(q) ? '' : 'none';
                });
            }));
            box.addEventListener('show.bs.modal', refresh);
            // Volver desde "elige un filtro": abrir el armador directo.
            if (location.hash === '#armar') new bootstrap.Modal(box).show();
        })();
    </script>
</body>
</html>
