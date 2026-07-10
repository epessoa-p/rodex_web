<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VR Motors')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-red:      #e10600;
            --brand-red-dark: #b30500;
            --brand-black:    #0a0a0a;
            --brand-black-2:  #161616;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: var(--brand-black);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
            -webkit-font-smoothing: antialiased;
        }

        /* Fondo con motivo de pista de carrera (gradientes radiales en rojo) */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(225,6,0,.25) 0%, transparent 70%);
            top: -200px; left: -200px;
        }
        body::after {
            width: 700px; height: 700px;
            background: radial-gradient(circle, rgba(225,6,0,.18) 0%, transparent 70%);
            bottom: -250px; right: -250px;
        }

        /* Línea de velocidad sutil al fondo */
        .speed-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(90deg, transparent 0%, transparent 49%, rgba(225,6,0,.04) 50%, transparent 51%, transparent 100%);
            background-size: 4px 100%;
            z-index: 0;
            opacity: 0.6;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .auth-card {
            background: rgba(22, 22, 22, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            box-shadow:
                0 30px 60px rgba(0,0,0,.5),
                0 0 0 1px rgba(225,6,0,.15),
                inset 0 1px 0 rgba(255,255,255,.04);
            padding: 38px 36px;
            position: relative;
            overflow: hidden;
        }

        /* Acento rojo arriba */
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent 0%, var(--brand-red) 50%, transparent 100%);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo {
            width: 110px;
            height: 110px;
            background: #000;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,.45), inset 0 1px 0 rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.06);
            overflow: hidden;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .auth-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .auth-header .brand-accent {
            color: var(--brand-red);
        }

        .auth-header p {
            color: #9a9a9a;
            font-size: 0.85rem;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            color: #d1d1d1;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 0.82rem;
            letter-spacing: 0.01em;
        }

        .input-group {
            border-radius: 10px;
            overflow: hidden;
            transition: all .2s ease;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 3px rgba(225,6,0,.25);
        }

        .input-group-text {
            border: 1px solid rgba(255,255,255,0.08);
            background-color: rgba(255,255,255,0.04);
            color: var(--brand-red);
            border-right: 0;
        }

        .form-control {
            padding: 11px 14px;
            border: 1px solid rgba(255,255,255,0.08);
            border-left: 0;
            border-radius: 0 10px 10px 0;
            font-size: 0.92rem;
            background-color: rgba(255,255,255,0.04);
            color: #fff;
            transition: all .2s ease;
        }
        .input-group .input-group-text:first-child {
            border-radius: 10px 0 0 10px;
        }

        .form-control::placeholder { color: #6a6a6a; }

        .form-control:focus {
            background-color: rgba(255,255,255,0.06);
            border-color: rgba(225,6,0,.5);
            color: #fff;
            box-shadow: none;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--brand-red) 0%, var(--brand-red-dark) 100%);
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.92rem;
            width: 100%;
            transition: all .2s ease;
            margin-top: 8px;
            letter-spacing: 0.01em;
            box-shadow: 0 6px 16px rgba(225,6,0,.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(225,6,0,.45);
            color: white;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border: 1px solid rgba(225,6,0,.2);
            background: rgba(225,6,0,.08);
            color: #ffb4b0;
            border-radius: 10px;
            margin-bottom: 18px;
            padding: 10px 14px;
            font-size: 0.85rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 18px;
            color: #a8a8a8;
            font-size: 0.85rem;
        }

        .remember-me input {
            margin-right: 8px;
            cursor: pointer;
            accent-color: var(--brand-red);
        }

        .auth-footer {
            text-align: center;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.05);
            color: #6a6a6a;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        @media (max-width: 480px) {
            .auth-card  { padding: 28px 22px; }
            .auth-header h1 { font-size: 1.4rem; }
            .logo       { width: 88px; height: 88px; font-size: 1.6rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="speed-lines"></div>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
