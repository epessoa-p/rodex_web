<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sucursal "base" del personal (OPCIONAL). Antes la sucursal de un empleado solo
 * se conocía indirectamente por la caja que operaba; el personal sin caja
 * (mecánicos, almacén, encargados) no tenía ninguna. Este branch_id la vuelve un
 * atributo de primera clase para organización/reportes, sin cambiar de dónde sale
 * la sucursal de una venta (que sigue viniendo de la caja).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personal', 'branch_id')) {
            Schema::table('personal', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('cargo_id')
                      ->constrained('branches')->nullOnDelete();
            });
        }

        // Backfill: si el personal tiene una caja asignada, hereda su sucursal.
        DB::table('cash_registers')
            ->whereNotNull('assigned_personal_id')
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->get(['assigned_personal_id', 'branch_id'])
            ->each(function ($cr) {
                DB::table('personal')
                    ->where('id', $cr->assigned_personal_id)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $cr->branch_id]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('personal', 'branch_id')) {
            Schema::table('personal', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
