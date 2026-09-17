<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vigencia del token del móvil, además de la caducidad absoluta de Sanctum
 * (`sanctum.expiration`, 30 días desde el login):
 *
 *  - Usuario desactivado → se revoca el token y 401 `user_inactive`.
 *  - Inactividad: el token lleva `expires_at` = ahora + SANCTUM_IDLE_DAYS (7) y
 *    aquí se **renueva en cada petición** (ventana deslizante). Sanctum rechaza
 *    por sí mismo los tokens con `expires_at` vencido (401), así que quien usa
 *    la app a diario nunca lo ve y un celular sin uso queda fuera en 7 días.
 *    (No sirve mirar `last_used_at`: Sanctum lo pone en "ahora" antes de
 *    llegar aquí.)
 *
 * La app cierra sesión sola ante cualquier 401 y muestra el motivo.
 */
class EnsureTokenFresh
{
    public static function idleDays(): int
    {
        return max(1, (int) env('SANCTUM_IDLE_DAYS', 7));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user  = $request->user();
        $token = $user?->currentAccessToken();

        if ($user && ! $user->active) {
            $token?->delete();

            return response()->json([
                'message' => 'Tu usuario fue desactivado. Contacta al administrador.',
                'code'    => 'user_inactive',
            ], 401);
        }

        // Renueva la ventana de inactividad. Para no escribir en cada petición,
        // solo cuando el vencimiento actual quedó más de 1 hora atrás del nuevo.
        if ($token && method_exists($token, 'forceFill')) {
            $next_ = now()->addDays(self::idleDays());
            if (! $token->expires_at || $token->expires_at->lt($next_->copy()->subHour())) {
                $token->forceFill(['expires_at' => $next_])->save();
            }
        }

        return $next($request);
    }
}
