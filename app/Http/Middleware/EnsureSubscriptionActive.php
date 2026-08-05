<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta el acceso de una empresa según el estado de su suscripción SaaS.
 *
 *   - Con acceso de escritura (trial vigente o periodo pagado) => pasa.
 *   - Vencida pero en periodo de gracia => SOLO LECTURA: se permiten los GET
 *     y se bloquean POST/PUT/PATCH/DELETE con un aviso para que renueve.
 *   - Vencida fuera de gracia, suspendida o cancelada => pantalla de bloqueo.
 *
 * El super_admin (operador de la plataforma) nunca se bloquea, para poder
 * entrar a arreglar/renovar. Tampoco se bloquean las rutas de sesión (logout,
 * selección de empresa) ni la propia pantalla de bloqueo: si no, el usuario
 * quedaría atrapado sin poder ni salir.
 */
class EnsureSubscriptionActive
{
    /** Rutas siempre accesibles, aunque la suscripción esté vencida. */
    private array $allowedRoutes = [
        'logout',
        'select-company',
        'set-company',
        'subscription.blocked',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if ($user->is_super_admin) {
            return $next($request);
        }

        if ($request->routeIs($this->allowedRoutes)) {
            return $next($request);
        }

        $company = $user->getCurrentCompany();

        if (!$company) {
            return $next($request);  // CheckPermission ya gestiona el caso sin empresa
        }

        $subscription = $company->subscription;

        // Sin suscripción registrada => la empresa aún no está habilitada.
        if (!$subscription || !$subscription->allowsRead()) {
            return redirect()->route('subscription.blocked');
        }

        // Periodo de gracia: se puede consultar, pero no modificar.
        if ($subscription->inGrace() && !$this->isReadOnlyMethod($request)) {
            return redirect()
                ->route('subscription.blocked')
                ->with('warning', 'Tu suscripción venció. Puedes consultar tus datos, pero no registrar cambios hasta renovar.');
        }

        return $next($request);
    }

    private function isReadOnlyMethod(Request $request): bool
    {
        return in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
    }
}
