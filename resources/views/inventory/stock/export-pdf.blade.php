@php
    // dompdf rinde mejor con la imagen embebida en base64 que con una URL/ruta.
    // $companyLogo es el logo de la empresa (o el de la plataforma como respaldo).
    $logoPath = $companyLogo ?? null;
    $logoData = ($logoPath && is_file($logoPath))
        ? 'data:' . (mime_content_type($logoPath) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inventario — {{ $companyName }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            font-size: 11px;
        }
        /* Header como tabla (dompdf no soporta flexbox) */
        .header { width: 100%; border-bottom: 3px solid #e63946; padding-bottom: 10px; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .header img { height: 64px; width: auto; }
        .header .logo-cell { width: 80px; }
        .header h1 { margin: 0; font-size: 17px; }
        .header .meta { color: #666; font-size: 10px; margin-top: 3px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data thead th {
            background: #22242e;
            color: #fff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.data thead th.num { text-align: right; }
        table.data tbody td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        table.data tbody td.num { text-align: right; }
        table.data tbody tr:nth-child(even) { background: #fafafa; }
        table.data tfoot td { padding: 7px 8px; border-top: 2px solid #22242e; font-weight: bold; }
        table.data tfoot td.num { text-align: right; }
        .code { color: #555; font-family: "DejaVu Sans Mono", monospace; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if($logoData)
            <td class="logo-cell"><img src="{{ $logoData }}" alt="logo"></td>
            @endif
            <td>
                <h1>{{ $companyName }} — Inventario</h1>
                <div class="meta">Almacén: {{ $warehouseLabel }} &middot; Generado: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                <th class="num">Precio (Bs.)</th>
                <th class="num">Costo (Bs.)</th>
                <th class="num">Cantidad disponible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td class="code">{{ $row['code'] }}</td>
                <td class="num">{{ number_format($row['price'], 2) }}</td>
                <td class="num">{{ number_format($row['cost'], 2) }}</td>
                <td class="num">{{ number_format($row['stock'], 0) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:24px;color:#888;">No hay productos en el inventario.</td></tr>
            @endforelse
        </tbody>
        @if(count($rows))
        <tfoot>
            <tr>
                <td colspan="4">Total de productos: {{ count($rows) }}</td>
                <td class="num">{{ number_format(collect($rows)->sum('stock'), 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>
