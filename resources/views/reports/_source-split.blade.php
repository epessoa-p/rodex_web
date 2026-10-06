{{-- "Caja X · Tesorería Y" bajo un total del estado de resultados: la pestaña
     Movimientos y los cierres solo ven la caja; aquí se ve de dónde sale cada parte. --}}
@if($cash !== null && $treasury !== null && ((float) $cash > 0 || (float) $treasury > 0))
<tr class="small text-muted">
    <td class="ps-4 py-1 fw-normal" colspan="2">
        <i class="bi bi-cash-coin me-1"></i>Caja {{ money($cash) }}
        <span class="mx-2">·</span>
        <i class="bi bi-bank me-1"></i>Tesorería {{ money($treasury) }}
    </td>
</tr>
@endif
