<?php

namespace App\Services\Admin;

use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monitoreo de uso de cada empresa para el operador (super_admin): ¿están
 * usando el sistema? Se mide por los registros operativos que crean (ventas,
 * OTs, compras, alquileres, citas, clientes, sesiones de caja) y por la última
 * vez que alguien entró a la empresa (company_user.last_seen_at).
 *
 * Consulta con DB::table para saltar los global scopes de tenant: el operador
 * no tiene empresa activa y se filtra por company_id explícito.
 */
class CompanyUsageService
{
    /** Módulos monitoreados: tabla con company_id + created_at (+ deleted_at). */
    private const SOURCES = [
        'sales'        => ['label' => 'Ventas',            'table' => 'sales',            'icon' => 'bi-cart-check'],
        'work_orders'  => ['label' => 'Órdenes de taller', 'table' => 'work_orders',      'icon' => 'bi-wrench'],
        'purchases'    => ['label' => 'Compras',           'table' => 'purchases',        'icon' => 'bi-bag'],
        'rentals'      => ['label' => 'Alquileres',        'table' => 'rental_contracts', 'icon' => 'bi-bicycle'],
        'appointments' => ['label' => 'Citas',             'table' => 'appointments',     'icon' => 'bi-calendar-check'],
        'clients'      => ['label' => 'Clientes',          'table' => 'clients',          'icon' => 'bi-people'],
    ];

    public const RECENT_DAYS = 30;

    /**
     * Última actividad por empresa, para el listado: [company_id => ?Carbon].
     * Una consulta agregada por módulo (no una por empresa).
     */
    public function lastActivityFor(array $companyIds): array
    {
        if (empty($companyIds)) {
            return [];
        }

        $last = array_fill_keys($companyIds, null);

        $merge = function (iterable $rows) use (&$last) {
            foreach ($rows as $r) {
                if ($r->last === null) {
                    continue;
                }
                $at = Carbon::parse($r->last);
                if ($last[$r->company_id] === null || $at->gt($last[$r->company_id])) {
                    $last[$r->company_id] = $at;
                }
            }
        };

        foreach (self::SOURCES as $src) {
            $merge($this->safe(fn () => DB::table($src['table'])
                ->selectRaw('company_id, MAX(created_at) as last')
                ->whereIn('company_id', $companyIds)
                ->whereNull('deleted_at')
                ->groupBy('company_id')
                ->get()) ?? []);
        }

        // Sesiones de caja: sin company_id propio, se une por la caja.
        $merge($this->safe(fn () => DB::table('cash_register_sessions as s')
            ->join('cash_registers as r', 'r.id', '=', 's.cash_register_id')
            ->selectRaw('r.company_id, MAX(s.opened_at) as last')
            ->whereIn('r.company_id', $companyIds)
            ->groupBy('r.company_id')
            ->get()) ?? []);

        return $last;
    }

    /**
     * Ejecuta una consulta de monitoreo y devuelve null si falla (p. ej. la
     * tabla de un módulo aún no existe en esa instalación porque no se corrió
     * su script SQL). El panel del operador nunca debe caerse por eso.
     */
    private function safe(callable $query): mixed
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Dashboard de uso de una empresa (vista show). */
    public function dashboardFor(Company $company): array
    {
        $cid   = $company->id;
        $since = now()->subDays(self::RECENT_DAYS);

        $modules = [];
        $lastActivity = null;

        foreach (self::SOURCES as $key => $src) {
            $row = $this->safe(fn () => DB::table($src['table'])
                ->selectRaw('MAX(created_at) as last, COUNT(*) as total, SUM(created_at >= ?) as recent', [$since])
                ->where('company_id', $cid)
                ->whereNull('deleted_at')
                ->first());

            $modules[$key] = $this->moduleRow($src, $row);
            $lastActivity  = $this->later($lastActivity, $modules[$key]['last_at']);
        }

        $row = $this->safe(fn () => DB::table('cash_register_sessions as s')
            ->join('cash_registers as r', 'r.id', '=', 's.cash_register_id')
            ->selectRaw('MAX(s.opened_at) as last, COUNT(*) as total, SUM(s.opened_at >= ?) as recent', [$since])
            ->where('r.company_id', $cid)
            ->first());
        $modules['cash_sessions'] = $this->moduleRow(
            ['label' => 'Sesiones de caja', 'icon' => 'bi-safe'], $row
        );
        $lastActivity = $this->later($lastActivity, $modules['cash_sessions']['last_at']);

        // Presencia: última vez que alguien entró a ESTA empresa, y cuántos en 30 días.
        // Tolerante a que aún no se haya corrido el script SQL de `last_seen_at`.
        $seen = $this->safe(fn () => DB::table('company_user')
            ->selectRaw('MAX(last_seen_at) as last, SUM(last_seen_at >= ?) as recent, COUNT(*) as total', [$since])
            ->where('company_id', $cid)
            ->first());

        $lastSeen = $seen?->last ? Carbon::parse($seen->last) : null;

        return [
            'status'           => $this->status($lastActivity),
            'last_activity_at' => $lastActivity,
            'last_seen_at'     => $lastSeen,
            'active_users'     => (int) ($seen->recent ?? 0),
            'total_users'      => (int) ($seen->total ?? 0),
            'recent_days'      => self::RECENT_DAYS,
            'modules'          => $modules,
        ];
    }

    /**
     * Estado según la antigüedad de la última actividad. Devuelve
     * ['key', 'label', 'color'] listo para pintar un badge.
     */
    public function status(?Carbon $lastActivity): array
    {
        if ($lastActivity === null) {
            return ['key' => 'never', 'label' => 'Sin actividad', 'color' => 'secondary'];
        }

        $days = (int) $lastActivity->diffInDays(now());

        return match (true) {
            $days < 1  => ['key' => 'today', 'label' => 'Activa hoy',    'color' => 'success'],
            $days < 7  => ['key' => 'week',  'label' => 'Esta semana',   'color' => 'success'],
            $days < 30 => ['key' => 'month', 'label' => 'Este mes',      'color' => 'warning'],
            default    => ['key' => 'idle',  'label' => "Inactiva hace {$days} días", 'color' => 'danger'],
        };
    }

    private function moduleRow(array $src, ?object $row): array
    {
        return [
            'label'   => $src['label'],
            'icon'    => $src['icon'],
            'last_at' => $row?->last ? Carbon::parse($row->last) : null,
            'total'   => (int) ($row->total ?? 0),
            'recent'  => (int) ($row->recent ?? 0),
        ];
    }

    private function later(?Carbon $a, ?Carbon $b): ?Carbon
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $b->gt($a) ? $b : $a;
    }
}
