<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\CashMovement;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\GoodsReceiptItem;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseItem;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use App\Models\Purchases\SupplierPayment;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Purchases\TreasuryMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Recepción de mercadería desde el móvil: OC por recibir → recepción que suma
 * stock, avanza la OC y genera la compra (cuenta por pagar). Espeja la lógica
 * del web (Purchases\GoodsReceiptController).
 */
class PurchaseOrderController extends Controller
{
    use ResolvesCashSession;

    /**
     * Compra directa (contado): registra la compra, suma el stock al almacén y
     * paga desde la caja abierta (gasto), todo en un paso. Requiere caja abierta.
     */
    public function directPurchase(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'supplier_id'        => ['required', Rule::exists('suppliers', 'id')->where('company_id', $cid)],
            'warehouse_id'       => ['required', Rule::exists('warehouses', 'id')->where('company_id', $cid)],
            'invoice_number'     => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'method'             => ['nullable', 'string', 'max:30'],
            // Origen del pago: 'cash' (caja abierta) o 'treasury' (cuenta).
            'payment_source'      => ['nullable', 'in:cash,treasury'],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $cid)],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $cid)],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        $source = $data['payment_source'] ?? 'cash';
        $subtotal = collect($data['items'])
            ->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_cost']);

        // Pago desde caja: requiere sesión abierta.
        $session = null;
        if ($source === 'cash') {
            $session = $this->currentOpenSession();
            if (! $session) {
                return response()->json([
                    'message' => 'Necesitas tu caja abierta para registrar la compra.',
                    'code'    => 'cash_session_required',
                ], 422);
            }
        }

        // Pago desde tesorería: valida saldo de la cuenta.
        $account = null;
        if ($source === 'treasury') {
            $account = TreasuryAccount::find($data['treasury_account_id']);
            if ($account && $subtotal > (float) $account->balance) {
                return response()->json([
                    'message' => 'El monto supera el saldo disponible de la cuenta.',
                    'code'    => 'insufficient_balance',
                ], 422);
            }
        }

        try {
            $purchase = DB::transaction(function () use ($data, $cid, $session, $source, $account, $subtotal) {
                $method = $data['method'] ?? 'efectivo';

                $purchase = Purchase::create([
                    'company_id'     => $cid,
                    'supplier_id'    => $data['supplier_id'],
                    'code'           => 'COM-' . str_pad((string) (Purchase::withTrashed()->where('company_id', $cid)->count() + 1), 5, '0', STR_PAD_LEFT),
                    'invoice_number' => $data['invoice_number'] ?? null,
                    'purchase_date'  => now()->toDateString(),
                    'subtotal'       => $subtotal,
                    'tax'            => 0,
                    'total'          => $subtotal,
                    'paid_amount'    => 0,
                    'payment_status' => 'pending',
                    'notes'          => $data['notes'] ?? null,
                    'created_by'     => auth()->id(),
                ]);

                foreach ($data['items'] as $item) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['quantity'],
                        'unit_cost'   => $item['unit_cost'],
                        'subtotal'    => (float) $item['quantity'] * (float) $item['unit_cost'],
                    ]);

                    InventoryMovement::create([
                        'company_id'    => $cid,
                        'warehouse_id'  => $data['warehouse_id'],
                        'product_id'    => $item['product_id'],
                        'user_id'       => auth()->id(),
                        'type'          => 'in',
                        'quantity'      => $item['quantity'],
                        'unit_cost'     => $item['unit_cost'],
                        'reference'     => $purchase->code,
                        'notes'         => 'Compra directa ' . $purchase->code,
                        'movement_date' => now(),
                    ]);

                    Product::where('id', $item['product_id'])->increment('current_stock', $item['quantity']);
                }

                // Pago: concilia la compra y registra el gasto en el origen elegido.
                SupplierPayment::create([
                    'company_id'          => $cid,
                    'purchase_id'         => $purchase->id,
                    'treasury_account_id' => $source === 'treasury' ? $account->id : null,
                    'amount'              => $subtotal,
                    'payment_date'        => now()->toDateString(),
                    'method'              => $method,
                    'reference'           => $source === 'treasury' ? 'TESORERIA' : 'CAJA',
                    'notes'               => 'Compra directa (móvil)',
                    'user_id'             => auth()->id(),
                ]);
                $purchase->increment('paid_amount', $subtotal);
                $purchase->refresh()->recalcPaymentStatus();

                if ($source === 'treasury') {
                    // Gasto desde la cuenta de tesorería.
                    TreasuryMovement::create([
                        'company_id'          => $cid,
                        'treasury_account_id' => $account->id,
                        'user_id'             => auth()->id(),
                        'type'                => 'out',
                        'category'            => 'supplier_payment',
                        'amount'              => $subtotal,
                        'reference_type'      => Purchase::class,
                        'reference_id'        => $purchase->id,
                        'description'         => 'Compra ' . $purchase->code,
                        'movement_date'       => now(),
                    ]);
                    $account->decrement('balance', $subtotal);
                } else {
                    // Gasto desde la caja abierta.
                    CashMovement::create([
                        'company_id'               => $cid,
                        'cash_register_id'         => $session->cash_register_id,
                        'cash_register_session_id' => $session->id,
                        'user_id'                  => auth()->id(),
                        'type'                     => 'expense',
                        'category'                 => 'expense_supplier',
                        'amount'                   => $subtotal,
                        'method'                   => $method,
                        'reference_type'           => Purchase::class,
                        'reference_id'             => $purchase->id,
                        'description'              => 'Compra ' . $purchase->code,
                        'movement_date'            => now(),
                    ]);
                }

                return $purchase;
            });
        } catch (\Throwable $e) {
            Log::error('Error compra directa móvil', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo registrar la compra: ' . $e->getMessage()], 500);
        }

        $purchase->load('supplier:id,name');

        return response()->json(['data' => [
            'code'     => $purchase->code,
            'supplier' => $purchase->supplier?->name,
            'total'    => (float) $purchase->total,
        ]], 201);
    }

    /** Órdenes de compra por recibir (enviadas o parciales). */
    public function index()
    {
        $orders = PurchaseOrder::with('supplier:id,name')
            ->whereIn('status', ['sent', 'partial'])
            ->latest('order_date')->latest('id')
            ->get()
            ->map(fn (PurchaseOrder $po) => [
                'id'       => $po->id,
                'code'     => $po->code,
                'supplier' => $po->supplier?->name,
                'status'   => $po->status,
                'date'     => optional($po->order_date)->toDateString(),
                'total'    => (float) $po->total,
            ]);

        return response()->json(['data' => $orders]);
    }

    /**
     * Crea una orden de compra desde el móvil (estado 'sent' para que quede
     * lista para recibir). Requiere permiso purchase-orders.create.
     */
    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'supplier_id'        => ['required', Rule::exists('suppliers', 'id')->where('company_id', $cid)],
            'branch_id'          => ['nullable', Rule::exists('branches', 'id')->where('company_id', $cid)],
            'expected_date'      => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $cid)],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data, $cid) {
            $subtotal = collect($data['items'])
                ->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_cost']);

            $order = PurchaseOrder::create([
                'company_id'    => $cid,
                'supplier_id'   => $data['supplier_id'],
                'branch_id'     => $data['branch_id'] ?? null,
                'code'          => 'OC-' . str_pad((string) (PurchaseOrder::withTrashed()->where('company_id', $cid)->count() + 1), 5, '0', STR_PAD_LEFT),
                'status'        => 'sent',
                'order_date'    => now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'subtotal'      => $subtotal,
                'tax'           => 0,
                'total'         => $subtotal,
                'notes'         => $data['notes'] ?? null,
                'created_by'    => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'unit_cost'         => $item['unit_cost'],
                    'subtotal'          => (float) $item['quantity'] * (float) $item['unit_cost'],
                    'received_quantity' => 0,
                ]);
            }

            return $order;
        });

        $order->load('supplier:id,name');

        return response()->json(['data' => [
            'id'       => $order->id,
            'code'     => $order->code,
            'supplier' => $order->supplier?->name,
            'status'   => $order->status,
            'total'    => (float) $order->total,
        ]], 201);
    }

    /** Detalle de una OC con items (pendiente por recibir) + almacenes. */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items.product:id,name,unit', 'supplier:id,name');

        $warehouses = Warehouse::where('company_id', $purchaseOrder->company_id)
            ->where('active', true)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Warehouse $w) => ['id' => $w->id, 'name' => $w->name])->values();

        return response()->json(['data' => [
            'id'       => $purchaseOrder->id,
            'code'     => $purchaseOrder->code,
            'supplier' => $purchaseOrder->supplier?->name,
            'status'   => $purchaseOrder->status,
            'date'     => optional($purchaseOrder->order_date)->toDateString(),
            'items'    => $purchaseOrder->items->map(fn (PurchaseOrderItem $it) => [
                'po_item_id' => $it->id,
                'product'    => $it->product?->name,
                'unit'       => $it->product?->unit,
                'ordered'    => (float) $it->quantity,
                'received'   => (float) $it->received_quantity,
                'pending'    => (float) $it->quantity - (float) $it->received_quantity,
                'unit_cost'  => (float) $it->unit_cost,
            ])->values(),
            'warehouses' => $warehouses,
        ]]);
    }

    /** Recibe mercadería contra la OC: suma stock, avanza la OC y genera la compra (CxP). */
    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'], true)) {
            return response()->json(['message' => 'Esta orden ya fue recibida o está anulada.'], 422);
        }

        $validated = $request->validate([
            'warehouse_id'       => 'required|exists:warehouses,id',
            'invoice_number'     => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity'   => 'nullable|numeric|min:0',
        ]);

        if (! collect($validated['items'])->contains(fn ($i) => (float) ($i['quantity'] ?? 0) > 0)) {
            return response()->json(['message' => 'Recibe al menos un producto con cantidad mayor a 0.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($purchaseOrder, $validated) {
                $companyId = $purchaseOrder->company_id;

                $receipt = GoodsReceipt::create([
                    'company_id'        => $companyId,
                    'purchase_order_id' => $purchaseOrder->id,
                    'warehouse_id'      => $validated['warehouse_id'],
                    'code'              => 'REC-' . str_pad((string) (GoodsReceipt::withTrashed()->where('company_id', $companyId)->count() + 1), 5, '0', STR_PAD_LEFT),
                    'receipt_date'      => now()->toDateString(),
                    'notes'             => $validated['notes'] ?? null,
                    'received_by'       => auth()->id(),
                ]);

                $purchaseItems = [];

                foreach ($validated['items'] as $row) {
                    $qty = (float) ($row['quantity'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $poItem = PurchaseOrderItem::findOrFail($row['po_item_id']);
                    // No recibir más de lo pendiente.
                    $pending = (float) $poItem->quantity - (float) $poItem->received_quantity;
                    if ($qty > $pending) {
                        $qty = $pending;
                    }
                    if ($qty <= 0) {
                        continue;
                    }

                    GoodsReceiptItem::create([
                        'goods_receipt_id'       => $receipt->id,
                        'purchase_order_item_id' => $poItem->id,
                        'product_id'             => $poItem->product_id,
                        'quantity'               => $qty,
                        'unit_cost'              => $poItem->unit_cost,
                    ]);

                    InventoryMovement::create([
                        'company_id'    => $companyId,
                        'warehouse_id'  => $validated['warehouse_id'],
                        'branch_id'     => $purchaseOrder->branch_id,
                        'product_id'    => $poItem->product_id,
                        'user_id'       => auth()->id(),
                        'type'          => 'in',
                        'quantity'      => $qty,
                        'unit_cost'     => $poItem->unit_cost,
                        'reference'     => $receipt->code,
                        'notes'         => 'Recepción OC ' . $purchaseOrder->code,
                        'movement_date' => now(),
                    ]);

                    Product::where('id', $poItem->product_id)->increment('current_stock', $qty);
                    $poItem->increment('received_quantity', $qty);

                    $purchaseItems[] = [
                        'product_id' => $poItem->product_id,
                        'quantity'   => $qty,
                        'unit_cost'  => (float) $poItem->unit_cost,
                    ];
                }

                $purchaseOrder->refreshReceiptStatus();

                $purchase = null;
                if (! empty($purchaseItems)) {
                    $subtotal = collect($purchaseItems)->sum(fn ($i) => $i['quantity'] * $i['unit_cost']);

                    $purchase = Purchase::create([
                        'company_id'        => $companyId,
                        'supplier_id'       => $purchaseOrder->supplier_id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'code'              => 'COM-' . str_pad((string) (Purchase::withTrashed()->where('company_id', $companyId)->count() + 1), 5, '0', STR_PAD_LEFT),
                        'invoice_number'    => $validated['invoice_number'] ?? null,
                        'purchase_date'     => now()->toDateString(),
                        'subtotal'          => $subtotal,
                        'tax'               => 0,
                        'total'             => $subtotal,
                        'paid_amount'       => 0,
                        'payment_status'    => 'pending',
                        'notes'             => $validated['notes'] ?? null,
                        'created_by'        => auth()->id(),
                    ]);

                    foreach ($purchaseItems as $pi) {
                        PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'product_id'  => $pi['product_id'],
                            'quantity'    => $pi['quantity'],
                            'unit_cost'   => $pi['unit_cost'],
                            'subtotal'    => $pi['quantity'] * $pi['unit_cost'],
                        ]);
                    }

                    $receipt->update(['purchase_id' => $purchase->id]);
                }

                return ['receipt' => $receipt, 'purchase' => $purchase];
            });
        } catch (\Throwable $e) {
            Log::error('Error recepción móvil', ['po' => $purchaseOrder->id, 'msg' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo registrar la recepción: ' . $e->getMessage()], 500);
        }

        $receipt  = $result['receipt'];
        $purchase = $result['purchase'];

        return response()->json(['data' => [
            'receipt_code'  => $receipt->code,
            'purchase_code' => $purchase?->code,
            'status'        => $purchaseOrder->fresh()->status,
            'message'       => $purchase
                ? "Recepción {$receipt->code} · compra {$purchase->code} (cuenta por pagar)"
                : "Recepción {$receipt->code} registrada",
        ]]);
    }
}
