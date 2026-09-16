<?php

namespace App\Services\Expenses;

use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\ExpenseService;
use App\Models\Personal;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gastos y pago a personal (Pagos → Gastos / Personal), compartido por la API
 * del móvil y el hub "Pagos" de la web: resumen (overview) y registro del
 * egreso saliendo de la caja abierta o de una cuenta de tesorería.
 *
 * Registra EXACTAMENTE como Cash\ExpenseController::resolveExpense: misma
 * categoría, descripción y referencia, para que caiga igual en Caja,
 * Tesorería y el Estado de resultados. Asume el tenant activo (global scope).
 */
class ExpenseRecorder
{
    /** Categorías de caja que cuentan como "gasto" (no nómina) para el total del mes. */
    public const EXPENSE_CATEGORIES = ['expense_service', 'expense_operational', 'expense_transport'];

    /** Todo lo que necesitan los tabs Gastos y Personal, en una llamada. */
    public function overview(): array
    {
        $monthStart = today()->startOfMonth();

        // Último pago por referencia (servicio / personal), en caja y tesorería.
        $lastByRef = function (string $refType, ?Carbon $since = null) {
            $rows = collect();
            $cash = CashMovement::where('type', 'expense')->where('reference_type', $refType)
                ->when($since, fn ($q) => $q->where('movement_date', '>=', $since))
                ->get(['reference_id', 'amount', 'movement_date', 'description']);
            $tre = TreasuryMovement::where('type', 'out')->where('reference_type', $refType)
                ->when($since, fn ($q) => $q->where('movement_date', '>=', $since))
                ->get(['reference_id', 'amount', 'movement_date', 'description']);
            foreach ($cash->concat($tre) as $m) {
                $cur = $rows[$m->reference_id] ?? null;
                if (! $cur || $m->movement_date->gt($cur->movement_date)) {
                    $rows[$m->reference_id] = $m;
                }
            }
            return $rows;
        };

        $servicePaid  = $lastByRef(ExpenseService::class, $monthStart);
        $personalLast = $lastByRef(Personal::class);

        $services = ExpenseService::where('active', true)->orderBy('type')->orderBy('name')->get()
            ->map(function (ExpenseService $s) use ($servicePaid) {
                $p = $servicePaid[$s->id] ?? null;
                return [
                    'id'             => $s->id,
                    'name'           => $s->name,
                    'type'           => $s->type,
                    'type_label'     => $s->type_label,
                    'default_amount' => (float) ($s->default_amount ?? 0),
                    'paid_this_month' => $p ? [
                        'date'   => $p->movement_date->toDateString(),
                        'amount' => (float) $p->amount,
                    ] : null,
                ];
            })->values()->all();

        $personal = Personal::with('cargo:id,name')->where('active', true)->orderBy('full_name')->get()
            ->map(function (Personal $p) use ($personalLast) {
                $last = $personalLast[$p->id] ?? null;
                return [
                    'id'    => $p->id,
                    'name'  => $p->full_name,
                    'cargo' => $p->cargo?->name,
                    'last_payment' => $last ? [
                        'date'   => $last->movement_date->toDateString(),
                        'amount' => (float) $last->amount,
                        'period' => $this->periodFromDescription($last->description),
                    ] : null,
                ];
            })->values()->all();

        return [
            'month_total'         => $this->monthTotal(self::EXPENSE_CATEGORIES, ['expense'], $monthStart),
            'payroll_month_total' => $this->monthTotal(['expense_payroll'], ['payroll'], $monthStart),
            'services'            => $services,
            'personal'            => $personal,
            'recent'              => $this->recent(),
        ];
    }

