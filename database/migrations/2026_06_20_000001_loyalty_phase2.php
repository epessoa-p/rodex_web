<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vencimiento: meses de caducidad (null/0 = no vence)
        Schema::table('loyalty_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_settings', 'expiration_months')) {
                $table->unsignedSmallInteger('expiration_months')->nullable()->after('min_purchase');
            }
        });

        // Lotes FIFO + fecha de expiración en el ledger
        Schema::table('loyalty_point_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_point_movements', 'points_remaining')) {
                $table->integer('points_remaining')->nullable()->after('points');
            }
            if (!Schema::hasColumn('loyalty_point_movements', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('points_remaining');
            }
        });

        // Backfill: lotes 'earn' existentes quedan con su saldo completo disponible
        DB::table('loyalty_point_movements')
            ->where('type', 'earn')
            ->whereNull('points_remaining')
            ->update(['points_remaining' => DB::raw('points')]);

        // Campañas (multiplicadores temporales de puntos)
        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('multiplier', 5, 2)->default(2);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaigns');
        Schema::table('loyalty_point_movements', function (Blueprint $table) {
            foreach (['points_remaining', 'expires_at'] as $col) {
                if (Schema::hasColumn('loyalty_point_movements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('loyalty_settings', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_settings', 'expiration_months')) {
                $table->dropColumn('expiration_months');
            }
        });
    }
};
