<?php

use App\Support\ProductOriginDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Origen/procedencia del producto (país). Tabla catálogo por empresa + FK
 * opcional en products.origin_id. Siembra un set de países comunes por empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_origins')) {
            Schema::create('product_origins', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'name']);
                $table->index('company_id');
            });
        }

        if (! Schema::hasColumn('products', 'origin_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('origin_id')->nullable()->after('brand_id')
                      ->constrained('product_origins')->nullOnDelete();
            });
        }

        // Siembra por empresa existente.
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            ProductOriginDefaults::seedFor((int) $companyId);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'origin_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('origin_id');
            });
        }
        Schema::dropIfExists('product_origins');
    }
};
