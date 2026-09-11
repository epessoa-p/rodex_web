<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registra cuándo un usuario entró por última vez a una empresa
 * (company_user.last_seen_at). Lo consume el monitoreo de uso del operador.
 *
 * Se llama en cada request con tenant (web y API), así que se limita a UNA
 * escritura por usuario+empresa por hora usando el caché como candado.
 */
class CompanyPresence
{
    private const THROTTLE_MINUTES = 60;

    public static function touch(?int $userId, ?int $companyId): void
    {
        if (!$userId || !$companyId) {
            return;
        }

        // Cache::add solo devuelve true si la clave NO existía: primer toque de la hora.
        if (!Cache::add("company_presence:{$userId}:{$companyId}", 1, now()->addMinutes(self::THROTTLE_MINUTES))) {
            return;
        }

        try {
            DB::table('company_user')
                ->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->update(['last_seen_at' => now()]);
        } catch (\Throwable $e) {
            // Nunca debe tumbar una petición (p. ej. si aún no se corrió el script SQL).
            Log::warning('CompanyPresence: no se pudo registrar last_seen_at', ['msg' => $e->getMessage()]);
        }
    }
}
