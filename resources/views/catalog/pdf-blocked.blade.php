<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elige un filtro — {{ $branch->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
    <div class="container" style="max-width:520px">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 text-center">
                <div class="mb-3"><i class="bi bi-images text-warning" style="font-size:2.6rem"></i></div>
                <h1 class="h5 fw-bold">Elige qué productos incluir</h1>
                <p class="text-muted">{{ $blocked }}</p>
                <a href="{{ route('catalog.public', array_merge(['token' => $branch->public_token], array_diff_key($filterQuery ?? [], ['fotos' => 1]))) }}#armar"
                   class="btn btn-dark"><i class="bi bi-funnel me-1"></i>Volver y elegir filtros</a>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">{{ $company?->name }} · {{ $branch->name }}</p>
    </div>
</body>
</html>