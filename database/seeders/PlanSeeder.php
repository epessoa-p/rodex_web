<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'           => 'basico',
                'name'           => 'Básico',
                'description'    => 'Tienda y ventas: lo esencial para empezar.',
                'price'          => 250.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 3,
                'max_branches'   => 1,
                'max_products'   => 500,
                'features'       => ['inventory', 'sales', 'cash'],
                'active'         => true,
            ],
            [
                'slug'           => 'pro',
                'name'           => 'Pro',
                'description'    => 'Añade taller, compras y fidelización.',
                'price'          => 500.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 10,
                'max_branches'   => 3,
                'max_products'   => 5000,
                'features'       => ['inventory', 'sales', 'cash', 'purchases', 'workshop', 'loyalty', 'statistics'],
                'active'         => true,
            ],
            [
                'slug'           => 'full',
                'name'           => 'Full',
                'description'    => 'Todos los módulos, sin límites de uso.',
                'price'          => 850.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => null,   // ilimitado
                'max_branches'   => null,
                'max_products'   => null,
                'features'       => array_keys(Plan::MODULES),
                'active'         => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
