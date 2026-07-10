<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `loyalty_point_movements` MODIFY COLUMN `type` ENUM('earn','redeem','adjust','expire') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `loyalty_point_movements` MODIFY COLUMN `type` ENUM('earn','redeem','adjust') NOT NULL");
    }
};
