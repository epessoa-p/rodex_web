<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de la plataforma SaaS. Modelo GLOBAL: no lleva company_id ni el trait
 * BelongsToCompany (los planes son del operador, no de un tenant).
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * Módulos que se pueden habilitar/deshabilitar por plan.
     * La clave es el identificador usado en features[] y en planAllows().
     */
    public const MODULES = [
        'inventory'  => 'Inventario',
        'sales'      => 'Ventas',
        'purchases'  => 'Compras',
        'workshop'   => 'Taller / Mecánica',
        'rentals'    => 'Alquiler',
        'motos'      => 'Venta de motos',
        'loyalty'    => 'Fidelización',
        'cash'       => 'Caja',
        'statistics' => 'Estadísticas',
    ];

    public const BILLING_PERIODS = [
        'monthly' => 'Mensual',
        'yearly'  => 'Anual',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'billing_period', 'trial_days',
        'max_users', 'max_branches', 'max_products', 'features', 'active',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'trial_days'   => 'integer',
        'max_users'    => 'integer',
        'max_branches' => 'integer',
        'max_products' => 'integer',
        'features'     => 'array',
        'active'       => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** ¿El plan incluye este módulo? */
    public function allows(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    /**
     * Límite del plan para una clave dada ('users', 'branches', 'products').
     * null = ilimitado.
     */
    public function limitFor(string $key): ?int
    {
        return $this->{'max_' . $key};
    }

    public function getBillingPeriodLabelAttribute(): string
    {
        return self::BILLING_PERIODS[$this->billing_period] ?? $this->billing_period;
    }

    /** Meses que dura un periodo de facturación de este plan. */
    public function periodMonths(): int
    {
        return $this->billing_period === 'yearly' ? 12 : 1;
    }
}
