<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambia sales.sale_date de DATE a DATETIME para conservar la hora de la venta.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dateTime('sale_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->date('sale_date')->change();
        });
    }
};
