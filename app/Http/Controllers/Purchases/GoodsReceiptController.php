<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\GoodsReceiptItem;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseItem;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $warehouses = Warehouse::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'company_id']);

        // Almacenes seleccionados (multi-selección)
        $selected = array_values(array_filter(array_map('intval', (array) $request->input('warehouses', []))));

        $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'receivedBy'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when(!empty($selected), fn ($q) => $q->whereIn('warehouse_id', $selected))
            ->latest();

        // Conteo de recepciones por almacén (para mostrar en los tabs)
        $counts = GoodsReceipt::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->selectRaw('warehouse_id, COUNT(*) as total')
            ->groupBy('warehouse_id')
            ->pluck('total', 'warehouse_id');

        return view('purchases.receipts.index', [
            'receipts'   => $query->paginate(15)->withQueryString(),
            'warehouses' => $warehouses,
            'selected'   => $selected,
            'counts'     => $counts,
            'totalCount' => $counts->sum(),
        ]);
    }

    /** Formulario de recepción para una OC específica */
    public function create(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->withErrors(['error' => 'Esta orden ya fue recibida o está anulada.']);
        }

        $purchaseOrder->load('items.product');
        $warehouses = Warehouse::where('company_id', $purchaseOrder->company_id)->where('active', true)->orderBy('name')->get();

        return view('purchases.receipts.create', ['order' => $purchaseOrder, 'warehouses' => $warehouses]);
    }

    public function store(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        $validated = request()->validate([
            'warehouse_id'        => 'required|exists:warehouses,id',
            'receipt_date'        => 'required|date',
            'invoice_number'      => 'nullable|string|max:100',
            'tax'                 => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.po_item_id'  => 'required|exists:purchase_order_items,id',
            'items.*.quantity'    => 'nullable|integer|min:0',
        ]);

        // Al menos un item con cantidad > 0
        $hasQty = collect($validated['items'])->contains(fn ($i) => (float) ($i['quantity'] ?? 0) > 0);
        if (!$hasQty) {
            return back()->withInput()->withErrors(['error' => 'Debes recibir al menos un producto con cantidad mayor a cero.']);
        }

        try {
            [$receipt, $purchase] = DB::transaction(function () use ($purchaseOrder, $validated) {
                $receipt = GoodsReceipt::create([
                    'company_id'        => $purchaseOrder->company_id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'warehouse_id'      => $validated['warehouse_id'],
                    'code'              => $this->nextCode($purchaseOrder->company_id),
                    'receipt_date'      => $validated['receipt_date'],
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

                    // No permitir recibir más de lo pendiente
                    $pending = (float) $poItem->quantity - (float) $poItem->received_quantity;
                    if ($qty > $pending) {
                        $qty = $pending;
                    }
                    if ($qty <= 0) {
                        continue;
                    }

                    // 1. Item de recepción
                    GoodsReceiptItem::create([
                        'goods_receipt_id'       => $receipt->id,
                        'purchase_order_item_id' => $poItem->id,
                        'product_id'             => $poItem->product_id,
                        'quantity'               => $qty,
                        'unit_cost'              => $poItem->unit_cost,
                    ]);

                    // 2. Movimiento de inventario (Kardex)
                    InventoryMovement::create([
                        'company_id'    => $purchaseOrder->company_id,
                        'warehouse_id'  => $validated['warehouse_id'],
                        'branch_id'     => $purchaseOrder->branch_id,
                        'product_id'    => $poItem->product_id,
                        'user_id'       => auth()->id(),
                        'type'          => 'in',
                        'quantity'      => $qty,
                        'unit_cost'     => $poItem->unit_cost,
                        'reference'     => $receipt->code,
                        'notes'         => 'Recepción OC ' . $purchaseOrder->code,
                        'movement_date' => $validated['receipt_date'],
                    ]);

                    // 3. Actualizar stock del producto
                    Product::where('id', $poItem->product_id)->increment('current_stock', $qty);

                    // 4. Actualizar cantidad recibida en el item de la OC
                    $poItem->increment('received_quantity', $qty);

                    // Acumular para la compra (factura)
                    $purchaseItems[] = [
                        'product_id' => $poItem->product_id,
                        'quantity'   => $qty,
                        'unit_cost'  => (float) $poItem->unit_cost,
                    ];
                }

                // 5. Refrescar estado de la OC
                $purchaseOrder->refreshReceiptStatus();

                // 6. Registrar la COMPRA (factura) directamente con lo recibido → genera la cuenta por pagar
                $purchase = null;
                if (!empty($purchaseItems)) {
                    $subtotal = collect($purchaseItems)->sum(fn ($i) => $i['quantity'] * $i['unit_cost']);
                    $tax      = (float) ($validated['tax'] ?? 0);

                    $purchase = Purchase::create([
                        'company_id'        => $purchaseOrder->company_id,
                        'supplier_id'       => $purchaseOrder->supplier_id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'code'              => $this->nextPurchaseCode($purchaseOrder->company_id),
                        'invoice_number'    => $validated['invoice_number'] ?? null,
                        'purchase_date'     => $validated['receipt_date'],
                        'subtotal'          => $subtotal,
                        'tax'               => $tax,
                        'total'             => $subtotal + $tax,
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

                    // Enlazar la recepción con la compra generada
                    $receipt->update(['purchase_id' => $purchase->id]);
                }

                return [$receipt, $purchase];
            });

            if ($purchase) {
                return redirect()->route('purchases.show', $purchase)
                    ->with('success', 'Recepción y compra registradas. Se generó la cuenta por pagar (' . $purchase->code . ').');
            }

            return redirect()->route('goods-receipts.show', $receipt)->with('success', 'Recepción registrada. Stock actualizado.');
        } catch (\Throwable $e) {
            Log::error('Error al registrar recepción', ['po' => $purchaseOrder->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $this->authorizeReceipt($goodsReceipt);
        $goodsReceipt->load(['purchaseOrder.supplier', 'warehouse', 'receivedBy', 'items.product']);
        return view('purchases.receipts.show', ['receipt' => $goodsReceipt]);
    }

    private function nextCode(int $companyId): string
    {
        $count = GoodsReceipt::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'REC-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function nextPurchaseCode(int $companyId): string
    {
        $count = Purchase::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'COM-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeOrder(PurchaseOrder $order): void
    {
        if (!auth()->user()->is_super_admin && $order->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function authorizeReceipt(GoodsReceipt $receipt): void
    {
        if (!auth()->user()->is_super_admin && $receipt->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
