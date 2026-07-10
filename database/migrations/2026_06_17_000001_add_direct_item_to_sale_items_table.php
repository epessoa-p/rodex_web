<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Columna de nombre libre para ítems de "Venta rápida" (producto no registrado).
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'description')) {
                $table->string('description')->nullable()->after('product_id');
            }
        });

        // 2. product_id pasa a ser nullable (tiene FK → soltar, modificar, recrear).
        // El FK se llama `sale_items_product_fk` (definido en SQL crudo), no el
        // nombre convencional de Laravel; se suelta por su nombre real.
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign('sale_items_product_fk');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
            if (Schema::hasColumn('sale_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
