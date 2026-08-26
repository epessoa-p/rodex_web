<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes por defecto orientados al ecosistema MOTO. Cada uno activa los módulos
 * del arquetipo de negocio (repuestos, taller, concesionaria, alquiler, full).
 * El operador puede editarlos o crear otros desde el panel de Planes.
 *
 * Módulos disponibles (Plan::MODULES): inventory, sales, purchases, workshop,
 * rentals, motos, loyalty, cash, statistics.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'           => 'repuestos',
                'name'           => 'Repuestos',
                'description'    => 'Tienda de repuestos de moto: inventario, ventas (POS) y caja.',
                'price'          => 150.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 3,
                'max_branches'   => 1,
                'max_products'   => 1000,
                'features'       => ['inventory', 'sales', 'cash', 'statistics'],
                'active'         => true,
            ],
            [
                'slug'           => 'taller',
                'name'           => 'Taller',
                'description'    => 'Taller de motos: suma órdenes de trabajo (mecánica) y compras.',
                'price'          => 300.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 5,
                'max_branches'   => 2,
                'max_products'   => 2000,
                'features'       => ['inventory', 'sales', 'cash', 'purchases', 'workshop', 'statistics'],
                'active'         => true,
            ],
            [
                'slug'           => 'concesionaria',
                'name'           => 'Concesionaria',
                'description'    => 'Venta de motos + repuestos y taller, con fidelización.',
                'price'          => 550.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 10,
                'max_branches'   => 3,
                'max_products'   => 5000,
                'features'       => ['inventory', 'sales', 'cash', 'purchases', 'workshop', 'motos', 'loyalty', 'statistics'],
                'active'         => true,
            ],
            [
                'slug'           => 'alquiler',
                'name'           => 'Alquiler',
                'description'    => 'Alquiler de motos: inventario, ventas, caja y módulo de alquiler.',
                'price'          => 350.00,
                'billing_period' => 'monthly',
                'trial_days'     => 15,
                'max_users'      => 5,
                'max_branches'   => 2,
                'max_products'   => 1000,
                'features'       => ['inventory', 'sales', 'cash', 'rentals', 'statistics'],
                'active'         => true,
            ],
            [
                'slug'           => 'full',
                'name'           => 'Full',
                'description'    => 'Todos los módulos del ecosistema moto, sin límites de uso.',
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
