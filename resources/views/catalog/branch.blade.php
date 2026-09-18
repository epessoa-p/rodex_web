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
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn-pdf" href="{{ route('catalog.public.pdf', $branch->public_token) }}">
            <i class="bi bi-download"></i> Descargar PDF
        </a>
    </div>

    <div class="sheet">
        <div class="cat-hero">
            <img src="{{ $logo }}" alt="Logo">
            <div class="titles">
                <h1>{{ $company?->name ?? config('brand.name') }}</h1>
                <div class="sub">Catálogo de productos y precios</div>
                <span class="branch"><i class="bi bi-shop"></i> {{ $branch->name }}</span>
            </div>
        </div>

        <div class="searchbar">
            {{-- Búsqueda en el servidor (recorre todo el catálogo, no solo la página). --}}
            <form method="GET" action="{{ route('catalog.public', $branch->public_token) }}" id="catForm">
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
                        <div class="mt-2"><a href="{{ route('catalog.public', $branch->public_token) }}" class="btn btn-sm btn-outline-dark">Ver todo el catálogo</a></div>
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
                            @if($photo)<img src="{{ $photo->url }}" alt="{{ $p->name }}">@else<i class="bi bi-box-seam"></i>@endif
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
</body>
</html>
