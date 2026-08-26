<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite personal SIN usuario del sistema: no todo el personal (p. ej. un
 * mecánico que no usa la app) necesita credenciales de acceso. El índice único
 * sobre user_id sigue válido porque MySQL admite múltiples NULL en un único.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
