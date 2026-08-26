<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrección: la migración previa agregó branch_id a la tabla `personal`
 * (singular, legada) por error. El modelo Personal usa la tabla `personals`
 * (plural). Aquí se agrega branch_id a `personals` con su backfill, y se limpia
 * la columna sobrante de `personal`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('personals') && ! Schema::hasColumn('personals', 'branch_id')) {
            Schema::table('personals', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('cargo_id')
                      ->constrained('branches')->nullOnDelete();
            });
        }

        // Backfill: sucursal desde la caja asignada al personal.
        DB::table('cash_registers')
            ->whereNotNull('assigned_personal_id')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->get(['assigned_personal_id', 'branch_id'])
            ->each(function ($cr) {
                DB::table('personals')
                    ->where('id', $cr->assigned_personal_id)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $cr->branch_id]);
            });

        // Limpia la columna agregada por error en la tabla legada `personal`.
        if (Schema::hasColumn('personal', 'branch_id')) {
            Schema::table('personal', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personals', 'branch_id')) {
            Schema::table('personals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
