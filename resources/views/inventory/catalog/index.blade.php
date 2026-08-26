@extends('layouts.app')

@section('title', 'Catálogo público')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1"><i class="bi bi-qr-code"></i> Catálogo público</h1>
            <p class="text-muted mb-0 small">
                Comparte el enlace o QR de cada sucursal. Los clientes ven productos y precios (solo consulta),
                y en qué otra sucursal hay disponibilidad.
            </p>
        </div>
    </div>

    @if($branches->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>No tienes sucursales activas. Crea una sucursal para publicar su catálogo.
        </div>
    @else
    <div class="row g-3">
        @foreach($branches as $branch)
            @php $url = route('catalog.public', $branch->public_token); @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-1"><i class="bi bi-shop me-1"></i>{{ $branch->name }}</h6>
                        <p class="text-muted small mb-3">{{ $branch->address ?: 'Catálogo de esta sucursal' }}</p>

                        <div class="d-flex justify-content-center mb-3">
                            <div class="p-2 border rounded bg-white qr-box" data-url="{{ $url }}"></div>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <input type="text" class="form-control catalog-link" value="{{ $url }}" readonly>
                            <button class="btn btn-outline-secondary btn-copy" type="button" title="Copiar enlace">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-up-right"></i> Ver
                            </a>
                            <a href="{{ route('catalog.public.pdf', $branch->public_token) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-dark btn-qr-download"
                                    data-name="{{ \Illuminate\Support\Str::slug($branch->name) }}">
                                <i class="bi bi-download"></i> QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    if (typeof QRCode === 'undefined') return;

    // Genera el QR de cada sucursal.
    document.querySelectorAll('.qr-box').forEach(function (box) {
        new QRCode(box, { text: box.dataset.url, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
    });

    // Copiar enlace.
    document.querySelectorAll('.btn-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = btn.closest('.input-group').querySelector('.catalog-link');
            navigator.clipboard.writeText(input.value).then(function () {
                const icon = btn.querySelector('i');
                icon.className = 'bi bi-check-lg text-success';
                setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 1500);
            });
        });
    });

    // Descargar el QR como PNG.
    document.querySelectorAll('.btn-qr-download').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const card = btn.closest('.card');
            const canvas = card.querySelector('.qr-box canvas');
            const img = card.querySelector('.qr-box img');
            const src = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
            if (!src) return;
            const a = document.createElement('a');
            a.href = src;
            a.download = 'qr-catalogo-' + btn.dataset.name + '.png';
            a.click();
        });
    });
})();
</script>
@endpush
