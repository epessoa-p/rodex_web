<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta la sesión web de un usuario desactivado en su siguiente petición.
 *
 * Antes `active` solo se revisaba al iniciar sesión: una pestaña ya abierta
 * seguía funcionando hasta cerrar sesión. (En la API esto lo hace
 * Api\EnsureTokenFresh, que además revoca el token.)
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Tu usuario fue desactivado. Contacta al administrador.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'code' => 'user_inactive'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
