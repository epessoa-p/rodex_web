<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moneda por empresa (SaaS multi-país): cada empresa-cliente muestra su propio
 * símbolo (Bs, $, S/, Gs…). Por defecto 'Bs' para no cambiar lo existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'currency')) {
                $table->string('currency', 8)->default('Bs')->after('ruc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
