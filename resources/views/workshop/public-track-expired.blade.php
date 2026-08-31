<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enlace expirado — {{ $company?->name ?? config('brand.name') }}</title>
    @php $logo = $company?->logo_url ?? asset(config('brand.logo')); @endphp
    <style>
        body { margin:0; background:#f3f4f6; color:#1a1c22; font-family:"Segoe UI",Roboto,Arial,sans-serif; }
        .box { max-width:460px; margin:12vh auto; background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:32px 28px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .box img { max-height:52px; margin-bottom:14px; }
        .ic { font-size:2.4rem; }
        h1 { font-size:1.1rem; margin:.5rem 0; }
        p { color:#6b7280; font-size:.92rem; }
    </style>
</head>
<body>
    <div class="box">
        <img src="{{ $logo }}" alt="">
        <div class="ic">⏳</div>
        <h1>Enlace de seguimiento expirado</h1>
        <p>El enlace de esta orden de trabajo ya no está disponible. Si necesitas información, comunícate con {{ $company?->name ?? 'el taller' }}.</p>
    </div>
</body>
</html>
