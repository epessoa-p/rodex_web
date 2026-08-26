<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos del catálogo de Orígenes (product-origins.*) asignados a los roles base
 * como en marcas/unidades. La tabla y la siembra ya se crearon antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            ['name' => 'Ver Orígenes',      'slug' => 'product-origins.view',   'module' => 'product_origins', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crear Orígenes',    'slug' => 'product-origins.create', 'module' => 'product_origins', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Editar Orígenes',   'slug' => 'product-origins.edit',   'module' => 'product_origins', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Eliminar Orígenes', 'slug' => 'product-origins.delete', 'module' => 'product_origins', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $assign = function (array $roleSlugs, array $permSlugs) {
            $roleIds = DB::table('roles')->whereIn('slug', $roleSlugs)->pluck('id');
            $permIds = DB::table('permissions')->whereIn('slug', $permSlugs)->pluck('id');
            $rows = [];
            foreach ($roleIds as $rid) {
                foreach ($permIds as $pid) {
                    $rows[] = ['role_id' => $rid, 'permission_id' => $pid];
                }
            }
            if ($rows) {
                DB::table('role_permission')->insertOrIgnore($rows);
            }
        };
        $assign(['admin'],   ['product-origins.view', 'product-origins.create', 'product-origins.edit', 'product-origins.delete']);
        $assign(['manager'], ['product-origins.view', 'product-origins.create', 'product-origins.edit']);
        $assign(['cashier', 'employee'], ['product-origins.view']);
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'product-origins.view', 'product-origins.create', 'product-origins.edit', 'product-origins.delete',
        ])->delete();
    }
};
