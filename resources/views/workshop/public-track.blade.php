<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguimiento de OT {{ $order->code }} — {{ $company?->name ?? config('brand.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @php
        use App\Models\Workshop\WorkOrder;
        $fmt = fn ($n) => money((float) $n, $company?->currency);
        $logo = $company?->logo_url ?? asset(config('brand.logo'));

        // Pasos del avance (excluye 'anulada'); 'entregada' es el final.
        $steps = [
            'recibida'      => ['Recibida',      'bi-box-arrow-in-down'],
            'diagnosticada' => ['Diagnosticada', 'bi-search'],
            'en_proceso'    => ['En proceso',    'bi-gear-wide-connected'],
            'terminada'     => ['Terminada',     'bi-check2-circle'],
            'entregada'     => ['Entregada',     'bi-flag'],
        ];
        $order_keys = array_keys($steps);
        $currentIdx = array_search($order->status, $order_keys);
        if ($currentIdx === false) $currentIdx = 0;

        $statusMeta = WorkOrder::STATUSES[$order->status] ?? ['label' => $order->status, 'color' => 'secondary'];
        $payMeta = WorkOrder::PAYMENT_STATUSES[$order->payment_status] ?? ['label' => $order->payment_status, 'color' => 'secondary'];
        $veh = $order->vehicle;
        $vehName = $veh ? trim(($veh->brand ?? '').' '.($veh->model ?? '')) : null;
    @endphp
    <style>
        :root { --brand:#e63946; --ink:#1a1c22; --muted:#6b7280; --line:#e5e7eb; }
        * { box-sizing:border-box; }
        body { margin:0; background:#f3f4f6; color:var(--ink); font-family:"Segoe UI",Roboto,Arial,sans-serif; }
        .wrap { max-width:560px; margin:0 auto; padding:16px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:18px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .hero { text-align:center; padding-top:22px; }
        .hero img { max-height:56px; max-width:70%; object-fit:contain; }
        .hero h1 { font-size:1.05rem; margin:.5rem 0 .1rem; }
        .hero .sub { color:var(--muted); font-size:.85rem; }
        .code { font-size:1.5rem; font-weight:800; letter-spacing:.02em; }
        .badge { display:inline-block; padding:.28rem .7rem; border-radius:999px; font-size:.8rem; font-weight:700; }
        .b-info{background:#e0f2fe;color:#075985}.b-primary{background:#dbeafe;color:#1e40af}
        .b-warning{background:#fef3c7;color:#92400e}.b-success{background:#dcfce7;color:#166534}
        .b-dark{background:#e5e7eb;color:#1f2937}.b-danger{background:#fee2e2;color:#991b1b}.b-secondary{background:#eef2f7;color:#374151}
        /* Timeline */
        .steps { list-style:none; margin:0; padding:0; }
        .steps li { display:flex; gap:12px; position:relative; padding-bottom:18px; }
        .steps li:last-child { padding-bottom:0; }
        .steps li .dot { flex:0 0 34px; height:34px; border-radius:50%; display:grid; place-items:center; background:#eef2f7; color:#9aa3af; border:2px solid #eef2f7; z-index:1; }
        .steps li .bar { position:absolute; left:16px; top:34px; bottom:-2px; width:2px; background:var(--line); }
        .steps li:last-child .bar { display:none; }
        .steps li.done .dot { background:var(--brand); border-color:var(--brand); color:#fff; }
        .steps li.done .bar { background:var(--brand); }
        .steps li.current .dot { box-shadow:0 0 0 4px rgba(230,57,70,.18); }
        .steps li .lbl { font-weight:600; padding-top:5px; }
        .steps li.pending .lbl { color:var(--muted); font-weight:500; }
        .row2 { display:flex; justify-content:space-between; gap:10px; padding:.35rem 0; border-bottom:1px dashed var(--line); font-size:.92rem; }
        .row2:last-child { border-bottom:0; }
        .row2 .k { color:var(--muted); }
        .row2 .v { font-weight:600; text-align:right; }
        .sec-title { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); font-weight:700; margin:0 0 8px; }
        .li { display:flex; justify-content:space-between; padding:.3rem 0; font-size:.92rem; border-bottom:1px dashed var(--line); }
        .li:last-child { border-bottom:0; }
        .total { display:flex; justify-content:space-between; font-weight:800; font-size:1.1rem; margin-top:6px; }
        .foot { text-align:center; color:var(--muted); font-size:.78rem; padding:8px 0 24px; }
        .photos { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .photos figure { margin:0; }
        .photos img { width:100%; aspect-ratio:1; object-fit:cover; border-radius:10px; cursor:zoom-in; display:block; }
        .photos figcaption { font-size:.72rem; color:var(--ink); margin-top:3px; line-height:1.2; }
        .lb { position:fixed; inset:0; background:rgba(0,0,0,.92); display:none; align-items:center; justify-content:center; flex-direction:column; z-index:60; padding:16px; }
        .lb.open { display:flex; }
        .lb img { max-width:100%; max-height:78vh; object-fit:contain; border-radius:8px; }
        .lb .cap { color:#fff; margin-top:12px; font-size:.92rem; text-align:center; max-width:620px; }
        .lb .x { position:absolute; top:12px; right:18px; color:#fff; font-size:1.8rem; line-height:1; cursor:pointer; }
        .note { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; border-radius:12px; padding:10px 12px; font-size:.85rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <img src="{{ $logo }}" alt="{{ $company?->name }}">
            <h1>{{ $company?->name ?? config('brand.name') }}</h1>
            <div class="sub">Seguimiento de tu orden de trabajo</div>
        </div>

        <div class="card" style="margin-top:14px; text-align:center;">
            <div class="code">{{ $order->code }}</div>
            <div style="margin-top:8px;">
                <span class="badge b-{{ $statusMeta['color'] }}">{{ $statusMeta['label'] }}</span>
            </div>
            @if($vehName || ($veh && $veh->plate))
            <div style="margin-top:10px; color:var(--muted);">
                <i class="bi bi-bicycle"></i>
                {{ $vehName }}@if($veh && $veh->plate) · <strong>{{ $veh->plate }}</strong>@endif
            </div>
            @endif
        </div>

        {{-- Avance --}}
        <div class="card">
            <p class="sec-title">Estado</p>
            <ul class="steps">
                @foreach($steps as $key => [$label, $icon])
                    @php
                        $idx = array_search($key, $order_keys);
                        $state = $idx < $currentIdx ? 'done' : ($idx === $currentIdx ? 'done current' : 'pending');
                    @endphp
                    <li class="{{ $state }}">
                        <span class="bar"></span>
                        <span class="dot"><i class="bi {{ $icon }}"></i></span>
                        <span class="lbl">{{ $label }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Datos --}}
        <div class="card">
            <div class="row2"><span class="k">Recepción</span><span class="v">{{ optional($order->reception_date)->format('d/m/Y') ?? '—' }}</span></div>
            @if($order->mechanic)<div class="row2"><span class="k">Mecánico</span><span class="v">{{ $order->mechanic->name }}</span></div>@endif
            @if($order->status === 'entregada' && $order->delivered_at)
            <div class="row2"><span class="k">Entregada</span><span class="v">{{ $order->delivered_at->format('d/m/Y H:i') }}</span></div>
            @endif
            @if($order->reported_issue)
            <div class="row2" style="flex-direction:column; align-items:flex-start;">
                <span class="k">Falla reportada</span>
                <span class="v" style="text-align:left; font-weight:500;">{{ $order->reported_issue }}</span>
            </div>
            @endif
            @if($order->diagnosis)
            <div class="row2" style="flex-direction:column; align-items:flex-start;">
                <span class="k">Diagnóstico</span>
                <span class="v" style="text-align:left; font-weight:500;">{{ $order->diagnosis }}</span>
            </div>
            @endif
        </div>

        {{-- Fotos --}}
        @if($order->photos->isNotEmpty())
        <div class="card">
            <p class="sec-title">Fotos</p>
            <div class="photos">
                @foreach($order->photos as $ph)
                <figure>
                    <img src="{{ $ph->url }}" alt="Foto" data-cap="{{ $ph->caption }}" onclick="openLb(this)">
                    @if($ph->caption)<figcaption>{{ $ph->caption }}</figcaption>@endif
                </figure>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Detalle de trabajos --}}
        @if($order->services->isNotEmpty() || $order->parts->isNotEmpty())
        <div class="card">
            <p class="sec-title">Detalle</p>
            @foreach($order->services as $s)
            <div class="li"><span>{{ $s->description }}@if($s->quantity > 1) ×{{ $s->quantity }}@endif</span><span>{{ $fmt($s->subtotal) }}</span></div>
            @endforeach
            @foreach($order->parts as $p)
            <div class="li"><span>{{ $p->product?->name ?? 'Repuesto' }}@if($p->quantity > 1) ×{{ $p->quantity }}@endif</span><span>{{ $fmt($p->subtotal) }}</span></div>
            @endforeach
            <div class="total"><span>Total</span><span>{{ $fmt($order->total) }}</span></div>
            <div style="text-align:right; margin-top:6px;">
                <span class="badge b-{{ $payMeta['color'] }}">{{ $payMeta['label'] }}</span>
            </div>
        </div>
        @endif

        <div class="foot">
            <i class="bi bi-shield-check"></i> Esta página se actualiza sola. Guarda el enlace para consultar el avance.
        </div>
    </div>

    {{-- Lightbox --}}
    <div class="lb" id="lb" onclick="if(event.target.id==='lb')closeLb()">
        <span class="x" onclick="closeLb()">&times;</span>
        <img id="lbImg" src="" alt="">
        <div class="cap" id="lbCap"></div>
    </div>
    <script>
        function openLb(el){
            document.getElementById('lbImg').src = el.src;
            document.getElementById('lbCap').textContent = el.dataset.cap || '';
            document.getElementById('lb').classList.add('open');
        }
        function closeLb(){ document.getElementById('lb').classList.remove('open'); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeLb(); });
    </script>
</body>
</html>
