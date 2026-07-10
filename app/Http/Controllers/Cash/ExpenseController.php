<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\ExpenseService;
use App\Models\Personal;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\Supplier;
use App\Models\Purchases\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    use ResolvesCashSession;

    /** Datos para el modal de gasto (lazy-load al abrir). */
    public function data()
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        $personal = Personal::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('full_name')->get(['id', 'full_name']);

        $services = ExpenseService::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name', 'type', 'default_amount']);

        $suppliers = Supplier::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);

        $pendingPurchases = Purchase::with('supplier:id,name')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('payment_status', ['pending', 'partial'])
            ->orderByDesc('purchase_date')->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'supplier_id' => $p->supplier_id,
                'code'        => $p->code,
                'balance'     => (float) $p->balance,
                'date'        => optional($p->purchase_date)->format('d/m/Y'),
            ])->filter(fn ($p) => $p['balance'] > 0.01)->values();

        $session = $this->currentOpenSession();

        return response()->json([
            'personal'         => $personal,
            'services'         => $services,
            'suppliers'        => $suppliers,
            'pendingPurchases' => $pendingPurchases,
            'session'          => $session ? [
                'balance' => round($session->expectedBalance(), 2),
                'income'  => round($session->totalIncome(), 2),
                'expense' => round($session->totalExpense(), 2),
                'opening' => round((float) $session->opening_amount, 2),
            ] : null,
        ]);
    }

    /** Registra un gasto contra la caja abierta del usuario. */
    public function store(Request $request)
    {
        $session = $this->currentOpenSession();
        if (!$session) {
            return back()->withErrors(['error' => 'Necesitas tener tu caja abierta para registrar un gasto.']);
        }

        $validated = $request->validate([
            'expense_type'  => 'required|in:servicio,personal,transporte,proveedor,otro',
            'amount'        => 'required|numeric|min:0.01',
            'method'        => 'nullable|string|max:30',
            'movement_date' => 'required|date',
            'notes'         => 'nullable|string|max:500',
            // según tipo
            'service_id'    => 'nullable|exists:expense_services,id',
            'concept'       => 'nullable|string|max:255',
            'personal_id'   => 'required_if:expense_type,personal|nullable|exists:personals,id',
            'period'        => 'nullable|string|max:30',
            'supplier_id'   => 'required_if:expense_type,proveedor|nullable|exists:suppliers,id',
            'purchase_id'   => 'required_if:expense_type,proveedor|nullable|exists:purchases,id',
        ]);

        $companyId = $session->cashRegister->company_id;
        $amount    = (float) $validated['amount'];
        $method    = $validated['method'] ?? 'efectivo';
        $date      = $validated['movement_date'];
        $notes     = $validated['notes'] ?? null;

        try {
            DB::transaction(function () use ($validated, $session, $companyId, $amount, $method, $date, $notes) {
                [$category, $description, $refType, $refId] = $this->resolveExpense($validated, $companyId, $amount, $method, $date);

                CashMovement::create([
                    'company_id'               => $companyId,
                    'cash_register_id'         => $session->cash_register_id,
                    'cash_register_session_id' => $session->id,
                    'user_id'                  => auth()->id(),
                    'type'                     => 'expense',
                    'category'                 => $category,
                    'amount'                   => $amount,
                    'method'                   => $method,
                    'reference_type'           => $refType,
                    'reference_id'             => $refId,
                    'description'              => $notes ? ($description . ' — ' . $notes) : $description,
                    'movement_date'            => $date,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Error al registrar gasto', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No se pudo registrar el gasto: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Gasto registrado correctamente.');
    }

    /** Devuelve [category, description, reference_type, reference_id] y aplica integración CxP si es proveedor. */
    private function resolveExpense(array $d, int $companyId, float $amount, string $method, string $date): array
    {
        switch ($d['expense_type']) {
            case 'servicio':
                $name = trim((string) ($d['concept'] ?? ''));
                $ref  = null;
                if (!empty($d['service_id'])) {
                    $svc = ExpenseService::find($d['service_id']);
                    if ($svc) { $name = $svc->name; $ref = $svc; }
                }
                if ($name === '') $name = 'Servicio';
                return ['expense_service', 'Servicio: ' . $name, $ref ? ExpenseService::class : null, $ref?->id];

            case 'personal':
                $p = Personal::findOrFail($d['personal_id']);
                $period = trim((string) ($d['period'] ?? ''));
                $desc = 'Pago a personal' . ($period ? ' (' . $period . ')' : '') . ' · ' . $p->full_name;
                return ['expense_payroll', $desc, Personal::class, $p->id];

            case 'transporte':
                $c = trim((string) ($d['concept'] ?? '')) ?: 'envío';
                return ['expense_transport', 'Transporte / envío: ' . $c, null, null];

            case 'proveedor':
                $purchase = Purchase::where('company_id', $companyId)->findOrFail($d['purchase_id']);
                $balance  = (float) $purchase->balance;
                if ($amount > $balance + 0.01) {
                    throw ValidationException::withMessages([
                        'amount' => 'El monto supera el saldo de la factura (Bs. ' . number_format($balance, 2) . ').',
                    ]);
                }
                // Conciliar la factura (CxP) + registrar el pago (desde caja, sin tesorería)
                SupplierPayment::create([
                    'company_id'          => $companyId,
                    'purchase_id'         => $purchase->id,
                    'treasury_account_id' => null,
                    'amount'              => $amount,
                    'payment_date'        => $date,
                    'method'              => $method,
                    'reference'           => 'CAJA',
                    'notes'               => 'Pago desde caja',
                    'user_id'             => auth()->id(),
                ]);
                $purchase->increment('paid_amount', $amount);
                $purchase->refresh()->recalcPaymentStatus();

                $desc = 'Pago compra ' . $purchase->code . ' — ' . ($purchase->supplier?->name ?? 'Proveedor');
                return ['expense_supplier', $desc, Purchase::class, $purchase->id];

            default: // otro
                $c = trim((string) ($d['concept'] ?? '')) ?: 'Gasto operativo';
                return ['expense_operational', $c, null, null];
        }
    }
}
