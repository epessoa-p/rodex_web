<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleReturn;
use App\Models\Sales\SaleReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SaleReturnController extends Controller
{
    use ResolvesCashSession;

    public function index()
    {
        $user  = auth()->user();
        $query = SaleReturn::with(['sale.client'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('sales.returns.index', ['returns' => $query->paginate(15)]);
    }

    public function create(Sale $sale)
    {
        $this->authorizeSale($sale);

        if ($sale->status === 'cancelled') {
            return back()->withErrors(['error' => 'No se puede devolver una venta anulada.']);
        }

        $sale->load(['client', 'branch', 'items.product']);

        // Cantidad ya devuelta por cada item de venta
        $returnedByItem = SaleReturnItem::whereHas('saleReturn', fn ($q) => $q->where('sale_id', $sale->id))
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        return view('sales.returns.create', compact('sale', 'returnedByItem'));
    }

    public function store(Sale $sale, Request $request)
    {
        $this->authorizeSale($sale);

        $validated = $request->validate([
            'return_date'          => 'required|date',
            'refund_method'        => ['required', Rule::in(['cash', 'credit_note'])],
            'reason'               => 'nullable|string|max:255',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.quantity'     => 'nullable|integer|min:0',
        ]);

        // Debe haber al menos un item con cantidad > 0
        $hasQty = collect($validated['items'])->contains(fn ($i) => (float) ($i['quantity'] ?? 0) > 0);
        if (!$hasQty) {
            return back()->withInput()->withErrors(['error' => 'Indica la cantidad a devolver de al menos un producto.']);
        }

        $sale->load(['items', 'installments']);

        // Validar tope por item (vendido − ya devuelto)
        $returnedByItem = SaleReturnItem::whereHas('saleReturn', fn ($q) => $q->where('sale_id', $sale->id))
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        // Caja requerida si el reembolso es en efectivo
        $session = null;
        if ($validated['refund_method'] === 'cash') {
            $session = $this->currentOpenSession();
            if (!$session) {
                return back()->withInput()->withErrors(['error' => 'Para devolver en efectivo necesitas tener tu caja abierta.']);
            }
        }

        try {
            $return = DB::transaction(function () use ($sale, $validated, $session, $returnedByItem) {
                $warehouseId = $sale->branch?->warehouse_id;
                $total = 0;

                $return = SaleReturn::create([
                    'company_id'               => $sale->company_id,
                    'sale_id'                  => $sale->id,
                    'cash_register_session_id' => $session?->id,
                    'code'                     => $this->nextCode($sale->company_id),
                    'return_date'              => $validated['return_date'],
                    'refund_method'            => $validated['refund_method'],
                    'reason'                   => $validated['reason'] ?? null,
                    'total'                    => 0,
                    'notes'                    => $validated['notes'] ?? null,
                    'created_by'               => auth()->id(),
                ]);

                foreach ($validated['items'] as $row) {
                    $qty = (float) ($row['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $saleItem = $sale->items->firstWhere('id', $row['sale_item_id']);
                    if (!$saleItem) {
                        continue;
                    }

                    $alreadyReturned = (float) ($returnedByItem[$saleItem->id] ?? 0);
                    $returnable      = (float) $saleItem->quantity - $alreadyReturned;
                    if ($qty > $returnable) {
                        $qty = $returnable;
                    }
                    if ($qty <= 0) {
                        continue;
                    }

                    $lineTotal = $qty * (float) $saleItem->unit_price;
                    $total    += $lineTotal;

                    SaleReturnItem::create([
                        'sale_return_id' => $return->id,
                        'sale_item_id'   => $saleItem->id,
                        'product_id'     => $saleItem->product_id,
                        'quantity'       => $qty,
                        'unit_price'     => $saleItem->unit_price,
                        'subtotal'       => $lineTotal,
                    ]);

                    // Reingreso de stock
                    if ($warehouseId) {
                        InventoryMovement::create([
                            'company_id'    => $sale->company_id,
                            'warehouse_id'  => $warehouseId,
                            'branch_id'     => $sale->branch_id,
                            'product_id'    => $saleItem->product_id,
                            'user_id'       => auth()->id(),
                            'type'          => 'in',
                            'quantity'      => $qty,
                            'unit_cost'     => $saleItem->unit_price,
                            'reference'     => $return->code,
                            'notes'         => 'Devolución venta ' . $sale->code,
                            'movement_date' => $validated['return_date'],
                        ]);
                    }
                    Product::where('id', $saleItem->product_id)->increment('current_stock', $qty);
                }

                // ── Ajuste financiero de la venta ────────────────────
                // El valor devuelto (mercadería) se reparte entre:
                //  · saldo pendiente que se cancela (cuotas por pagar)
                //  · efectivo a reembolsar (solo lo que el cliente ya pagó)
                $balance = (float) $sale->total - (float) $sale->paid_amount;

                if ($validated['refund_method'] === 'cash') {
                    // Efectivo: devuelve lo ya pagado; cancela el resto del saldo
                    $cashRefund       = max(0, $total - $balance);
                    $balanceReduction = $total - $cashRefund;        // = min($total, $balance)
                    $newTotal = max(0, (float) $sale->total - $total);
                    $newPaid  = max(0, (float) $sale->paid_amount - $cashRefund);
                } else {
                    // Nota de crédito: solo condona el saldo pendiente, no devuelve dinero
                    $cashRefund       = 0;
                    $balanceReduction = min($total, $balance);
                    $newTotal = (float) $sale->total - $balanceReduction;
                    $newPaid  = (float) $sale->paid_amount;
                }

                $return->update(['total' => $total, 'refunded_amount' => $cashRefund]);

                // Reducir cuotas pendientes (de la más nueva a la más antigua)
                if ($balanceReduction > 0) {
                    $this->reduceInstallments($sale, $balanceReduction);
                }

                // Actualizar la venta y su estado de pago
                $sale->update(['total' => $newTotal, 'paid_amount' => $newPaid]);
                $sale->refresh()->recalcPaymentStatus();

                // Egreso de caja solo por el efectivo realmente reembolsado
                if ($session && $cashRefund > 0) {
                    CashMovement::create([
                        'company_id'               => $sale->company_id,
                        'cash_register_id'         => $session->cash_register_id,
                        'cash_register_session_id' => $session->id,
                        'user_id'                  => auth()->id(),
                        'type'                     => 'expense',
                        'category'                 => 'sale_return',
                        'amount'                   => $cashRefund,
                        'method'                   => 'efectivo',
                        'reference_type'           => SaleReturn::class,
                        'reference_id'             => $return->id,
                        'description'              => 'Devolución ' . $return->code . ' (venta ' . $sale->code . ')',
                        'movement_date'            => now(),
                    ]);
                }

                return $return;
            });

            return redirect()->route('sale-returns.show', $return)->with('success', 'Devolución registrada: ' . $return->code);
        } catch (\Throwable $e) {
            Log::error('Error al registrar devolución', ['sale' => $sale->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }

    public function show(SaleReturn $saleReturn)
    {
        $this->authorizeReturn($saleReturn);
        $saleReturn->load(['sale.client', 'createdBy', 'items.product', 'session.cashRegister']);
        return view('sales.returns.show', ['return' => $saleReturn]);
    }

    /**
     * Reduce las cuotas pendientes de la venta en $amount,
     * empezando por la más nueva (mayor number) hacia atrás.
     */
    private function reduceInstallments(Sale $sale, float $amount): void
    {
        $remaining = $amount;

        $pending = $sale->installments()
            ->whereColumn('paid_amount', '<', 'amount')
            ->orderByDesc('number')
            ->get();

        foreach ($pending as $inst) {
            if ($remaining <= 0.0001) {
                break;
            }
            $instBalance = (float) $inst->amount - (float) $inst->paid_amount;
            $take        = min($remaining, $instBalance);

            $inst->update(['amount' => (float) $inst->amount - $take]);
            $inst->refresh()->recalcStatus();
            $remaining -= $take;
        }
    }

    private function nextCode(int $companyId): string
    {
        $count = SaleReturn::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'DEV-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeSale(Sale $sale): void
    {
        if (!auth()->user()->is_super_admin && $sale->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function authorizeReturn(SaleReturn $return): void
    {
        if (!auth()->user()->is_super_admin && $return->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
