<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\ExpenseService;
use App\Models\Personal;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Gastos desde el móvil (Pagos → Gastos / Personal): servicios recurrentes del
 * catálogo (luz, agua, internet…), gastos libres, transporte y pago a personal,
 * saliendo de la caja abierta o de una cuenta de tesorería.
 *
 * Registra EXACTAMENTE como la web (Cash\ExpenseController::resolveExpense):
 * misma categoría, descripción y referencia, para que caiga igual en Caja,
 * Tesorería y el Estado de resultados. Aislamiento por empresa vía global scope.
 */
class ExpenseController extends Controller
{
    use ResolvesCashSession;

    /** Categorías de caja que cuentan como "gasto" (no nómina) para el total del mes. */
    private const EXPENSE_CATEGORIES = ['expense_service', 'expense_operational', 'expense_transport'];

    /** Todo lo que necesitan los tabs Gastos y Personal, en un request. */
    public function overview()
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
            })->values();

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
            })->values();

        return response()->json(['data' => [
            'month_total'         => $this->monthTotal(self::EXPENSE_CATEGORIES, ['expense'], $monthStart),
            'payroll_month_total' => $this->monthTotal(['expense_payroll'], ['payroll'], $monthStart),
            'services'            => $services,
            'personal'            => $personal,
            'recent'              => $this->recent(),
        ]]);
    }

    /** Registra un gasto (servicio / otro / transporte / nómina) desde caja o tesorería. */
    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'kind'                => ['required', Rule::in(['service', 'other', 'transport', 'payroll'])],
            'expense_service_id'  => ['nullable', Rule::exists('expense_services', 'id')->where('company_id', $cid)],
            'personal_id'         => ['nullable', 'required_if:kind,payroll', Rule::exists('personals', 'id')->where('company_id', $cid)],
            'concept'             => ['nullable', 'string', 'max:255'],
            'period'              => ['nullable', 'string', 'max:30'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_source'      => ['required', Rule::in(['cash', 'treasury'])],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $cid)],
            'method'              => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string', 'max:500'],
        ]);

        $amount = round((float) $data['amount'], 2);
        [$cashCategory, $treasuryCategory, $description, $refType, $refId] = $this->resolve($data);
        if (! empty($data['notes'])) {
            $description .= ' — ' . $data['notes'];
        }

        if ($data['payment_source'] === 'cash') {
            $session = $this->currentOpenSession();
            if (! $session) {
                return response()->json(['message' => 'Necesitas tener tu caja abierta para registrar un gasto.', 'code' => 'no_open_session'], 422);
            }
            if ($amount > (float) $session->expectedBalance() + 0.001) {
                return response()->json([
                    'message' => 'La caja no tiene saldo suficiente (disponible: ' . number_format($session->expectedBalance(), 2) . ').',
                    'code'    => 'insufficient_balance',
                ], 422);
            }

            $movement = CashMovement::create([
                'company_id'               => $cid,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
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
            $account = TreasuryAccount::find($data['treasury_account_id']);
            if (! $account || $amount > (float) $account->balance + 0.001) {
                return response()->json([
                    'message' => 'La cuenta no tiene saldo suficiente (disponible: ' . number_format((float) ($account?->balance ?? 0), 2) . ').',
                    'code'    => 'insufficient_balance',
                ], 422);
            }

            $movement = DB::transaction(function () use ($cid, $account, $treasuryCategory, $amount, $refType, $refId, $description) {
                $m = TreasuryMovement::create([
                    'company_id'          => $cid,
                    'treasury_account_id' => $account->id,
                    'user_id'             => auth()->id(),
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

        return response()->json(['data' => [
            'id'          => $movement->id,
            'date'        => $movement->movement_date->toDateString(),
            'description' => $description,
            'amount'      => $amount,
            'source'      => $source,
        ]], 201);
    }

    /** Alta rápida de un servicio recurrente (luz, agua, internet…) desde el móvil. */
    public function storeService(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'type'           => ['required', Rule::in(array_keys(ExpenseService::TYPES))],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $s = ExpenseService::create([
            'company_id'     => $cid,
            'name'           => trim($data['name']),
            'type'           => $data['type'],
            'default_amount' => (float) ($data['default_amount'] ?? 0),
            'active'         => true,
            'created_by'     => auth()->id(),
        ]);

        return response()->json(['data' => [
            'id'             => $s->id,
            'name'           => $s->name,
            'type'           => $s->type,
            'type_label'     => $s->type_label,
            'default_amount' => (float) $s->default_amount,
            'paid_this_month' => null,
        ]], 201);
    }

    // ── helpers ──────────────────────────────────────────────────

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
