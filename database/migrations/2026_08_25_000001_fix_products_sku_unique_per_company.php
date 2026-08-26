<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El SKU interno (PRD-00001) se genera correlativo POR EMPRESA, pero la tabla
 * tenía un índice único GLOBAL sobre `sku`, lo que hacía colisionar el primer
 * producto de una empresa nueva con el de otra (misma numeración). Se cambia a
 * un índice único compuesto (company_id, sku), acorde al multi-tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['company_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'sku']);
            $table->unique('sku');
        });
    }
};
