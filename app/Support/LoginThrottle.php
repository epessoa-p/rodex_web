<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Freno a los intentos de contraseña (web y API comparten el contador).
 *
 * - 5 fallos del mismo usuario desde la misma IP → espera 5 minutos.
 * - 20 fallos desde una misma IP (probando varios usuarios) → espera 5 minutos.
 * Un inicio de sesión correcto limpia el contador de ese usuario.
 */
class LoginThrottle
{
    public const MAX_PER_LOGIN = 5;
    public const MAX_PER_IP = 20;
    public const DECAY_SECONDS = 300;

    public function __construct(private string $login, private string $ip)
    {
    }

    /** Segundos que faltan si está bloqueado; null si puede intentar. */
    public function blockedFor(): ?int
    {
        foreach ([[$this->loginKey(), self::MAX_PER_LOGIN], [$this->ipKey(), self::MAX_PER_IP]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return max(1, RateLimiter::availableIn($key));
            }
        }

        return null;
    }

    public function failed(): void
    {
        RateLimiter::hit($this->loginKey(), self::DECAY_SECONDS);
        RateLimiter::hit($this->ipKey(), self::DECAY_SECONDS);
    }

    public function succeeded(): void
    {
        RateLimiter::clear($this->loginKey());
    }

    public static function message(int $seconds): string
    {
        $minutes = (int) ceil($seconds / 60);

        return 'Demasiados intentos fallidos. Espera ' . $minutes . ' minuto' . ($minutes === 1 ? '' : 's') . ' e inténtalo de nuevo.';
    }

    private function loginKey(): string
    {
        return 'login:' . Str::lower(trim($this->login)) . '|' . $this->ip;
    }

    private function ipKey(): string
    {
        return 'login-ip:' . $this->ip;
    }
}
