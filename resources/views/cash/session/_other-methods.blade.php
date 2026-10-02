{{-- Lo cobrado por QR / transferencia / tarjeta en la sesión: queda registrado
     pero NO está en el cajón, así que no entra en el esperado ni se cuenta al
     cerrar. Se muestra aparte. Parámetros: $session; $compact (opcional). --}}
@php
    $omList    = $session->otherMethodsSummary();
    $omCompany = $session->cashRegister?->company;
    $compact   = $compact ?? false;
@endphp
@if($omList)
<div class="{{ $compact ? 'small' : '' }} rounded-3 px-3 py-2 mt-2" style="background:rgba(13,110,253,.06);border:1px dashed rgba(13,110,253,.35);">
    <div class="fw-semibold small mb-1"><i class="bi bi-phone me-1"></i>Otros medios <span class="text-muted fw-normal">— no se cuentan en el cajón</span></div>
    <div class="d-flex flex-wrap gap-3">
        @foreach($omList as $om)
        <span><span class="text-muted">{{ $om['label'] }}</span> <strong>{{ money($om['amount'], $omCompany) }}</strong></span>
        @endforeach
    </div>
</div>
@endif
