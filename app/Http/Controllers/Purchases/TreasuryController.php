<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TreasuryController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = TreasuryAccount::query()->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        $accounts    = $query->get();
        $totalBalance = $accounts->sum('balance');

        return view('purchases.treasury.index', compact('accounts', 'totalBalance'));
    }

    public function createAccount()
    {
        return view('purchases.treasury.account-form');
    }

    public function storeAccount()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = request()->validate([
            'name'           => 'required|string|max:255',
            'type'           => ['required', Rule::in(array_keys(TreasuryAccount::TYPES))],
            'bank_name'      => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'opening_balance'=> 'nullable|numeric|min:0',
            'active'         => 'sometimes|boolean',
        ]);

        try {
            DB::transaction(function () use ($validated, $companyId) {
                $opening = (float) ($validated['opening_balance'] ?? 0);

                $account = TreasuryAccount::create([
                    'company_id'     => $companyId,
                    'name'           => $validated['name'],
                    'type'           => $validated['type'],
                    'bank_name'      => $validated['bank_name'] ?? null,
                    'account_number' => $validated['account_number'] ?? null,
                    'balance'        => 0,
                    'active'         => request()->boolean('active', true),
                ]);

                if ($opening > 0) {
                    $this->registerMovement($account, 'in', 'capital_injection', $opening, 'Saldo inicial de apertura');
                }
            });

            return redirect()->route('treasury.index')->with('success', 'Cuenta de tesorería creada.');
        } catch (\Throwable $e) {
            Log::error('Error al crear cuenta tesorería', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function editAccount(TreasuryAccount $account)
    {
        $this->authorizeAccount($account);
        return view('purchases.treasury.account-form', compact('account'));
    }

    public function updateAccount(TreasuryAccount $account)
    {
        $this->authorizeAccount($account);

        $validated = request()->validate([
            'name'           => 'required|string|max:255',
            'type'           => ['required', Rule::in(array_keys(TreasuryAccount::TYPES))],
            'bank_name'      => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'active'         => 'sometimes|boolean',
        ]);

        try {
            $account->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('treasury.index')->with('success', 'Cuenta actualizada.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function show(TreasuryAccount $account)
    {
        $this->authorizeAccount($account);
        $movements = $account->movements()->with('user')->latest('movement_date')->latest('id')->paginate(30);
        return view('purchases.treasury.show', compact('account', 'movements'));
    }

    /** Registrar aporte de capital o ajuste manual */
    public function storeCapital(TreasuryAccount $account)
    {
        $this->authorizeAccount($account);

        $validated = request()->validate([
            'category'    => ['required', Rule::in(['capital_injection', 'adjustment_in', 'adjustment_out', 'expense'])],
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $type = TreasuryMovement::CATEGORIES[$validated['category']]['type'];

        if ($type === 'out' && $validated['amount'] > $account->balance) {
            return back()->withErrors(['error' => 'El monto supera el saldo disponible de la cuenta.']);
        }

        try {
            DB::transaction(function () use ($account, $type, $validated) {
                $this->registerMovement(
                    $account, $type, $validated['category'],
                    $validated['amount'], $validated['description'] ?? null
                );
            });
            return back()->with('success', 'Movimiento de tesorería registrado.');
        } catch (\Throwable $e) {
            Log::error('Error movimiento tesorería', ['msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /** Helper compartido: crea el movimiento y ajusta el balance */
    private function registerMovement(TreasuryAccount $account, string $type, string $category, float $amount, ?string $description, array $reference = []): TreasuryMovement
    {
        $movement = TreasuryMovement::create([
            'company_id'          => $account->company_id,
            'treasury_account_id' => $account->id,
            'user_id'             => auth()->id(),
            'type'                => $type,
            'category'            => $category,
            'amount'              => $amount,
            'reference_type'      => $reference['type'] ?? null,
            'reference_id'        => $reference['id'] ?? null,
            'description'         => $description,
            'movement_date'       => now(),
        ]);

        $account->increment('balance', $type === 'in' ? $amount : -$amount);

        return $movement;
    }

    private function authorizeAccount(TreasuryAccount $account): void
    {
        if (!auth()->user()->is_super_admin && $account->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
