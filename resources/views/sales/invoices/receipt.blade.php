@php
    $company = $sale->company;
    $branch  = $sale->branch;
    $itemsSubtotal = $sale->items->sum(fn ($i) => $i->quantity * $i->unit_price - ($i->discount ?? 0));
    $money = fn ($n) => '$' . number_format((float) $n, 2);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo {{ $sale->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 80mm auto; margin: 0; }
        html, body {
            width: 80mm;
            background: #fff;
            color: #000;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }
        .ticket { width: 80mm; padding: 4mm 3mm 6mm; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: 700; }
        .head { display: flex; align-items: center; gap: 6px; }
        .head .logo { flex: 0 0 auto; }
        .head .logo img { width: 26mm; height: auto; max-height: 26mm; object-fit: contain; display: block; }
        .head .biz { flex: 1 1 auto; text-align: right; min-width: 0; }
        .muted  { color: #333; }
        .biz-name { font-size: 15px; font-weight: 800; letter-spacing: .3px; }
        .biz-sub  { font-size: 10px; }
        .sep { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row > span:last-child { text-align: right; white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1px 0; vertical-align: top; }
        thead th { font-size: 9.5px; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 2px; }
        .it-name { font-size: 10.5px; }
        .it-line { font-size: 9.5px; color: #222; }
        .col-qty { width: 26%; }
        .col-sub { width: 28%; text-align: right; white-space: nowrap; }
        .totals .row { font-size: 11px; }
        .totals .grand { font-size: 14px; font-weight: 800; border-top: 1px solid #000; padding-top: 3px; margin-top: 3px; }
        .foot { font-size: 9.5px; margin-top: 8px; }
        .badge-credit { display: inline-block; border: 1px solid #000; padding: 1px 6px; font-size: 9.5px; font-weight: 700; border-radius: 3px; }
        @media screen {
            body { background: #e9ecef; padding: 18px 0; }
            .ticket { margin: 0 auto; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,.15); }
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="ticket">

    {{-- ── Encabezado: logo a la izquierda, datos a la derecha ── --}}
    @php $logoPath = public_path('images/logo_blanco.jpeg'); @endphp
    <div class="head">
        @if(file_exists($logoPath))
        <div class="logo"><img src="{{ asset('images/logo_blanco.jpeg') }}" alt="Logo"></div>
        @endif
        <div class="biz">
            <div class="biz-name">{{ $company->name ?? 'VR Motors' }}</div>
            <div class="biz-sub muted">Repuestos &amp; Accesorios</div>
            @if($branch?->name)<div class="biz-sub bold">{{ $branch->name }}</div>@endif
            @if($branch?->address)<div class="biz-sub">{{ $branch->address }}</div>@elseif($company?->address)<div class="biz-sub">{{ $company->address }}</div>@endif
            @php $tel = $branch?->phone ?: $company?->phone; @endphp
            @if($tel)<div class="biz-sub">Tel: {{ $tel }}</div>@endif
            @if($company?->ruc)<div class="biz-sub">NIT: {{ $company->ruc }}</div>@endif
        </div>
    </div>

    <hr class="sep">

    {{-- ── Datos de la venta ───────────────────────── --}}
    <div class="row"><span class="bold">Recibo</span><span class="bold">{{ $sale->code }}</span></div>
    <div class="row"><span class="muted">Fecha</span><span>{{ $sale->sale_date->format('d/m/Y') }} {{ $sale->created_at->format('H:i') }}</span></div>
    <div class="row"><span class="muted">Cliente</span><span>{{ $sale->client_name }}</span></div>
    @if($sale->client?->id_number)
    <div class="row"><span class="muted">Doc.</span><span>{{ $sale->client->id_number }}</span></div>
    @endif
    @if($sale->createdBy)
    <div class="row"><span class="muted">Atendió</span><span>{{ $sale->createdBy->name }}</span></div>
    @endif
    <div class="row">
        <span class="muted">Tipo</span>
        <span>{{ $sale->sale_type_label }}@if($sale->sale_type === 'credit') <span class="badge-credit">CRÉDITO</span>@endif</span>
    </div>

    <hr class="sep">

    {{-- ── Ítems ───────────────────────────────────── --}}
    <table>
        <thead>
            <tr>
                <th style="text-align:left;">Producto</th>
                <th class="col-sub">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            @php $lineSub = $item->quantity * $item->unit_price - ($item->discount ?? 0); @endphp
            <tr>
                <td>
                    <div class="it-name">{{ $item->display_name }}</div>
                    <div class="it-line">
                        {{ number_format($item->quantity, 0) }} x {{ $money($item->unit_price) }}
                        @if($item->discount > 0) (-{{ $money($item->discount) }}) @endif
                    </div>
                </td>
                <td class="col-sub bold" style="vertical-align:bottom;">{{ $money($lineSub) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="sep">

    {{-- ── Totales ─────────────────────────────────── --}}
    <div class="totals">
        <div class="row"><span class="muted">Subtotal</span><span>{{ $money($itemsSubtotal) }}</span></div>
        @if($sale->discount > 0)
        <div class="row"><span class="muted">Descuento</span><span>-{{ $money($sale->discount) }}</span></div>
        @endif
        @if($sale->tax > 0)
        <div class="row"><span class="muted">Impuesto</span><span>{{ $money($sale->tax) }}</span></div>
        @endif
        @if($sale->interest > 0)
        <div class="row"><span class="muted">Recargo</span><span>{{ $money($sale->interest) }}</span></div>
        @endif
        <div class="row grand"><span>TOTAL</span><span>{{ $money($sale->total) }}</span></div>
        <div class="row"><span class="muted">Pagado</span><span>{{ $money($sale->paid_amount) }}</span></div>
        @if($sale->balance > 0)
        <div class="row bold"><span>Saldo</span><span>{{ $money($sale->balance) }}</span></div>
        @endif
    </div>

    {{-- ── Cronograma de cuotas (solo crédito) ─────── --}}
    @if($sale->sale_type === 'credit' && $sale->installments->isNotEmpty())
    <hr class="sep">
    <div class="bold center" style="margin-bottom:3px;">CRONOGRAMA DE CUOTAS</div>
    <table>
        <thead>
            <tr>
                <th style="text-align:left;">#</th>
                <th style="text-align:left;">Vencimiento</th>
                <th class="col-sub">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->installments as $inst)
            <tr>
                <td>{{ $inst->number }}</td>
                <td>{{ $inst->due_date->format('d/m/Y') }}</td>
                <td class="col-sub bold">{{ $money($inst->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <hr class="sep">

    <div class="center foot">
        <div class="bold">¡Gracias por su compra!</div>
        <div class="muted">Conserve este recibo.</div>
        <div class="muted" style="margin-top:4px;">{{ now()->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Botones solo en pantalla (no al imprimir) --}}
    <div class="no-print center" style="margin-top:14px;">
        <button onclick="window.print()" style="padding:6px 14px;font-size:12px;cursor:pointer;">🖨️ Imprimir</button>
        <button onclick="window.close()" style="padding:6px 14px;font-size:12px;cursor:pointer;">Cerrar</button>
    </div>
</div>

<script>
    // Auto-imprime al cargar (sirve tanto en ventana nueva como dentro de un iframe).
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 250);
    });
    // Cierra solo si fue abierto como ventana emergente (no dentro de un iframe).
    window.addEventListener('afterprint', function () {
        if (window.opener && !window.frameElement) {
            setTimeout(function () { window.close(); }, 300);
        }
    });
</script>
</body>
</html>