    /**
     * Registra el egreso. $data ya validado: kind (service|other|transport|payroll),
     * expense_service_id, personal_id, concept, period, amount, payment_source
     * (cash|treasury), treasury_account_id, method, notes.
     * Devuelve [id, date, description, amount, source].
     *
     * @throws ExpenseException con code no_open_session | insufficient_balance
     */
    public function record(array $data, int $companyId, int $userId, ?CashRegisterSession $session): array
    {
        $amount = round((float) $data['amount'], 2);
        [$cashCategory, $treasuryCategory, $description, $refType, $refId] = $this->resolve($data);
        if (! empty($data['notes'])) {
            $description .= ' — ' . $data['notes'];
        }

        if (($data['payment_source'] ?? 'cash') === 'cash') {
            if (! $session) {
                throw new ExpenseException('Necesitas tener tu caja abierta para registrar un gasto.', 'no_open_session');
            }
            if ($amount > (float) $session->expectedBalance() + 0.001) {
                throw new ExpenseException(
                    'La caja no tiene saldo suficiente (disponible: ' . number_format($session->expectedBalance(), 2) . ').',
                    'insufficient_balance'
                );
            }

            $movement = CashMovement::create([
                'company_id'               => $companyId,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => $userId,
                'type'                     => 'expense',
                'category'                 => $cashCategory,
                'amount'                   => $amount,
                'method'                   => $data['method'] ?? 'efectivo',
                'reference_type'           => $refType,
                'reference_id'             => $refId,
                'description'              => $description,
                'movement_date'            => now(),
            ]);
            $source = 'Caja';
        } else {
            $account = TreasuryAccount::find($data['treasury_account_id'] ?? null);
            if (! $account || $amount > (float) $account->balance + 0.001) {
                throw new ExpenseException(
                    'La cuenta no tiene saldo suficiente (disponible: ' . number_format((float) ($account?->balance ?? 0), 2) . ').',
                    'insufficient_balance'
                );
            }

            $movement = DB::transaction(function () use ($companyId, $userId, $account, $treasuryCategory, $amount, $refType, $refId, $description) {
                $m = TreasuryMovement::create([
                    'company_id'          => $companyId,
                    'treasury_account_id' => $account->id,
                    'user_id'             => $userId,
                    'type'                => 'out',
                    'category'            => $treasuryCategory,
                    'amount'              => $amount,
                    'reference_type'      => $refType,
                    'reference_id'        => $refId,
                    'description'         => $description,
                    'movement_date'       => now(),
                ]);
                $account->decrement('balance', $amount);
                return $m;
            });
            $source = $account->name;
        }

        return [
            'id'          => $movement->id,
            'date'        => $movement->movement_date->toDateString(),
            'description' => $description,
            'amount'      => $amount,
            'source'      => $source,
        ];
    }

    /**
     * [categoría caja, categoría tesorería, descripción, reference_type, reference_id]
     * con los mismos textos que Cash\ExpenseController::resolveExpense (web).
     */
    private function resolve(array $d): array
    {
        $period = trim((string) ($d['period'] ?? ''));

        switch ($d['kind']) {
            case 'service':
                $name = trim((string) ($d['concept'] ?? ''));
                $ref  = null;
                if (! empty($d['expense_service_id'])) {
                    $ref  = ExpenseService::find($d['expense_service_id']);
                    $name = $ref?->name ?? $name;
                }
                $name = $name !== '' ? $name : 'Servicio';
                $desc = 'Servicio: ' . $name . ($period ? ' · ' . $period : '');
                return ['expense_service', 'expense', $desc, $ref ? ExpenseService::class : null, $ref?->id];

            case 'payroll':
                $p    = Personal::findOrFail($d['personal_id']);
                $desc = 'Pago a personal' . ($period ? ' (' . $period . ')' : '') . ' · ' . $p->full_name;
                return ['expense_payroll', 'payroll', $desc, Personal::class, $p->id];

            case 'transport':
                $c = trim((string) ($d['concept'] ?? '')) ?: 'envío';
                return ['expense_transport', 'expense', 'Transporte / envío: ' . $c, null, null];

            default: // other
                $c = trim((string) ($d['concept'] ?? '')) ?: 'Gasto operativo';
                return ['expense_operational', 'expense', $c, null, null];
        }
    }

    /** Extrae "(Sep 2026)" de la descripción de un pago a personal. */
    private function periodFromDescription(?string $desc): ?string
    {
        return $desc && preg_match('/\(([^)]+)\)/', $desc, $m) ? $m[1] : null;
    }

    private function monthTotal(array $cashCategories, array $treasuryCategories, Carbon $since): float
    {
        $cash = (float) CashMovement::where('type', 'expense')->whereIn('category', $cashCategories)
            ->where('movement_date', '>=', $since)->sum('amount');
        $tre  = (float) TreasuryMovement::where('type', 'out')->whereIn('category', $treasuryCategories)
            ->where('movement_date', '>=', $since)->sum('amount');

        return round($cash + $tre, 2);
    }

    /** Últimos 10 egresos de gasto/nómina, de caja y tesorería, por fecha desc. */
    private function recent(): array
    {
        $cash = CashMovement::where('type', 'expense')
            ->whereIn('category', [...self::EXPENSE_CATEGORIES, 'expense_payroll'])
            ->latest('movement_date')->latest('id')->limit(10)->get()
            ->map(fn ($m) => [
                'date'        => $m->movement_date->toDateString(),
                'sort'        => $m->movement_date->timestamp,
                'description' => $m->description,
                'amount'      => (float) $m->amount,
                'source'      => 'Caja',
            ]);

        $tre = TreasuryMovement::with('treasuryAccount:id,name')->where('type', 'out')
            ->whereIn('category', ['expense', 'payroll'])
            ->latest('movement_date')->latest('id')->limit(10)->get()
            ->map(fn ($m) => [
                'date'        => $m->movement_date->toDateString(),
                'sort'        => $m->movement_date->timestamp,
                'description' => $m->description,
                'amount'      => (float) $m->amount,
                'source'      => $m->treasuryAccount?->name ?? 'Tesorería',
            ]);

        return $cash->concat($tre)->sortByDesc('sort')->take(10)
            ->map(fn ($r) => collect($r)->except('sort')->all())->values()->all();
    }
}
