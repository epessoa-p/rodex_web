<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use App\Models\Purchases\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = PurchaseOrder::with(['supplier', 'branch'])->withCount('items')->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return view('purchases.orders.index', ['orders' => $query->paginate(15)]);
    }

    public function create()
    {
        return view('purchases.orders.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateOrder();

        try {
            $order = DB::transaction(function () use ($validated, $companyId) {
                $totals = $this->calcTotals($validated['items'], $validated['tax'] ?? 0);

                $order = PurchaseOrder::create([
                    'company_id'    => $companyId,
                    'supplier_id'   => $validated['supplier_id'],
                    'branch_id'     => $validated['branch_id'] ?? null,
                    'code'          => $this->nextCode($companyId),
                    'status'        => $validated['status'] ?? 'draft',
                    'order_date'    => $validated['order_date'],
                    'expected_date' => $validated['expected_date'] ?? null,
                    'subtotal'      => $totals['subtotal'],
                    'tax'           => $totals['tax'],
                    'total'         => $totals['total'],
                    'notes'         => $validated['notes'] ?? null,
                    'created_by'    => auth()->id(),
                ]);

                $this->syncItems($order, $validated['items']);
                return $order;
            });

            return redirect()->route('purchase-orders.show', $order)->with('success', 'Orden de compra creada.');
        } catch (\Throwable $e) {
            Log::error('Error al crear OC', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);
        $purchaseOrder->load(['supplier', 'branch', 'createdBy', 'items.product', 'receipts.warehouse', 'purchases']);
        return view('purchases.orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->withErrors(['error' => 'No se puede editar una orden recibida o anulada.']);
        }

        $purchaseOrder->load('items.product');
        return view('purchases.orders.edit', array_merge($this->formData(), ['order' => $purchaseOrder]));
    }

    public function update(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->withErrors(['error' => 'No se puede editar una orden recibida o anulada.']);
        }

        $validated = $this->validateOrder();

        try {
            DB::transaction(function () use ($purchaseOrder, $validated) {
                $totals = $this->calcTotals($validated['items'], $validated['tax'] ?? 0);

                $purchaseOrder->update([
                    'supplier_id'   => $validated['supplier_id'],
                    'branch_id'     => $validated['branch_id'] ?? null,
                    'status'        => $validated['status'] ?? $purchaseOrder->status,
                    'order_date'    => $validated['order_date'],
                    'expected_date' => $validated['expected_date'] ?? null,
                    'subtotal'      => $totals['subtotal'],
                    'tax'           => $totals['tax'],
                    'total'         => $totals['total'],
                    'notes'         => $validated['notes'] ?? null,
                ]);

                // Recrear items (la OC aún no tiene recepciones si está draft/sent)
                $purchaseOrder->items()->delete();
                $this->syncItems($purchaseOrder, $validated['items']);
            });

            return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Orden actualizada.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar OC', ['id' => $purchaseOrder->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if ($purchaseOrder->receipts()->exists()) {
            return back()->withErrors(['error' => 'No se puede anular: la orden ya tiene recepciones.']);
        }

        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Orden anulada.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOrder($purchaseOrder);

        if ($purchaseOrder->receipts()->exists() || $purchaseOrder->purchases()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: la orden tiene recepciones o facturas.']);
        }

        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')->with('success', 'Orden eliminada.');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function validateOrder(): array
    {
        return request()->validate([
            'supplier_id'      => 'required|exists:suppliers,id',
            'branch_id'        => 'nullable|exists:branches,id',
            'status'           => 'nullable|in:draft,sent',
            'order_date'       => 'required|date',
            'expected_date'    => 'nullable|date',
            'tax'              => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ]);
    }

    private function calcTotals(array $items, $tax): array
    {
        $subtotal = collect($items)->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_cost']);
        $tax      = (float) $tax;
        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax];
    }

    private function syncItems(PurchaseOrder $order, array $items): void
    {
        foreach ($items as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id'        => $item['product_id'],
                'quantity'          => $item['quantity'],
                'unit_cost'         => $item['unit_cost'],
                'subtotal'          => (float) $item['quantity'] * (float) $item['unit_cost'],
                'received_quantity' => 0,
            ]);
        }
    }

    private function nextCode(int $companyId): string
    {
        $count = PurchaseOrder::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'OC-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeOrder(PurchaseOrder $order): void
    {
        if (!auth()->user()->is_super_admin && $order->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $suppliers = Supplier::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $branches  = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $products  = Product::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return compact('suppliers', 'branches', 'products');
    }
}
