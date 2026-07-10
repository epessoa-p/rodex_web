{{-- Nota: los mensajes de session('success')/('error') ya los muestra el layout
     (layouts/app.blade.php) de forma global. Aquí solo mostramos los errores de
     validación, que el layout no renderiza. --}}
@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
