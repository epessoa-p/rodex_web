<?php

namespace App\Services\Admin;

use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Dashboard del super_admin: salud de la PLATAFORMA (no de una empresa).
 * Responde "¿quién usa el sistema, quién está por vencer y quién se enfrió?"
 * reutilizando el monitoreo de uso por empresa (CompanyUsageService).
 */
class PlatformOverviewService
{
    /** Días de anticipación para "vence pronto". */
    public const EXPIRING_DAYS = 15;

    public function __construct(private CompanyUsageService $usage) {}

    public function build(): array
    {
        $companies = Company::with('subscription.plan')->orderBy('name')->get();
        $last      = $this->usage->lastActivityFor($companies->pluck('id')->all());

        $rows = $companies->map(function (Company $c) use ($last) {
            $sub    = $c->subscription;
            $lastAt = $last[$c->id] ?? null;
            $ends   = $sub?->endsAt();

            return (object) [
                'company'       => $c,
                'plan'          => $sub?->plan?->name,
                'sub_status'    => $sub?->effectiveStatus() ?? 'none',
                'sub_label'     => $sub?->status_label ?? 'Sin suscripción',
                'ends_at'       => $ends,
                'days_to_end'   => $ends ? (int) ceil(now()->diffInDays($ends, false)) : null,
                'last_activity' => $lastAt,
                'activity'      => $this->usage->status($lastAt),
            ];
        });

        $activeSubs = $rows->filter(fn ($r) => in_array($r->sub_status, ['active', 'trial'], true));

        return [
            'kpis' => [
                'companies'     => $rows->count(),
                'active_subs'   => $activeSubs->count(),
                'trial'         => $rows->where('sub_status', 'trial')->count(),
                'past_due'      => $rows->where('sub_status', 'past_due')->count(),
                'blocked'       => $rows->whereIn('sub_status', ['suspended', 'cancelled'])->count(),
                'active_today'  => $rows->where('activity.key', 'today')->count(),
                'active_week'   => $rows->whereIn('activity.key', ['today', 'week'])->count(),
                'idle'          => $rows->whereIn('activity.key', ['idle', 'never'])->count(),
            ],
            // Vencen en ≤ 15 días (activas o en prueba), las más urgentes primero.
            'expiring' => $activeSubs
                ->filter(fn ($r) => $r->days_to_end !== null && $r->days_to_end <= self::EXPIRING_DAYS)
                ->sortBy('days_to_end')->values(),
            // Vencidas (en gracia o ya sin acceso): hay que cobrar o cortar.
            'past_due' => $rows->where('sub_status', 'past_due')
                ->sortBy(fn ($r) => $r->ends_at?->timestamp ?? 0)->values(),
            // Con suscripción vigente pero sin uso: riesgo de baja.
            'idle' => $activeSubs
                ->filter(fn ($r) => in_array($r->activity['key'], ['idle', 'never'], true))
                ->sortBy(fn ($r) => $r->last_activity?->timestamp ?? 0)->values(),
            // Las más activas hoy/semana, para ver quién de verdad opera.
            'most_active' => $rows
                ->filter(fn ($r) => $r->last_activity !== null)
                ->sortByDesc(fn ($r) => $r->last_activity->timestamp)
                ->take(8)->values(),
            'recent_companies' => $companies->sortByDesc('created_at')->take(5)->values(),
        ];
    }
}
