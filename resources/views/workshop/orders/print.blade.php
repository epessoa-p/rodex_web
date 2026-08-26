@php
    $company = $order->company;
    $money = fn ($n) => money($n, $company?->currency);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OT {{ $order->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: letter; margin: 14mm 14mm 16mm; }
        html, body { background: #fff; color: #1a1a1a; font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; line-height: 1.4; }
        .sheet { width: 100%; max-width: 720px; margin: 0 auto; }

        .head { display: flex; align-items: center; gap: 14px; border-bottom: 3px solid #e63946; padding-bottom: 12px; margin-bottom: 14px; }
        .head img { height: 58px; width: auto; }
        .head .biz { flex: 1; }
        .biz-name { font-size: 18px; font-weight: 800; }
        .biz-sub { font-size: 11px; color: #666; }
        .doc-box { text-align: right; }
        .doc-title { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #888; }
        .doc-code { font-size: 20px; font-weight: 800; color: #e63946; }
        .doc-date { font-size: 11px; color: #666; }

        .meta { display: flex; gap: 14px; margin-bottom: 14px; }
        .meta .box { flex: 1; border: 1px solid #e5e5e5; border-radius: 8px; padding: 9px 11px; }
        .meta .box h4 { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #999; margin-bottom: 4px; }
        .meta .box .row { font-size: 11.5px; }
        .meta .box .row b { display: inline-block; min-width: 62px; color: #555; font-weight: 600; }

        .sec-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #444; margin: 14px 0 5px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #22242e; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; text-transform: uppercase; }
        thead th.num { text-align: right; }
        thead th.ctr { text-align: center; }
        tbody td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 11.5px; }
        tbody td.num { text-align: right; }
        tbody td.ctr { text-align: center; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .empty { text-align: center; color: #999; padding: 10px; font-style: italic; }

        .totes { display: flex; justify-content: flex-end; margin-top: 12px; }
        .totes table { width: 46%; }
        .totes td { padding: 3px 8px; font-size: 12px; }
        .totes td.num { text-align: right; }
        .totes .grand td { border-top: 2px solid #22242e; font-weight: 800; font-size: 14px; padding-top: 5px; }
        .totes .bal td { font-weight: 700; color: #e63946; }

        .diag { border: 1px solid #e5e5e5; border-radius: 8px; padding: 9px 11px; margin-top: 12px; font-size: 11.5px; }
        .signs { display: flex; gap: 40px; margin-top: 42px; }
        .signs .s { flex: 1; text-align: center; border-top: 1px solid #333; padding-top: 5px; font-size: 11px; color: #555; }
        .foot { margin-top: 18px; text-align: center; font-size: 10px; color: #999; }

        .print-bar { position: fixed; top: 12px; right: 16px; }
        .print-bar button { background: #e63946; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        @media print { .print-bar { display: none; } }
    </style>
</head>
<body>
    <div class="print-bar">
        <button onclick="window.print()">🖨 Imprimir</button>
    </div>

    <div class="sheet">
        <div class="head">
            @if($company?->logo_file)
            <img src="{{ $company->logo_url }}" alt="{{ $company->name }}">
            @endif
            <div class="biz">
                <div class="biz-name">{{ $company?->name ?? config('brand.name') }}</div>
                <div class="biz-sub">Taller &amp; Servicio Técnico</div>
                @if($order->branch?->name)<div class="biz-sub">{{ $order->branch->name }}</div>@endif
                @php $tel = $order->branch?->phone ?: $company?->phone; @endphp
                @if($tel)<div class="biz-sub">Tel: {{ $tel }}</div>@endif
            </div>
            <div class="doc-box">
                <div class="doc-title">Orden de Trabajo</div>
                <div class="doc-code">{{ $order->code }}</div>
                <div class="doc-date">Recepción: {{ \Carbon\Carbon::parse($order->reception_date)->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="meta">
            <div class="box">
                <h4>Cliente</h4>
                <div class="row"><b>Nombre:</b> {{ $order->client?->full_name ?? '—' }}</div>
                @if($order->client?->id_number)<div class="row"><b>Documento:</b> {{ $order->client->id_number }}</div>@endif
                @if($order->client?->phone)<div class="row"><b>Teléfono:</b> {{ $order->client->phone }}</div>@endif
            </div>
            <div class="box">
                <h4>Vehículo</h4>
                <div class="row"><b>Unidad:</b> {{ $order->vehicle?->display_name ?? '—' }}</div>
                @if($order->vehicle?->plate)<div class="row"><b>Placa:</b> {{ $order->vehicle->plate }}</div>@endif
                <div class="row"><b>Kilometraje:</b> {{ $order->mileage ? number_format($order->mileage) . ' km' : '—' }}</div>
                @if($order->mechanic)<div class="row"><b>Mecánico:</b> {{ $order->mechanic->name }}</div>@endif
            </div>
        </div>

        @if($order->reported_issue)
        <div class="diag"><b>Falla reportada:</b> {{ $order->reported_issue }}</div>
        @endif
        @if($order->diagnosis)
        <div class="diag"><b>Diagnóstico:</b> {{ $order->diagnosis }}</div>
        @endif

        <div class="sec-title">Servicios</div>
        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Mecánico</th>
                    <th class="num">Precio</th>
                    <th class="ctr">Cant.</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->services as $svc)
                <tr>
                    <td>{{ $svc->description ?: $svc->service?->name }}</td>
                    <td>{{ $svc->mechanic?->name ?? '—' }}</td>
                    <td class="num">{{ $money($svc->price) }}</td>
                    <td class="ctr">{{ $svc->quantity }}</td>
                    <td class="num">{{ $money($svc->subtotal) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty">Sin servicios.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="sec-title">Repuestos</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="ctr">Cant.</th>
                    <th class="num">Precio</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->parts as $part)
                <tr>
                    <td>{{ $part->product?->name ?? '—' }}</td>
                    <td class="ctr">{{ $part->quantity }}</td>
                    <td class="num">{{ $money($part->unit_price) }}</td>
                    <td class="num">{{ $money($part->quantity * $part->unit_price) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty">Sin repuestos.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="totes">
            <table>
                <tr><td>Subtotal servicios</td><td class="num">{{ $money($order->subtotal_services) }}</td></tr>
                <tr><td>Subtotal repuestos</td><td class="num">{{ $money($order->subtotal_parts) }}</td></tr>
                @if($order->discount)<tr><td>Descuento</td><td class="num">-{{ $money($order->discount) }}</td></tr>@endif
                @if($order->tax)<tr><td>Impuesto</td><td class="num">{{ $money($order->tax) }}</td></tr>@endif
                <tr class="grand"><td>TOTAL</td><td class="num">{{ $money($order->total) }}</td></tr>
                <tr><td>Pagado</td><td class="num">{{ $money($order->paid_amount) }}</td></tr>
                @if($order->balance > 0)
                <tr class="bal"><td>Saldo</td><td class="num">{{ $money($order->balance) }}</td></tr>
                @endif
            </table>
        </div>

        <div class="signs">
            <div class="s">Firma del cliente</div>
            <div class="s">Firma / sello del taller</div>
        </div>

        <div class="foot">
            {{ $company?->name ?? config('brand.name') }} · OT {{ $order->code }} · Impreso {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
        window.addEventListener('afterprint', function () { if (window.opener) setTimeout(() => window.close(), 300); });
    </script>
</body>
</html>
