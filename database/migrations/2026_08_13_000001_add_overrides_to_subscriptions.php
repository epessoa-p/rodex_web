<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes personalizados por empresa: permiten que UNA suscripción tenga más (o
 * menos) cupo o distintos módulos que su plan, sin crear un plan nuevo.
 *
 * NULL = "hereda del plan". Cualquier valor no nulo tiene precedencia sobre el
 * plan (ver Subscription::effectiveLimitFor / effectiveFeatures).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'max_users_override')) {
                $table->unsignedInteger('max_users_override')->nullable()->after('plan_id');
            }
            if (! Schema::hasColumn('subscriptions', 'max_branches_override')) {
                $table->unsignedInteger('max_branches_override')->nullable()->after('max_users_override');
            }
            if (! Schema::hasColumn('subscriptions', 'max_products_override')) {
                $table->unsignedInteger('max_products_override')->nullable()->after('max_branches_override');
            }
            if (! Schema::hasColumn('subscriptions', 'features_override')) {
                $table->json('features_override')->nullable()->after('max_products_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['max_users_override', 'max_branches_override', 'max_products_override', 'features_override'] as $col) {
                if (Schema::hasColumn('subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
