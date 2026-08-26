<?php

namespace App\Http\Controllers\Api;

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

/**
 * Recepción de mercadería desde el móvil: OC por recibir → recepción que suma
 * stock, avanza la OC y genera la compra (cuenta por pagar). Espeja la lógica
 * del web (Purchases\GoodsReceiptController).
 */
class PurchaseOrderController extends Controller
{
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
