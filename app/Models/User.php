<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'is_super_admin',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Empresas a las que pertenece este usuario
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
                    ->withPivot('role_id', 'active');
    }

    /**
     * Roles que tiene el usuario en sus empresas
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'company_user', 'user_id', 'role_id')
                    ->distinct();
    }

    /**
     * Permisos asignados directamente al usuario en una empresa
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id')
                    ->withPivot('company_id');
    }

    public function personal(): HasOne
    {
        return $this->hasOne(Personal::class);
    }

    /**
     * Tablas operativas que referencian a un usuario (etiqueta => [[tabla, columna], ...]).
     * Se usa para impedir eliminar un usuario/personal con historial asociado.
     */
    protected static array $operationalReferenceMap = [
        'Ventas'                    => [['sales', 'created_by']],
        'Pagos de ventas'           => [['sale_payments', 'user_id']],
        'Devoluciones de venta'     => [['sale_returns', 'created_by']],
        'Cotizaciones'              => [['quotes', 'created_by']],
        'Sesiones de caja'          => [['cash_register_sessions', 'opened_by'], ['cash_register_sessions', 'closed_by']],
        'Cajas registradoras'       => [['cash_registers', 'created_by']],
        'Movimientos de caja'       => [['cash_movements', 'user_id']],
        'Caja chica'                => [['petty_cashes', 'created_by'], ['petty_cash_movements', 'created_by']],
        'Movimientos de inventario' => [['inventory_movements', 'user_id']],
        'Compras'                   => [['purchases', 'created_by'], ['purchase_orders', 'created_by']],
        'Recepciones de mercadería' => [['goods_receipts', 'received_by']],
        'Pagos a proveedores'       => [['supplier_payments', 'user_id']],
        'Movimientos de tesorería'  => [['treasury_movements', 'user_id']],
        'Órdenes de trabajo'        => [['work_orders', 'created_by'], ['work_orders', 'mechanic_id']],
        'Pagos de taller'           => [['work_order_payments', 'user_id']],
        'Contratos de alquiler'     => [['rental_contracts', 'created_by']],
        'Pagos de alquiler'         => [['rental_payments', 'user_id']],
        'Créditos'                  => [['credit_applications', 'created_by']],
        'Comisiones'                => [['commissions', 'created_by']],
        'Clientes registrados'      => [['clients', 'created_by']],
    ];

    /**
     * Devuelve los registros operativos asociados a este usuario, agrupados por
     * etiqueta legible: ['Ventas' => 3, 'Pagos de ventas' => 5, ...].
     * Solo incluye las categorías con al menos un registro.
     */
    public function operationalReferences(): array
    {
        $found = [];

        foreach (static::$operationalReferenceMap as $label => $sources) {
            $count = 0;
            foreach ($sources as [$table, $column]) {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                    continue;
                }
                $count += DB::table($table)->where($column, $this->id)->count();
            }
            if ($count > 0) {
                $found[$label] = $count;
            }
        }

        return $found;
    }

    /** ¿El usuario tiene historial operativo que impida eliminarlo? */
    public function hasOperationalReferences(): bool
    {
        return !empty($this->operationalReferences());
    }

    /**
     * Verificar si el usuario tiene un rol específico en una empresa
     */
    public function hasRoleInCompany(string $roleSlug, ?Company $company): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (!$company) {
            return false;
        }

        $role = $company->users()
            ->where('user_id', $this->id)
            ->first()?->pivot->role_id;

        if (!$role) {
            return false;
        }

        return Role::find($role)->slug === $roleSlug;
    }

    /**
     * Verificar si el usuario tiene un permiso en una empresa
     */
    public function hasPermissionInCompany(string $permissionSlug, ?Company $company): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (!$company) {
            return false;
        }

        $permissions = $company->getPermissionsForUser($this);
        return in_array($permissionSlug, $permissions);
    }

    /**
     * Obtener todas las empresas activas del usuario
     */
    public function activeCompanies()
    {
        return $this->companies()
                    ->where('company_user.active', true)
                    ->where('companies.active', true);
    }

    /**
     * Obtener empresa actual de la sesión o la primera que pertenezca
     */
    public function getCurrentCompany(): ?Company
    {
        if (auth()->check() && session()->has('current_company_id')) {
            return Company::find(session('current_company_id'));
        }

        return $this->activeCompanies()->first();
    }
}

