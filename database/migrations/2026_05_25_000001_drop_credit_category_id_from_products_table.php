<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'credit_category_id')) {
                $table->dropForeign(['credit_category_id']);
                $table->dropColumn('credit_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_category_id')->nullable()->after('company_id');
        });
    }
};
