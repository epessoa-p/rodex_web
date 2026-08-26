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
            </td>
        </tr>
    </table>

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

    <div class="foot">
        {{ $company?->name ?? config('brand.name') }} · {{ $branch->name }} — Precios de referencia sujetos a disponibilidad.
    </div>
</body>
</html>
