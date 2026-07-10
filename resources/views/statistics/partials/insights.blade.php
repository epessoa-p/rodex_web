{{-- Recuadro "Análisis y recomendaciones".
     Props: $items (array de ['severity','icon','title','text', opcional 'tab']) --}}
@php
    $map = [
        'good'    => ['#198754', 'success'],
        'warning' => ['#fd7e14', 'warning'],
        'danger'  => ['#dc3545', 'danger'],
        'info'    => ['#0d6efd', 'primary'],
    ];
    $tabLabels = ['ventas'=>'Ventas','personal'=>'Personal','clientes'=>'Clientes','compras'=>'Compras','inventario'=>'Inventario','taller'=>'Taller','alquileres'=>'Alquileres'];
@endphp
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white border-bottom py-2 px-3">
        <h6 class="mb-0 fw-semibold small"><i class="bi bi-lightbulb me-2 text-warning"></i>Análisis y recomendaciones</h6>
    </div>
    <div class="card-body p-3 d-flex flex-column gap-2">
        @forelse($items as $it)
            @php [$color, $bs] = $map[$it['severity']] ?? $map['info']; @endphp
            <div class="rounded-3 border bg-light p-2 d-flex gap-2" style="border-left:4px solid {{ $color }} !important;">
                <i class="bi {{ $it['icon'] }}" style="color:{{ $color }};font-size:1rem;"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:.82rem;">
                        {{ $it['title'] }}
                        @if(!empty($it['tab']) && isset($tabLabels[$it['tab']]))
                            <span class="badge bg-{{ $bs }}-subtle text-{{ $bs }} border border-{{ $bs }}-subtle ms-1" style="font-size:.6rem;">{{ $tabLabels[$it['tab']] }}</span>
                        @endif
                    </div>
                    <div class="text-muted" style="font-size:.78rem;line-height:1.35;">{{ $it['text'] }}</div>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-3 small"><i class="bi bi-emoji-smile d-block fs-4 mb-1 opacity-50"></i>Sin observaciones para este período.</div>
        @endforelse
    </div>
</div>
