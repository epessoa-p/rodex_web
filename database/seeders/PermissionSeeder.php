<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Empresas
            ['name' => 'Ver Empresas', 'slug' => 'companies.view', 'module' => 'companies'],
            ['name' => 'Crear Empresas', 'slug' => 'companies.create', 'module' => 'companies'],
            ['name' => 'Editar Empresas', 'slug' => 'companies.edit', 'module' => 'companies'],
            ['name' => 'Eliminar Empresas', 'slug' => 'companies.delete', 'module' => 'companies'],

            // Usuarios
            ['name' => 'Ver Usuarios', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Crear Usuarios', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Editar Usuarios', 'slug' => 'users.edit', 'module' => 'users'],
            ['name' => 'Eliminar Usuarios', 'slug' => 'users.delete', 'module' => 'users'],

            // Roles
            ['name' => 'Ver Roles', 'slug' => 'roles.view', 'module' => 'roles'],
            ['name' => 'Crear Roles', 'slug' => 'roles.create', 'module' => 'roles'],
            ['name' => 'Editar Roles', 'slug' => 'roles.edit', 'module' => 'roles'],
            ['name' => 'Eliminar Roles', 'slug' => 'roles.delete', 'module' => 'roles'],

            // Reportes
            ['name' => 'Ver Reportes', 'slug' => 'reports.view', 'module' => 'reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
