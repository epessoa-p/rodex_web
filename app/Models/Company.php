<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'ruc', 'currency', 'address', 'phone', 'email', 'logo', 'description', 'active',
        'theme_primary', 'theme_accent', 'tracking_link_days',
    ];

    protected $casts = [
        'active' => 'boolean',
        'tracking_link_days' => 'integer',
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

    // ── Marca (white-label) ───────────────────────────────────────

    /**
     * URL del logo de la empresa para mostrar en pantalla.
     * Si la empresa no subió logo, se usa el de la plataforma.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . $this->logo);
        }

        return asset(config('brand.logo'));
    }

    /**
     * Ruta en disco del logo, para incrustarlo en PDF/Excel
     * (esos formatos no pueden resolver una URL).
     */
    public function getLogoFileAttribute(): ?string
    {
        $path = $this->logo
            ? public_path('storage/' . $this->logo)
            : public_path(config('brand.logo'));

        return is_file($path) ? $path : null;
    }

    /** ¿La empresa definió colores propios para el menú y la cabecera? */
    public function hasTheme(): bool
    {
        return !empty($this->theme_primary) && !empty($this->theme_accent);
    }

    // ── Suscripción SaaS ──────────────────────────────────────────

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /** ¿La empresa puede escribir? (trial o periodo pagado vigente) */
    public function subscriptionAllowsWrite(): bool
    {
        return (bool) $this->subscription?->allowsWrite();
    }

    /** ¿La empresa puede al menos entrar y consultar? (incluye gracia) */
    public function subscriptionAllowsRead(): bool
    {
        return (bool) $this->subscription?->allowsRead();
    }

    /**
     * ¿El plan contratado incluye este módulo? (ver Plan::MODULES)
     * Respeta el override de módulos de la empresa si lo hay.
     * Sin suscripción o sin plan => no.
     */
    public function planAllows(string $feature): bool
    {
        $sub = $this->subscription;

        return $sub !== null && in_array($feature, $sub->effectiveFeatures(), true);
    }

    /**
     * Lista de módulos efectivos de la empresa (override o plan).
     * null si no hay suscripción (no se puede determinar el plan).
     */
    public function effectiveFeatures(): ?array
    {
        return $this->subscription?->effectiveFeatures();
    }

    /**
     * Límite efectivo de un recurso ('users', 'branches', 'products'):
     * override de la empresa si existe, si no el del plan. null = ilimitado.
     */
    public function effectiveLimit(string $key): ?int
    {
        return $this->subscription?->effectiveLimitFor($key);
    }

    /** Uso actual de un recurso limitado por plan. */
    public function usageFor(string $key): int
    {
        return match ($key) {
            'users'    => $this->users()->count(),
            'branches' => $this->branches()->count(),
            'products' => $this->products()->count(),
            default    => 0,
        };
    }

    /**
     * ¿Queda cupo en el plan para añadir otro recurso de este tipo?
     * Sin límite definido (null) => ilimitado.
     */
    public function withinLimit(string $key): bool
    {
        $limit = $this->effectiveLimit($key);

        if ($limit === null) {
            return true;
        }

        return $this->usageFor($key) < $limit;
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
