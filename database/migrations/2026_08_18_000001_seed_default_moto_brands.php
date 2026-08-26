<?php

use App\Models\Motos\MotoBrand;
use App\Support\MotoBrandDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de marcas de moto para empresas EXISTENTES que aún no tienen ninguna
 * (onboarding). No toca a las que ya armaron su catálogo, para no ensuciarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $hasBrands = MotoBrand::withoutGlobalScopes()->where('company_id', $companyId)->exists();
            if (! $hasBrands) {
                MotoBrandDefaults::seedFor((int) $companyId);
            }
        }
    }

    public function down(): void
    {
        // No se revierte: son datos de catálogo del cliente.
    }
};
