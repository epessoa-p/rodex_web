<?php

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

/**
 * Da de alta las empresas que YA existían antes de introducir el módulo de
 * suscripción. Sin esto, al activarse EnsureSubscriptionActive quedarían sin
 * suscripción y sus usuarios verían la pantalla de bloqueo al entrar.
 *
 * Se les asigna el plan Full con un vencimiento muy lejano: equivale a una
 * licencia comprada (perpetua), que es como está el sistema hoy para su dueño.
 */
return new class extends Migration
{
    public function up(): void
    {
        $plan = Plan::where('slug', 'full')->first();

        if (!$plan) {
            // Sin planes sembrados no hay nada que asignar (instalación nueva).
            return;
        }

        Company::whereDoesntHave('subscription')->each(function (Company $company) use ($plan) {
            $company->subscription()->create([
                'plan_id'            => $plan->id,
                'status'             => 'active',
                'current_period_end' => now()->addYears(10),
                'grace_days'         => 3,
                'notes'              => 'Licencia previa a la introducción del módulo de suscripción (acceso perpetuo).',
            ]);
        });
    }

    public function down(): void
    {
        // Las suscripciones se borran con la tabla en la migración correspondiente.
    }
};
