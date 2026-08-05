<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suscripción de cada empresa a un plan de la plataforma.
 *
 * Activación MANUAL: el operador (super_admin) asigna plan, activa, renueva y
 * suspende desde el panel. El esquema ya contempla los campos que necesitaría
 * una pasarela de pago automática más adelante (periodos, trial, gracia).
 *
 * Una fila por empresa (la suscripción vigente); renovar extiende current_period_end.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();

            // trial: en periodo de prueba | active: al día | past_due: vencida (en gracia)
            // suspended: cortada por el operador | cancelled: baja definitiva
            $table->enum('status', ['trial', 'active', 'past_due', 'suspended', 'cancelled'])
                  ->default('trial');

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_end')->nullable();

            // Días de tolerancia tras vencer antes de cortar el acceso (solo-lectura).
            $table->unsignedSmallInteger('grace_days')->default(3);

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
