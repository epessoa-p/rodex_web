<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Tesorería para el móvil: cuentas (efectivo/banco) e ingresos/gastos sobre
 * ellas. Aislamiento por empresa vía global scope (BelongsToCompany) en las
 * consultas y route-model binding; el alta usa tenant_company.
 */
class TreasuryController extends Controller
{
    /** Listado de cuentas con saldos + saldo total. */
    public function index(Request $request)
    {
        $accounts = TreasuryAccount::query()->latest()->get();

        return response()->json([
            'data' => [
                'total_balance' => (float) $accounts->sum('balance'),
                'accounts'      => $accounts->map(fn (TreasuryAccount $a) => $this->accountPayload($a))->values(),
            ],
        ]);
    }

    /** Crea una cuenta (con saldo de apertura opcional). */
    public function storeAccount(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => ['required', Rule::in(array_keys(TreasuryAccount::TYPES))],
            'bank_name'       => 'nullable|string|max:255',
            'account_number'  => 'nullable|string|max:100',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $account = DB::transaction(function () use ($validated, $company) {
            $opening = (float) ($validated['opening_balance'] ?? 0);

            $account = TreasuryAccount::create([
                'company_id'     => $company->id,
                'name'           => $validated['name'],
                'type'           => $validated['type'],
                'bank_name'      => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'balance'        => 0,
                'active'         => true,
            ]);

            if ($opening > 0) {
                $this->registerMovement($account, 'in', 'capital_injection', $opening, 'Saldo inicial de apertura');
            }

            return $account;
        });

        return response()->json(['data' => $this->accountPayload($account->refresh())], 201);
    }

    /** Detalle de una cuenta con sus últimos movimientos. */
    public function show(TreasuryAccount $account)
    {
        $movements = $account->movements()
            ->with('user:id,name')
            ->latest('movement_date')->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (TreasuryMovement $m) => $this->movementPayload($m));

        return response()->json([
            'data' => [
                ...$this->accountPayload($account),
                'movements' => $movements,
            ],
        ]);
    }

    /**
     * Registra un ingreso o gasto sobre la cuenta. Categorías permitidas:
     * capital_injection / adjustment_in (ingreso), expense / adjustment_out (gasto).
     */
    public function storeMovement(Request $request, TreasuryAccount $account)
    {
        $validated = $request->validate([
            'category'    => ['required', Rule::in(['capital_injection', 'adjustment_in', 'adjustment_out', 'expense'])],
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $type = TreasuryMovement::CATEGORIES[$validated['category']]['type'];

        if ($type === 'out' && $validated['amount'] > (float) $account->balance) {
            return response()->json([
                'message' => 'El monto supera el saldo disponible de la cuenta.',
            ], 422);
        }

        $movement = DB::transaction(fn () => $this->registerMovement(
            $account, $type, $validated['category'],
            (float) $validated['amount'], $validated['description'] ?? null
        ));

        return response()->json([
            'data' => [
                'account'   => $this->accountPayload($account->refresh()),
                'movement'  => $this->movementPayload($movement),
            ],
        ], 201);
    }

    /** Crea el movimiento y ajusta el balance de la cuenta. */
    private function registerMovement(TreasuryAccount $account, string $type, string $category, float $amount, ?string $description): TreasuryMovement
    {
        $movement = TreasuryMovement::create([
            'company_id'          => $account->company_id,
            'treasury_account_id' => $account->id,
            'user_id'             => auth()->id(),
            'type'                => $type,
            'category'            => $category,
            'amount'              => $amount,
            'description'         => $description,
            'movement_date'       => now(),
        ]);

        $account->increment('balance', $type === 'in' ? $amount : -$amount);

        return $movement;
    }

    private function accountPayload(TreasuryAccount $a): array
    {
        return [
            'id'             => $a->id,
            'name'           => $a->name,
            'type'           => $a->type,
            'type_label'     => $a->type_label,
            'bank_name'      => $a->bank_name,
            'account_number' => $a->account_number,
            'balance'        => (float) $a->balance,
            'active'         => (bool) $a->active,
        ];
    }

    private function movementPayload(TreasuryMovement $m): array
    {
        return [
            'id'             => $m->id,
            'type'           => $m->type,
            'category'       => $m->category,
            'category_label' => $m->category_label,
            'amount'         => (float) $m->amount,
            'description'    => $m->description,
            'user'           => $m->user?->name,
            'date'           => $m->movement_date?->toIso8601String(),
        ];
    }
}
