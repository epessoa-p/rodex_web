<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colores base por empresa (white-label). Se usan para pintar el menú de
 * navegación y la cabecera (topbar con los botones de usuario y cerrar sesión).
 *
 *  theme_primary : color de fondo del menú/cabecera (tono oscuro).
 *  theme_accent  : color de acento (item activo, hover de botones).
 *
 * NULL = la empresa usa la paleta por defecto del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('theme_primary', 9)->nullable()->after('logo');
            $table->string('theme_accent', 9)->nullable()->after('theme_primary');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['theme_primary', 'theme_accent']);
        });
    }
};
