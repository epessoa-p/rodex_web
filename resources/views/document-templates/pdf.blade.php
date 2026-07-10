<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTemplate->name }} - PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1f2937;
            margin: 24px;
        }
        .header {
            margin-bottom: 16px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 12px;
        }
        .title {
            font-size: 22px;
            margin: 0 0 8px 0;
        }
        .meta {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }
        .content {
            margin-top: 18px;
            line-height: 1.5;
            font-size: 14px;
        }
        .toolbar {
            position: fixed;
            right: 12px;
            top: 12px;
            display: flex;
            gap: 6px;
        }
        .toolbar button {
            border: 1px solid #d1d5db;
            background: white;
            padding: 7px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
        }
        @media print {
            .toolbar {
                display: none;
            }
            body {
                margin: 12mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Imprimir / Guardar PDF</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <div class="header">
        <h1 class="title">{{ $documentTemplate->name }}</h1>
        <p class="meta">Tipo: {{ $documentTemplate->type_label }}</p>
        <p class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="content">
        {!! $renderedContent !!}
    </div>
</body>
</html>
