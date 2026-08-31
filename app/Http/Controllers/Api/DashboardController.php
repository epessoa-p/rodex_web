<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use Illuminate\Support\Carbon;

/**
 * Series comparativas para el dashboard móvil: por semana (últimas 8) y por
 * mes (últimos 6), con monto y cantidad. Aislamiento por empresa vía global scope.
 */
class DashboardController extends Controller
{
    private const WEEKS = 8;
    private const MONTHS = 6;

    public function sales()
    {
        return response()->json([
            'data' => $this->buildSeries(fn () => Sale::where('status', 'completed'), 'sale_date'),
        ]);
    }

    public function workshop()
    {
        return response()->json([
            'data' => $this->buildSeries(
                fn () => WorkOrder::where('status', 'entregada')->whereNotNull('delivered_at'),
                'delivered_at'
            ),
        ]);
    }

    public function purchases()
    {
        return response()->json([
            'data' => $this->buildSeries(fn () => Purchase::query(), 'purchase_date'),
        ]);
    }

    /** @param \Closure():\Illuminate\Database\Eloquent\Builder $freshQuery */
    private function buildSeries(\Closure $freshQuery, string $dateCol): array
    {
        return [
            'weekly'  => $this->weekly($freshQuery, $dateCol),
            'monthly' => $this->monthly($freshQuery, $dateCol),
        ];
    }

    private function weekly(\Closure $freshQuery, string $dateCol): array
    {
        // Lunes de la semana más antigua a incluir.
        $start = Carbon::today()->startOfWeek(Carbon::MONDAY)->subWeeks(self::WEEKS - 1);

        $rows = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("YEARWEEK($dateCol, 3) as yw, SUM(total) as amount, COUNT(*) as cnt")
            ->groupBy('yw')
            ->pluck('amount', 'yw');   // amount por yw
        $counts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("YEARWEEK($dateCol, 3) as yw, COUNT(*) as cnt")
            ->groupBy('yw')
            ->pluck('cnt', 'yw');

        $out = [];
        for ($i = 0; $i < self::WEEKS; $i++) {
            $monday = $start->copy()->addWeeks($i);
            $yw = (int) $monday->format('oW'); // ISO year+week -> coincide con YEARWEEK modo 3
            $out[] = [
                'label'  => $monday->format('d/m'),
                'amount' => (float) ($rows[$yw] ?? 0),
                'count'  => (int) ($counts[$yw] ?? 0),
            ];
        }

        return $out;
    }

    private function monthly(\Closure $freshQuery, string $dateCol): array
    {
        $start = Carbon::today()->startOfMonth()->subMonthsNoOverflow(self::MONTHS - 1);

        $amounts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, SUM(total) as amount")
            ->groupBy('ym')
            ->pluck('amount', 'ym');
        $counts = $freshQuery()
            ->where($dateCol, '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, COUNT(*) as cnt")
            ->groupBy('ym')
            ->pluck('cnt', 'ym');

        $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $out = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $m = $start->copy()->addMonthsNoOverflow($i);
            $ym = $m->format('Y-m');
            $out[] = [
                'label'  => $months[$m->month - 1],
                'amount' => (float) ($amounts[$ym] ?? 0),
                'count'  => (int) ($counts[$ym] ?? 0),
            ];
        }

        return $out;
    }
}
