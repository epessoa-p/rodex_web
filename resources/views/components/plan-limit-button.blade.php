@props([
    'status',       // array de Controller::planLimitStatus()
    'href',         // ruta destino cuando hay cupo
    'label',        // texto del botón
    'resource',     // etiqueta del recurso para el aviso (ej. "sucursales")
    'icon' => 'bi-plus-lg',
    'class' => 'btn btn-primary',
])

@if($status['reached'] ?? false)
    <div class="text-end">
        <button type="button" class="{{ $class }}" disabled
                title="Alcanzaste el límite de tu plan">
            <i class="bi {{ $icon }}"></i> {{ $label }}
        </button>
        <div class="small text-danger mt-1">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Alcanzaste el límite de {{ $status['max'] }} {{ $resource }} de tu plan.
            Contacta a tu proveedor para ampliarlo.
        </div>
    </div>
@else
    <a href="{{ $href }}" class="{{ $class }}">
        <i class="bi {{ $icon }}"></i> {{ $label }}
    </a>
@endif
