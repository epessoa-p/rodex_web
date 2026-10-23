<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Catálogo — {{ $branch->name }}</title>
    @php $money = fn ($n) => money($n, $company?->currency); @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; color:#1a1a1a; margin:0; font-size:11px; }
        .header { width:100%; border-bottom:3px solid #e63946; padding-bottom:10px; margin-bottom:12px; }
        .header td { vertical-align: middle; }
        .header img { height:60px; width:auto; }
        .header .logo-cell { width:74px; }
        .header h1 { margin:0; font-size:16px; }
        .header .meta { color:#666; font-size:10px; margin-top:3px; }
        table.data { width:100%; border-collapse: collapse; }
        table.data thead th {
            background:#22242e; color:#fff; text-align:left; padding:6px 8px; font-size:10px; text-transform:uppercase;
        }
        table.data thead th.num { text-align:right; }
        table.data tbody td { padding:5px 8px; border-bottom:1px solid #eee; }
        table.data tbody td.num { text-align:right; font-weight:bold; }
        table.data tbody tr:nth-child(even) { background:#fafafa; }
        .ok { color:#15803d; font-weight:bold; }
        .no { color:#b91c1c; }
        .others { color:#555; font-size:9px; }
        .foot { margin-top:14px; color:#888; font-size:9px; text-align:center; }

        /* ── Versión con fotos: tarjetas de 3 por fila ── */
        table.grid { width:100%; border-collapse: separate; border-spacing: 8px; table-layout: fixed; margin: -8px; }
        table.grid td.card { width:33.33%; vertical-align: top; border:1px solid #e5e7eb; border-radius:8px; padding:8px; background:#fff; }
        table.grid td.empty { border:0; }
        .ph { height:118px; text-align:center; background:#f8fafc; border-radius:6px; margin-bottom:6px; }
        .ph img { max-width:100%; max-height:118px; }
        .ph .none { color:#94a3b8; font-size:9px; padding-top:50px; }
        .c-name { font-weight:bold; font-size:10.5px; line-height:1.25; height:27px; overflow:hidden; }
        .c-meta { color:#64748b; font-size:8.5px; margin-top:2px; height:11px; overflow:hidden; }
        .c-price { color:#e63946; font-size:14px; font-weight:bold; margin-top:5px; }
        .c-av { font-size:8.5px; margin-top:2px; }
        .chip-ok { color:#15803d; background:#dcfce7; padding:1px 5px; border-radius:6px; font-weight:bold; }
        .chip-no { color:#b91c1c; background:#fee2e2; padding:1px 5px; border-radius:6px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if($logoData)
            <td class="logo-cell"><img src="{{ $logoData }}" alt="Logo"></td>
            @endif
            <td>
                <h1>{{ $company?->name ?? config('brand.name') }}</h1>
                <div class="meta">
                    Catálogo de productos y precios · Sucursal: <strong>{{ $branch->name }}</strong> · Generado: {{ $generatedAt }}
                </div>
                @if(!empty($filterLabel))
                <div class="meta">Selección: <strong>{{ $filterLabel }}</strong> · {{ $products->count() }} {{ $products->count() === 1 ? 'producto' : 'productos' }}</div>
                @endif
            </td>
        </tr>
    </table>

    @if($withPhotos ?? false)
        {{-- Con fotos: tarjetas de 3 por fila --}}
        @if($products->isEmpty())
            <p style="text-align:center; padding:20px;">Sin productos.</p>
        @else
        <table class="grid">
            @foreach($products->chunk(3) as $row)
            <tr>
                @foreach($row as $p)
                    @php $av = $availability[$p->id] ?? ['here' => false, 'others' => []]; @endphp
                    <td class="card">
                        <div class="ph">
                            @if(!empty($photoData[$p->id]))
                                <img src="{{ $photoData[$p->id] }}" alt="">
                            @else
                                <div class="none">Sin foto</div>
                            @endif
                        </div>
                        <div class="c-name">{{ \Illuminate\Support\Str::limit($p->name, 60) }}</div>
                        <div class="c-meta">{{ $p->brand?->name }}@if($p->brand && $p->category) · @endif{{ $p->category?->name }}</div>
                        <div class="c-price">{{ $money($p->price) }}</div>
                        <div class="c-av">
                            @if($av['here'])<span class="chip-ok">Disponible</span>@else<span class="chip-no">Agotado</span>@endif
                            @if(!empty($av['others']))<div class="others" style="margin-top:2px;">También en: {{ implode(', ', $av['others']) }}</div>@endif
                        </div>
                    </td>
                @endforeach
                @for($i = $row->count(); $i < 3; $i++)<td class="empty"></td>@endfor
            </tr>
            @endforeach
        </table>
        @endif
    @else
    <table class="data">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Marca / Categoría</th>
                <th class="num">Precio</th>
                <th>Disponibilidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                @php $av = $availability[$p->id] ?? ['here' => false, 'others' => []]; @endphp
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->brand?->name }}@if($p->brand && $p->category) · @endif{{ $p->category?->name }}</td>
                    <td class="num">{{ $money($p->price) }}</td>
                    <td>
                        @if($av['here'])<span class="ok">Disponible</span>@else<span class="no">Agotado</span>@endif
                        @if(!empty($av['others']))
                            <div class="others">También en: {{ implode(', ', $av['others']) }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; padding:20px;">Sin productos.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="foot">
        {{ $company?->name ?? config('brand.name') }} · {{ $branch->name }} — Precios de referencia sujetos a disponibilidad.
    </div>
</body>
</html>
