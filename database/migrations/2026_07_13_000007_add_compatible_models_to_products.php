<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelos/vehículos compatibles como TEXTO LIBRE (genérico para repuestos de
 * moto y auto). Reemplaza el acoplamiento del importador con la estructura
 * específica de MotoModel; el módulo Motos sigue usando moto_models aparte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'compatible_models')) {
                $table->string('compatible_models', 500)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'compatible_models')) {
                $table->dropColumn('compatible_models');
            }
        });
    }
};
