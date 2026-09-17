<?php

namespace App\Services\Reports;

use App\Models\CashMovement;
use App\Models\Purchases\TreasuryMovement;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use Illuminate\Support\Carbon;

/**
 * Estado de resultados por movimientos (base efectivo): ingresos y egresos
 * de caja + tesorería, agrupados por categoría (etiqueta), en un período.
 */
class IncomeStatementService
{
    /** Fecha del movimiento más antiguo (caja o tesorería) de la empresa; hoy si no hay ninguno. */
    public function firstMovementDate(): Carbon
    {
        $dates = array_filter([
            CashMovement::min('movement_date'),
            TreasuryMovement::min('movement_date'),
        ]);

        return $dates ? Carbon::parse(min($dates))->startOfDay() : Carbon::today();
    }

    /**
     * Rango [from, to] de un preset: this_month | last_month | all. Devuelve
     * null si el preset no existe (se usa el rango manual).
     */
    public function presetRange(?string $preset): ?array
    {
        $today = Carbon::today();

        return match ($preset) {
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->startOfMonth()->subDay(),
            ],
            'all' => [$this->firstMovementDate(), $today],
            default => null,
        };
    }

    public function build(Carbon $from, Carbon $to): array
    {
        $income  = [];   // label => amount
        $expense = [];

        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        // Caja. Se agrupa además por reference_type para poder separar, dentro
        // de la categoría "Venta", las ventas de mostrador de los cobros de OT.
        CashMovement::whereBetween('movement_date', $range)
            ->selectRaw('category, type, reference_type, SUM(amount) as amt')
            ->groupBy('category', 'type', 'reference_type')
            ->get()
            ->each(function ($r) use (&$income, &$expense) {
                $label = $this->cashLabel($r->category, $r->reference_type);
                $amt = (float) $r->amt;
                if ($r->type === 'income') {
                    $income[$label] = ($income[$label] ?? 0) + $amt;
                } else {
                    $expense[$label] = ($expense[$label] ?? 0) + $amt;
                }
            });

        // Tesorería (type in/out)
        TreasuryMovement::whereBetween('movement_date', $range)
            ->selectRaw('category, type, SUM(amount) as amt')
            ->groupBy('category', 'type')
            ->get()
            ->each(function ($r) use (&$income, &$expense) {
                $label = TreasuryMovement::CATEGORIES[$r->category]['label'] ?? $r->category;
                $amt = (float) $r->amt;
                if ($r->type === 'in') {
                    $income[$label] = ($income[$label] ?? 0) + $amt;
                } else {
                    $expense[$label] = ($expense[$label] ?? 0) + $amt;
                }
            });

        $toLines = function (array $map) {
            arsort($map);
            return array_map(fn ($label, $amount) => [
                'label'  => $label,
                'amount' => round($amount, 2),
            ], array_keys($map), array_values($map));
        };

        $totalIncome  = round(array_sum($income), 2);
        $totalExpense = round(array_sum($expense), 2);

        return [
            'from'          => $from->toDateString(),
            'to'            => $to->toDateString(),
            'income'        => $toLines($income),
            'expense'       => $toLines($expense),
            'total_income'  => $totalIncome,
            'total_expense' => $totalExpense,
            'net'           => round($totalIncome - $totalExpense, 2),
        ];
    }

    /**
     * Etiqueta de un movimiento de caja. La categoría "Venta" se abre en dos:
     * ventas de mostrador (POS) y cobros de OT (taller), según el origen.
     */
    private function cashLabel(string $category, ?string $referenceType): string
    {
        if ($category === 'sale') {
            if ($referenceType === WorkOrder::class) {
                return 'Servicios / Taller (OT)';
            }
            if ($referenceType === Sale::class) {
                return 'Ventas (mostrador)';
            }
        }

        return CashMovement::CATEGORIES[$category]['label'] ?? $category;
    }
}
