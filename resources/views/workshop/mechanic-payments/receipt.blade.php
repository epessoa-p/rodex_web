<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante de pago — {{ $payment->mechanic?->name }}</title>
    @php
        $fmt = fn ($n) => money((float) $n, $company?->currency);
        $logo = $company?->logo_url ?? asset(config('brand.logo'));
        $sourceLabel = $payment->payment_source === 'treasury'
            ? 'Tesorería' . ($payment->treasuryAccount ? ' · ' . $payment->treasuryAccount->name : '')
            : 'Caja';
    @endphp
    <style>
        :root { --ink:#1a1c22; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing:border-box; }
        body { margin:0; background:#f3f4f6; color:var(--ink); font-family:"Segoe UI",Roboto,Arial,sans-serif; }
        .sheet { max-width:640px; margin:16px auto; background:#fff; border:1px solid var(--line); border-radius:14px; padding:24px 28px; }
        .top { display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--ink); padding-bottom:12px; margin-bottom:14px; }
        .top img { max-height:48px; }
        .top h1 { font-size:1.05rem; margin:0; }
        .doc { text-align:right; }
        .doc .t { font-size:1.15rem; font-weight:800; }
        .doc .n { color:var(--muted); font-size:.85rem; }
        .meta { display:grid; grid-template-columns:1fr 1fr; gap:6px 18px; font-size:.9rem; margin-bottom:16px; }
        .meta .k { color:var(--muted); }
        table { width:100%; border-collapse:collapse; font-size:.9rem; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid var(--line); }
        th:last-child, td:last-child { text-align:right; }
        tfoot td { font-weight:800; border-top:2px solid var(--ink); border-bottom:0; font-size:1.05rem; }
        .notes { margin-top:14px; font-size:.85rem; color:var(--muted); }
        .sign { margin-top:48px; display:flex; justify-content:space-between; gap:40px; }
        .sign div { flex:1; text-align:center; border-top:1px solid var(--ink); padding-top:6px; font-size:.8rem; color:var(--muted); }
        .bar { max-width:640px; margin:0 auto 16px; display:flex; gap:8px; }
        .btn { border:0; border-radius:8px; padding:.5rem .9rem; font-size:.9rem; cursor:pointer; text-decoration:none; }
        .btn-print { background:#16a34a; color:#fff; }
        .btn-back { background:#e5e7eb; color:#111; }
        @media print { .bar { display:none; } body { background:#fff; } .sheet { border:0; margin:0; } }
    </style>
</head>
<body>
    <div class="bar">
        <button class="btn btn-print" onclick="window.print()"><span>🖨️</span> Imprimir / Guardar PDF</button>
        <a class="btn btn-back" href="{{ route('workshop.mechanic-payments.show', $payment->mechanic_id) }}">Volver</a>
    </div>

    <div class="sheet">
        <div class="top">
            <div style="display:flex;align-items:center;gap:12px;">
                <img src="{{ $logo }}" alt="">
                <h1>{{ $company?->name ?? config('brand.name') }}</h1>
            </div>
            <div class="doc">
                <div class="t">Comprobante de pago</div>
                <div class="n">N.º {{ $payment->id }}</div>
            </div>
        </div>

        <div class="meta">
            <div><span class="k">Mecánico:</span> <strong>{{ $payment->mechanic?->name }}</strong></div>
            <div><span class="k">Fecha:</span> {{ optional($payment->payment_date)->format('d/m/Y') }}</div>
            <div><span class="k">Método:</span> {{ ucfirst($payment->method ?? 'efectivo') }}</div>
            <div><span class="k">Origen:</span> {{ $sourceLabel }}</div>
        </div>

        <table>
            <thead>
                <tr><th>OT</th><th>Fecha</th><th>Comisión</th></tr>
            </thead>
            <tbody>
                @forelse($payment->workOrders as $o)
                <tr>
                    <td>{{ $o->code }}</td>
                    <td>{{ optional($o->delivered_at ?? $o->reception_date)->format('d/m/Y') }}</td>
                    <td>{{ $fmt($o->commission_amount ?? 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="color:var(--muted)">Pago sin OTs vinculadas.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr><td colspan="2">Total pagado</td><td>{{ $fmt($payment->amount) }}</td></tr>
            </tfoot>
        </table>

        @if($payment->notes)
        <div class="notes"><strong>Notas:</strong> {{ $payment->notes }}</div>
        @endif

        <div class="sign">
            <div>Entregado por</div>
            <div>Recibí conforme — {{ $payment->mechanic?->name }}</div>
        </div>
    </div>
</body>
</html>
