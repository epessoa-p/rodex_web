<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Suscripción de una empresa a un plan. Modelo GLOBAL (lo gestiona el operador),
 * por eso NO usa el trait BelongsToCompany: si lo usara, el super_admin no podría
 * listarlas y la propia empresa se filtraría a sí misma antes de saber si tiene acceso.
 *
 * El estado efectivo se DERIVA de las fechas, no solo de la columna `status`:
 * así una suscripción vencida corta el acceso sola, sin que el operador tenga
 * que cambiar el estado a mano cada día.
 */
class Subscription extends Model
{
    use HasFactory;

    public const STATUSES = [
        'trial'     => 'Prueba',
        'active'    => 'Activa',
        'past_due'  => 'Vencida',
        'suspended' => 'Suspendida',
        'cancelled' => 'Cancelada',
    ];

    protected $fillable = [
        'company_id', 'plan_id', 'status', 'trial_ends_at',
        'current_period_end', 'grace_days', 'notes', 'created_by',
        'max_users_override', 'max_branches_override', 'max_products_override', 'features_override',
    ];

    protected $casts = [
        'trial_ends_at'         => 'datetime',
        'current_period_end'    => 'datetime',
        'grace_days'            => 'integer',
        'max_users_override'    => 'integer',
        'max_branches_override' => 'integer',
        'max_products_override' => 'integer',
        'features_override'     => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Ajustes personalizados por empresa (override sobre el plan) ─────

    /**
     * Límite efectivo para una clave ('users', 'branches', 'products'):
     * el override de la empresa si está definido; si no, el del plan.
     * null = ilimitado.
     */
    public function effectiveLimitFor(string $key): ?int
    {
        $override = $this->{'max_' . $key . '_override'};

        return $override !== null ? $override : $this->plan?->limitFor($key);
    }

    /**
     * Módulos efectivos: el override de la empresa si está definido (reemplaza
     * al plan); si no, los del plan.
     */
    public function effectiveFeatures(): array
    {
        if ($this->features_override !== null) {
            return $this->features_override;
        }

        return $this->plan?->features ?? [];
    }

    // ── Estado efectivo ───────────────────────────────────────────

    /** Cortada explícitamente por el operador: nunca da acceso. */
    public function isBlocked(): bool
    {
        return in_array($this->status, ['suspended', 'cancelled'], true);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /** Al día: dentro del periodo pagado. */
    public function isCurrent(): bool
    {
        return $this->status === 'active'
            && $this->current_period_end
            && $this->current_period_end->isFuture();
    }

    /** Fecha límite de acceso (fin de periodo/trial + días de gracia). */
    public function graceEndsAt(): ?Carbon
    {
        $end = $this->status === 'trial' ? $this->trial_ends_at : $this->current_period_end;

        return $end?->copy()->addDays($this->grace_days ?? 0);
    }

    /** Venció, pero sigue dentro del periodo de gracia => acceso SOLO LECTURA. */
    public function inGrace(): bool
    {
        if ($this->isBlocked() || $this->onTrial() || $this->isCurrent()) {
            return false;
        }

        $graceEnd = $this->graceEndsAt();

        return $graceEnd !== null && $graceEnd->isFuture();
    }

    /** ¿Puede la empresa usar el sistema con normalidad (escritura incluida)? */
    public function allowsWrite(): bool
    {
        return !$this->isBlocked() && ($this->onTrial() || $this->isCurrent());
    }

    /** ¿Puede al menos entrar y consultar? (incluye periodo de gracia) */
    public function allowsRead(): bool
    {
        return $this->allowsWrite() || $this->inGrace();
    }

    /** Etiqueta legible del estado real (no solo la columna). */
    public function effectiveStatus(): string
    {
        if ($this->status === 'suspended') return 'suspended';
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->onTrial())              return 'trial';
        if ($this->isCurrent())            return 'active';

        return 'past_due';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->effectiveStatus()] ?? $this->status;
    }

    /** Fin del acceso normal (trial o periodo pagado), para mostrar en el panel. */
    public function endsAt(): ?Carbon
    {
        return $this->status === 'trial' ? $this->trial_ends_at : $this->current_period_end;
    }

    // ── Acciones del operador (activación manual) ─────────────────

    /** Activa/renueva: extiende el periodo N meses desde hoy (o desde el fin vigente). */
    public function renew(?int $months = null): void
    {
        $months = $months ?? $this->plan->periodMonths();

        $from = $this->current_period_end && $this->current_period_end->isFuture()
            ? $this->current_period_end->copy()   // renovación anticipada: encadena
            : now();

        $this->status = 'active';
        $this->current_period_end = $from->addMonths($months);
        $this->save();
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
