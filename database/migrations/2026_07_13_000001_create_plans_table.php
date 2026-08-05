<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes de la plataforma SaaS (lo que se le vende a cada empresa-cliente).
 *
 * OJO: no confundir con `payment_plans`, que son los planes de financiación que
 * una empresa ofrece a SUS clientes finales en las ventas a crédito.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->unsignedSmallInteger('trial_days')->default(0);

            // Límites de uso. NULL = ilimitado.
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_branches')->nullable();
            $table->unsignedInteger('max_products')->nullable();

            // Módulos habilitados: ["inventory","sales","workshop","rentals",...]
            $table->json('features')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
