@extends('layouts.app')
@section('title', 'Reiniciar sistema')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>Reiniciar sistema</h1>
            <p class="text-muted mb-0 small">Borra los datos operativos para empezar desde cero, conservando la configuración base.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Aviso fuerte --}}
    <div class="alert alert-danger d-flex align-items-start gap-3 border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
        <div>
            <div class="fw-bold mb-1">Acción irreversible</div>
            <div class="small">Esto eliminará permanentemente los datos operativos de <strong>todas las empresas</strong>.
                No se puede deshacer. Asegúrate de tener una copia de seguridad si la necesitas.</div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Se elimina --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid var(--brand-red) !important;">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold text-danger"><i class="bi bi-trash3 me-2"></i>Se eliminará</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-2">
                        @foreach($counts as $label => $n)
                        <div class="col-6">
                            <div class="d-flex justify-content-between align-items-center rounded-3 border px-2 py-1" style="font-size:.8rem;">
                                <span class="text-muted text-truncate">{{ $label }}</span>
                                <span class="fw-bold">{{ number_format($n) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <ul class="mb-0 ps-3 text-muted" style="font-size:.8rem;">
                        <li>Clientes, ventas, cotizaciones y devoluciones</li>
                        <li>Compras, recepciones y cuentas por pagar/cobrar</li>
                        <li>Productos, stock y movimientos de inventario</li>
                        <li>Taller (órdenes, repuestos, pagos)</li>
                        <li>Alquileres (contratos, cuotas, inspecciones, pagos)</li>
                        <li>Unidades de moto y garantías</li>
                        <li>Sesiones y movimientos de caja</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Se conserva --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #16a34a !important;">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <h6 class="mb-0 fw-semibold text-success"><i class="bi bi-shield-check me-2"></i>Se conservará</h6>
                </div>
                <div class="card-body p-3">
                    <ul class="mb-0 ps-3 text-muted" style="font-size:.8rem;">
                        <li>Empresas, usuarios, roles y permisos</li>
                        <li>Personal, promotores, mecánicos y cargos</li>
                        <li>Sucursales, cajas y almacenes</li>
                        <li>Catálogos: categorías, marcas, modelos de moto</li>
                        <li>Proveedores, servicios y servicios de gasto</li>
                        <li>Planes de pago y plantillas de documento</li>
                        <li>Cuentas de tesorería <span class="text-muted">(su saldo se pone en 0)</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmación --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('system.reset.run') }}" method="POST"
                  onsubmit="return confirm('¿Seguro que deseas REINICIAR la base de datos? Esta acción es irreversible.');">
                @csrf
                <label class="form-label fw-semibold" for="confirmation">
                    Para confirmar, escribe <span class="text-danger fw-bold">REINICIAR</span>:
                </label>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" id="confirmation" name="confirmation" autocomplete="off"
                           class="form-control @error('confirmation') is-invalid @enderror"
                           placeholder="REINICIAR" style="max-width:260px;" required>
                    <button type="submit" class="btn btn-danger px-4" id="btnReset" disabled>
                        <i class="bi bi-exclamation-octagon me-1"></i>Reiniciar base de datos
                    </button>
                </div>
                @error('confirmation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    var input = document.getElementById('confirmation');
    var btn   = document.getElementById('btnReset');
    if (!input || !btn) return;
    input.addEventListener('input', function () {
        btn.disabled = this.value.trim().toUpperCase() !== 'REINICIAR';
    });
})();
</script>
@endpush
@endsection
