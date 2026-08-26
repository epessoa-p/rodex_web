<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de UNIDADES DE MEDIDA por empresa (Unidad, Pieza, Juego, Litro…),
 * al estilo de categorías/marcas. El producto sigue guardando el nombre de la
 * unidad como texto (products.unit); esta tabla provee la lista gestionable.
 *
 * Incluye: tabla, permisos (product-units.*) asignados a los roles base como en
 * marcas, y siembra por empresa (unidades ya usadas + un set por defecto).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Tabla
        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name', 50);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'name']);
                $table->index('company_id');
            });
        }

        // 2) Permisos
        DB::table('permissions')->insertOrIgnore([
            ['name' => 'Ver Unidades',      'slug' => 'product-units.view',   'module' => 'product_units', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Crear Unidades',    'slug' => 'product-units.create', 'module' => 'product_units', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Editar Unidades',   'slug' => 'product-units.edit',   'module' => 'product_units', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Eliminar Unidades', 'slug' => 'product-units.delete', 'module' => 'product_units', 'description' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3) Asignación a roles base (igual que marcas)
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
        $assign(['admin'],   ['product-units.view', 'product-units.create', 'product-units.edit', 'product-units.delete']);
        $assign(['manager'], ['product-units.view', 'product-units.create', 'product-units.edit']);
        $assign(['cashier', 'employee'], ['product-units.view']);

        // 4) Siembra por empresa: primero las unidades ya usadas (respeta su texto
        //    exacto), luego un set por defecto que no colisione.
        $defaults = ['Unidad', 'Pieza', 'Juego', 'Caja', 'Par', 'Litro', 'Kilogramo', 'Metro', 'Docena'];
        $now = now();

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $used = DB::table('products')
                ->where('company_id', $companyId)
                ->whereNotNull('unit')->where('unit', '<>', '')
                ->distinct()->pluck('unit')
                ->map(fn ($u) => trim($u))->filter()->unique(fn ($u) => mb_strtolower($u));

            $rows = [];
            foreach ($used as $name) {
                $rows[] = ['company_id' => $companyId, 'name' => $name, 'active' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ($defaults as $name) {
                $rows[] = ['company_id' => $companyId, 'name' => $name, 'active' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
            // insertOrIgnore respeta el unique(company_id,name) case-insensitive de MySQL.
            DB::table('product_units')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'product-units.view', 'product-units.create', 'product-units.edit', 'product-units.delete',
        ])->delete();

        Schema::dropIfExists('product_units');
    }
};
