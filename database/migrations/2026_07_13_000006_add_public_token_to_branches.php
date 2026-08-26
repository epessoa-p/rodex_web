<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token público por sucursal para el catálogo de solo consulta (enlace/QR sin
 * login). Mismo patrón que loyalty_settings.public_token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('public_token', 40)->nullable()->unique()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
