<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versión API de EnsureSubscriptionActive: corta el acceso según el estado de
 * la suscripción, respondiendo JSON en vez de redirigir. Reutiliza la misma
 * lógica del modelo Company/Subscription.
 *
 *   - Sin suscripción o fuera de gracia => 402 (pago requerido).
 *   - En periodo de gracia              => solo lectura: bloquea escrituras (403).
 */
class EnsureSubscriptionActiveApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $company = $request->attributes->get('tenant_company');

        if (! $company) {
            return $next($request);
        }

        if (! $company->subscriptionAllowsRead()) {
            return response()->json([
                'message' => 'La suscripción de la empresa no está activa. Contacta al proveedor.',
                'code'    => 'subscription_inactive',
            ], 402);
        }

        // En gracia: se puede consultar (GET) pero no registrar cambios.
        $isWrite = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($isWrite && $company->subscription?->inGrace()) {
            return response()->json([
                'message' => 'Tu suscripción venció. Puedes consultar, pero no registrar cambios hasta renovar.',
                'code'    => 'subscription_grace_readonly',
            ], 403);
        }

        return $next($request);
    }
}
