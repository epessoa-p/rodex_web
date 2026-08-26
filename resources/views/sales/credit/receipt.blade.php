@php
    $company = $sale->company;
    $branch  = $sale->branch;
    $money   = fn ($n) => money($n, $company?->currency);
    $dateC   = \Illuminate\Support\Carbon::parse($date);
    $methodLabel = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'][$method] ?? ucfirst($method);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de cobro {{ $sale->code }}</title>
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
        .muted  { color: #333; }
        .biz-name { font-size: 15px; font-weight: 800; letter-spacing: .3px; }
        .biz-sub  { font-size: 10px; }
        .head { display: flex; align-items: center; gap: 6px; }
        .head .logo { flex: 0 0 auto; }
        .head .logo img { width: 26mm; height: auto; max-height: 26mm; object-fit: contain; display: block; }
        .head .biz { flex: 1 1 auto; text-align: right; min-width: 0; }
        .sep { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        .doc-title { text-align: center; font-size: 12.5px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; margin: 2px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row > span:last-child { text-align: right; white-space: nowrap; }
        .amount-box { border: 1px solid #000; border-radius: 4px; padding: 5px 8px; margin: 6px 0; text-align: center; }
        .amount-box .lbl { font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; }
        .amount-box .val { font-size: 18px; font-weight: 800; }
        .totals .row { font-size: 11px; }
        .totals .grand { font-size: 13px; font-weight: 800; border-top: 1px solid #000; padding-top: 3px; margin-top: 3px; }
        .foot { font-size: 9.5px; margin-top: 8px; }
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
    <div class="head">
        @if($company?->logo_file)
        <div class="logo"><img src="{{ $company->logo_url }}" alt="{{ $company->name }}"></div>
        @endif
        <div class="biz">
            <div class="biz-name">{{ $company?->name ?? config('brand.name') }}</div>
            @if($company?->description)
            <div class="biz-sub muted">{{ $company->description }}</div>
            @endif
            @if($branch?->name)<div class="biz-sub bold">{{ $branch->name }}</div>@endif
            @if($branch?->address)<div class="biz-sub">{{ $branch->address }}</div>@elseif($company?->address)<div class="biz-sub">{{ $company->address }}</div>@endif
            @php $tel = $branch?->phone ?: $company?->phone; @endphp
            @if($tel)<div class="biz-sub">Tel: {{ $tel }}</div>@endif
            @if($company?->ruc)<div class="biz-sub">NIT: {{ $company->ruc }}</div>@endif
        </div>
    </div>

    <hr class="sep">

    <div class="doc-title">Recibo de cobro</div>

    <hr class="sep">

    {{-- ── Datos del cobro ─────────────────────────── --}}
    <div class="row"><span class="bold">Venta</span><span class="bold">{{ $sale->code }}</span></div>
    <div class="row"><span class="muted">Fecha</span><span>{{ $dateC->format('d/m/Y') }} {{ now()->format('H:i') }}</span></div>
    <div class="row"><span class="muted">Cliente</span><span>{{ $sale->client_name }}</span></div>
    @if($sale->client?->id_number)
    <div class="row"><span class="muted">Doc.</span><span>{{ $sale->client->id_number }}</span></div>
    @endif
    <div class="row"><span class="muted">Método</span><span>{{ $methodLabel }}</span></div>
    @if($sale->createdBy)
    <div class="row"><span class="muted">Atendió</span><span>{{ auth()->user()->name }}</span></div>
    @endif

    {{-- ── Monto cobrado ───────────────────────────── --}}
    <div class="amount-box">
        <div class="lbl">Monto cobrado</div>
        <div class="val">{{ $money($amount) }}</div>
    </div>

    <hr class="sep">

    {{-- ── Estado de cuenta de la venta ────────────── --}}
    <div class="totals">
        <div class="row"><span class="muted">Total venta</span><span>{{ $money($sale->total) }}</span></div>
        <div class="row"><span class="muted">Pagado acumulado</span><span>{{ $money($sale->paid_amount) }}</span></div>
        <div class="row grand">
            <span>Saldo</span>
            <span>{{ $money($sale->balance) }}</span>
        </div>
    </div>

    <hr class="sep">

    <div class="center foot">
        <div class="bold">¡Gracias por su pago!</div>
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
    // Auto-imprime al cargar (ventana nueva o dentro de un iframe).
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
