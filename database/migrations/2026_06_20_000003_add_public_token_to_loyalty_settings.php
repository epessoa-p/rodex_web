<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_settings', 'public_token')) {
                $table->string('public_token', 40)->nullable()->unique()->after('points_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_settings', 'public_token')) {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            }
        });
    }
};
