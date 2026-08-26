<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina products.compatible_models: los modelos ahora viven estructurados en
 * moto_models (enlazados por moto_model_product). El backfill previo ya migró el
 * texto, por eso aquí solo se descarta la columna vacía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'compatible_models')) {
                $table->dropColumn('compatible_models');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'compatible_models')) {
                $table->string('compatible_models', 500)->nullable()->after('description');
            }
        });
    }
};
