<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de Recompensas — {{ $company->name ?? 'VR Motors' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @php
        $logo = $company?->logo ? asset('storage/' . $company->logo) : asset('images/logo_blanco.jpeg');
        $money = fn ($n) => '$' . number_format((float) $n, 2);
        $ruleAmount = rtrim(rtrim(number_format((float) $settings->earn_amount, 2), '0'), '.');
    @endphp
    <style>
        :root { --brand:#e10600; }
        * { box-sizing: border-box; }
        body { background:#f3f4f6; color:#111; font-family:"Segoe UI",Arial,sans-serif; margin:0; }
        .sheet { max-width: 920px; margin: 0 auto; background:#fff; }
        .cat-hero {
            background: linear-gradient(120deg,#0a0a0a 0%, #1a1a1a 60%, var(--brand) 140%);
            color:#fff; padding: 28px 32px; display:flex; align-items:center; gap:20px;
        }
        .cat-hero img { width:84px; height:84px; object-fit:contain; background:#fff; border-radius:12px; padding:6px; }
        .cat-hero .titles { flex:1; }
        .cat-hero h1 { font-size:1.6rem; font-weight:800; margin:0; }
        .cat-hero .sub { opacity:.85; font-size:.9rem; }
        .cat-qr { background:#fff; padding:8px; border-radius:10px; text-align:center; line-height:0; }
        .cat-qr canvas, .cat-qr img { display:block; }
        .cat-qr small { display:block; color:#444; font-size:.6rem; margin-top:4px; line-height:1.1; }
        .rule-bar {
            background:#fff8e6; border-bottom:1px solid #f1e3b8; color:#7a5b00;
            padding:12px 32px; font-size:.95rem; display:flex; align-items:center; gap:10px;
        }
        .cat-body { padding: 24px 32px 40px; }
        .grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; }
        .reward {
            border:1.5px solid #ececec; border-radius:14px; overflow:hidden; background:#fff;
            display:flex; flex-direction:column;
        }
        .reward .thumb { height:150px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; }
        .reward .thumb img { width:100%; height:100%; object-fit:cover; }
        .reward .thumb i { font-size:2.6rem; color:#cfcfcf; }
        .reward .info { padding:12px 14px; flex:1; display:flex; flex-direction:column; }
        .reward .name { font-weight:700; font-size:.95rem; margin-bottom:4px; }
        .reward .desc { color:#666; font-size:.8rem; flex:1; }
        .reward .cost {
            margin-top:10px; align-self:flex-start; background:rgba(225,6,0,.08); color:var(--brand);
            border:1px solid rgba(225,6,0,.2); border-radius:50rem; padding:4px 12px; font-weight:800; font-size:.9rem;
        }
        .cat-foot { text-align:center; color:#888; font-size:.82rem; padding: 8px 32px 28px; }
        .toolbar { position:sticky; top:0; background:#111; color:#fff; padding:10px 16px; display:flex; gap:8px; justify-content:center; }
        .toolbar button, .toolbar a { border:0; border-radius:8px; padding:8px 16px; font-size:.9rem; cursor:pointer; text-decoration:none; }
        .btn-pdf { background:var(--brand); color:#fff; }
        .btn-ghost { background:#333; color:#fff; }
        @media (max-width: 700px) { .grid { grid-template-columns: repeat(2, 1fr); } .cat-hero { padding:20px; } }
        @media print {
            .toolbar { display:none !important; }
            body { background:#fff; }
            @page { size: A4; margin: 12mm; }
            .reward { break-inside: avoid; }
            .cat-hero { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-pdf" onclick="window.print()"><i class="bi bi-download"></i> Descargar PDF</button>
    </div>

    <div class="sheet">
        <div class="cat-hero">
            <img src="{{ $logo }}" alt="Logo">
            <div class="titles">
                <h1>Catálogo de Recompensas</h1>
                <div class="sub">{{ $company->name ?? 'VR Motors' }} · Programa de fidelización</div>
            </div>
            <div class="cat-qr">
                <div id="catalogQr" data-url="{{ url()->current() }}"></div>
                <small>Escanéame</small>
            </div>
        </div>

        <div class="rule-bar">
            <i class="bi bi-stars"></i>
            <span>Gana <strong>{{ $settings->earn_points }} {{ $settings->points_label }}</strong> por cada
                <strong>{{ $money($ruleAmount) }}</strong> de compra y canjéalos por estos premios.</span>
        </div>

        <div class="cat-body">
            @if($rewards->isEmpty())
                <p class="text-center text-muted py-5">Pronto tendremos recompensas disponibles.</p>
            @else
            <div class="grid">
                @foreach($rewards as $r)
                <div class="reward">
                    <div class="thumb">
                        @if($r->image_url)<img src="{{ $r->image_url }}" alt="{{ $r->name }}">@else<i class="bi bi-gift"></i>@endif
                    </div>
                    <div class="info">
                        <div class="name">{{ $r->name }}</div>
                        @if($r->description)<div class="desc">{{ $r->description }}</div>@endif
                        <span class="cost">{{ number_format($r->points_cost, 0) }} {{ $settings->points_label }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="cat-foot">
            @php $tel = $company?->phone; @endphp
            {{ $company->name ?? 'VR Motors' }}@if($tel) · Tel: {{ $tel }}@endif @if($company?->address) · {{ $company->address }}@endif
            <div>Precios en puntos sujetos a disponibilidad.</div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        (function () {
            const el = document.getElementById('catalogQr');
            if (!el || typeof QRCode === 'undefined') return;
            new QRCode(el, { text: el.dataset.url, width: 92, height: 92, correctLevel: QRCode.CorrectLevel.M });
        })();
    </script>
    @if($autoPrint)
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 700));</script>
    @endif
</body>
</html>
