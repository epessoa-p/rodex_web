<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuración + reglas de acumulación (1 fila por empresa)
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->decimal('earn_amount', 15, 2)->default(20);   // cada N de moneda…
            $table->unsignedInteger('earn_points')->default(100); // …otorga M puntos
            $table->enum('rounding', ['down', 'nearest', 'up'])->default('down');
            $table->decimal('min_purchase', 15, 2)->default(0);
            $table->string('points_label')->default('puntos');
            $table->timestamps();
            $table->unique('company_id');
        });

        // Catálogo de recompensas
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('points_cost');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('stock')->nullable();   // null = ilimitado / sin control propio
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('company_id');
        });

        // Canjes
        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('loyalty_rewards');
            $table->unsignedInteger('points_spent');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('status')->default('completed');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'client_id']);
        });

        // Ledger de movimientos de puntos (fuente de verdad del saldo)
        Schema::create('loyalty_point_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->enum('type', ['earn', 'redeem', 'adjust']);
            $table->integer('points'); // con signo: + gana / − canjea
            $table->nullableMorphs('source');
            $table->string('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'client_id']);
        });

        // Caché del saldo en clientes
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'points_balance')) {
                $table->integer('points_balance')->default(0)->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'points_balance')) {
                $table->dropColumn('points_balance');
            }
        });
        Schema::dropIfExists('loyalty_point_movements');
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_settings');
    }
};
