<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la tabla legada `personal` (singular). El modelo Personal usa
 * `personals` (plural); la singular quedó huérfana: 0 filas, sin FKs que la
 * referencien, sin uso en código y sin script que la cree. Verificado antes de
 * borrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Salvaguarda: solo eliminar si sigue vacía (por si acaso).
        if (Schema::hasTable('personal') && DB::table('personal')->count() === 0) {
            Schema::dropIfExists('personal');
        }
    }

    public function down(): void
    {
        // Tabla huérfana sin uso: no se recrea intencionalmente.
    }
};
