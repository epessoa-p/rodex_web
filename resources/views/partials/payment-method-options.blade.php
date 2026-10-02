{{-- <option> de las formas de pago que acepta la empresa activa (efectivo siempre).
     Uso: <select name="method">@include('partials.payment-method-options', ['selected' => old('method')])</select> --}}
@php
    $pmCompany  = auth()->user()?->getCurrentCompany();
    $pmList     = $pmCompany?->paymentMethods() ?? ['efectivo'];
    $pmSelected = $selected ?? 'efectivo';
    $pmSelected = in_array($pmSelected, $pmList, true) ? $pmSelected : 'efectivo';
@endphp
@foreach($pmList as $m)
<option value="{{ $m }}" {{ $m === $pmSelected ? 'selected' : '' }}>{{ \App\Models\CashMovement::METHOD_SHORT[$m] ?? $m }}</option>
@endforeach
