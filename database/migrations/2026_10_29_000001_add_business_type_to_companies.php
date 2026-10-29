<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rubro de la empresa (moto, auto, lubricentro, none). Decide qué catálogos se
 * precargan en el alta. Las existentes quedan como 'moto'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'business_type')) {
                $table->string('business_type', 20)->default('moto')->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'business_type')) {
                $table->dropColumn('business_type');
            }
        });
    }
};
