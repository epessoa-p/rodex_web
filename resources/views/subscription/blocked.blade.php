@extends('layouts.auth')

@section('title', 'Suscripción · ' . ($company->name ?? config('app.name')))

@section('content')
<div class="auth-container" style="max-width: 520px;">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo">
                @if($company?->logo)
                    <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}">
                @else
                    <i class="bi bi-shield-lock"></i>
                @endif
            </div>

            @php
                $status = $subscription?->effectiveStatus();
            @endphp

            <h1>
                @if(!$subscription)
                    Empresa <span class="brand-accent">no habilitada</span>
                @elseif($status === 'suspended')
                    Suscripción <span class="brand-accent">suspendida</span>
                @elseif($status === 'cancelled')
                    Suscripción <span class="brand-accent">cancelada</span>
                @else
                    Suscripción <span class="brand-accent">vencida</span>
                @endif
            </h1>

            <p>{{ $company->name ?? '' }}</p>
        </div>

        @if(session('warning'))
            <div class="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ session('warning') }}
            </div>
        @endif

        <div style="color:#b9b9b9; font-size:.9rem; line-height:1.6;">
            @if(!$subscription)
                <p>Tu empresa todavía no tiene una suscripción activa en el sistema.
                   Contacta al proveedor para habilitarla.</p>
            @elseif($status === 'suspended')
                <p>El acceso a tu empresa fue suspendido. Contacta al proveedor para reactivarlo.</p>
            @elseif($status === 'cancelled')
                <p>La suscripción de tu empresa fue cancelada. Si quieres volver a usar el sistema,
                   contacta al proveedor.</p>
            @else
                <p>El periodo de tu suscripción terminó
                   @if($subscription->endsAt())
                       el <strong>{{ $subscription->endsAt()->format('d/m/Y') }}</strong>.
                   @else
                       .
                   @endif
                   Renueva para seguir registrando operaciones.</p>

                @if($subscription->inGrace())
                    <p style="color:#ffc107;">
                        <i class="bi bi-clock-history me-1"></i>
                        Estás en periodo de gracia hasta el
                        <strong>{{ $subscription->graceEndsAt()->format('d/m/Y') }}</strong>:
                        puedes <strong>consultar</strong> tus datos, pero no registrar cambios.
                    </p>
                @endif
            @endif

            @if($subscription?->plan)
                <div style="margin-top:18px; padding:12px 14px; border:1px solid rgba(255,255,255,.08); border-radius:10px; background:rgba(255,255,255,.03);">
                    <div style="font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#7a7a7a;">Plan contratado</div>
                    <div style="font-weight:600; color:#fff;">{{ $subscription->plan->name }}</div>
                </div>
            @endif
        </div>

        <div class="d-flex gap-2 mt-4">
            @if($subscription?->inGrace())
                <a href="{{ route('dashboard') }}" class="btn btn-login" style="flex:1;">
                    <i class="bi bi-eye me-1"></i> Consultar mis datos
                </a>
            @endif

            <form action="{{ route('logout') }}" method="POST" style="flex:1;">
                @csrf
                <button type="submit" class="btn btn-login"
                        style="background:rgba(255,255,255,.06); box-shadow:none; width:100%;">
                    <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                </button>
            </form>
        </div>

        <div class="auth-footer">
            Contacta a tu proveedor para renovar
        </div>
    </div>
</div>
@endsection
