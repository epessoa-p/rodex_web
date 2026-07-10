{{-- Si hay sesión, usa el layout completo (con menú); si no (invitado/sesión vencida),
     usa el layout base para evitar el fatal por auth()->user() nulo. --}}
@extends(auth()->check() ? 'layouts.app' : 'layouts.base')

@section('title', 'Acceso Denegado')

@section(auth()->check() ? 'page' : 'content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh; padding: 1rem;">
    <div class="text-center">
        <h1 class="display-4 text-danger"><i class="bi bi-shield-exclamation"></i></h1>
        <h2 class="mb-3">Acceso Denegado</h2>
        <p class="text-muted mb-4">No tienes permisos para acceder a esta sección.</p>
        @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
        </a>
        @else
        <a href="{{ route('login') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
        </a>
        @endauth
    </div>
</div>
@endsection
