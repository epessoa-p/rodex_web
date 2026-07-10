<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'ruc', 'address', 'phone', 'email', 'logo', 'description', 'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Usuarios que pertenecen a esta empresa
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')
                    ->withPivot('role_id', 'active');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    public function personals(): HasMany
    {
        return $this->hasMany(Personal::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    /**
     * Obtener el rol de un usuario dentro de esta empresa
     */
    public function getRoleForUser(User $user): ?Role
    {
        $pivot = $this->users()
                      ->where('user_id', $user->id)
                      ->first()?->pivot;

        return $pivot ? Role::find($pivot->role_id) : null;
    }

    /**
     * Obtener permisos efectivos (del rol + permisos adicionales)
     */
    public function getPermissionsForUser(User $user): array
    {
        if ($user->is_super_admin) {
            return Permission::all()->pluck('slug')->toArray();
        }

        $role = $this->getRoleForUser($user);
        $permissions = [];

        if ($role) {
            $permissions = $role->permissions()->pluck('slug')->toArray();
        }

        // Agregar permisos adicionales directos
        $directPermissions = \DB::table('user_permission')
            ->where('user_id', $user->id)
            ->where('company_id', $this->id)
            ->join('permissions', 'user_permission.permission_id', '=', 'permissions.id')
            ->pluck('permissions.slug')
            ->toArray();

        return array_unique(array_merge($permissions, $directPermissions));
    }
}
