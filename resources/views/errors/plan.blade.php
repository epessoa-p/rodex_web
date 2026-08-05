{{-- Módulo no incluido en el plan contratado (distinto de "sin permisos"). --}}
@extends(auth()->check() ? 'layouts.app' : 'layouts.base')

@section('title', 'Módulo no incluido')

@section(auth()->check() ? 'page' : 'content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh; padding: 1rem;">
    <div class="text-center" style="max-width: 460px;">
        <h1 class="display-4 text-warning"><i class="bi bi-lock"></i></h1>
        <h2 class="mb-3">Módulo no incluido</h2>

        <p class="text-muted mb-2">
            El módulo
            <strong>{{ \App\Models\Plan::MODULES[$module] ?? $module }}</strong>
            no está incluido en tu plan
            @if($company?->subscription?->plan)
                <strong>{{ $company->subscription->plan->name }}</strong>
            @endif
            .
        </p>

        <p class="text-muted mb-4">
            Contacta a tu proveedor para ampliar el plan y habilitarlo.
        </p>

        <a href="{{ route('dashboard') }}" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
        </a>
    </div>
</div>
@endsection
