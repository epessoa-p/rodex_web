<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\Product;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseItem;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\Supplier;
use App\Models\Purchases\TreasuryAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    use ResolvesCashSession;

    public function index()
    {
        $user  = auth()->user();
        $query = Purchase::with(['supplier'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        if ($status = request('payment_status')) {
            $query->where('payment_status', $status);
        }

        return view('purchases.invoices.index', ['purchases' => $query->paginate(15)]);
    }

    public function create()
    {
        $fromOrder          = null;
        $uninvoicedReceipts = collect();

        if ($orderId = request('order_id')) {
            $fromOrder = PurchaseOrder::with([
                'receipts.items.product',
                'receipts.warehouse',
            ])->find($orderId);

            if ($fromOrder) {
                $this->authorizeCompany($fromOrder->company_id);

                // Solo recepciones que NO han sido facturadas aún
                $uninvoicedReceipts = $fromOrder->receipts
                    ->whereNull('purchase_id')
                    ->values();
            }
        }

        return view('purchases.invoices.create', array_merge(
            $this->formData(),
            [
                'fromOrder'          => $fromOrder,
                'uninvoicedReceipts' => $uninvoicedReceipts,
            ]
        ));
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = request()->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'purchase_order_id'  => 'nullable|exists:purchase_orders,id',
            'receipt_ids'        => 'nullable|array',
            'receipt_ids.*'      => 'exists:goods_receipts,id',
            'invoice_number'     => 'nullable|string|max:100',
            'purchase_date'      => 'required|date',
            'tax'                => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
        ]);

        try {
            $purchase = DB::transaction(function () use ($validated, $companyId) {
                $subtotal = collect($validated['items'])->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_cost']);
                $tax      = (float) ($validated['tax'] ?? 0);

                $purchase = Purchase::create([
                    'company_id'        => $companyId,
                    'supplier_id'       => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                    'code'              => $this->nextCode($companyId),
                    'invoice_number'    => $validated['invoice_number'] ?? null,
                    'purchase_date'     => $validated['purchase_date'],
                    'subtotal'          => $subtotal,
                    'tax'               => $tax,
                    'total'             => $subtotal + $tax,
                    'paid_amount'       => 0,
                    'payment_status'    => 'pending',
                    'notes'             => $validated['notes'] ?? null,
                    'created_by'        => auth()->id(),
                ]);

                // Vincular recepciones seleccionadas → marcarlas como facturadas
                if (!empty($validated['receipt_ids'])) {
                    GoodsReceipt::whereIn('id', $validated['receipt_ids'])
                        ->whereNull('purchase_id')
                        ->update(['purchase_id' => $purchase->id]);
                }

                foreach ($validated['items'] as $item) {
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id'  => $item['product_id'],
                        'quantity'    => $item['quantity'],
                        'unit_cost'   => $item['unit_cost'],
                        'subtotal'    => (float) $item['quantity'] * (float) $item['unit_cost'],
                    ]);
                }

                return $purchase;
            });

            return redirect()->route('purchases.show', $purchase)->with('success', 'Compra registrada. Generó cuenta por pagar.');
        } catch (\Throwable $e) {
            Log::error('Error al crear compra', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Purchase $purchase)
    {
        $this->authorizePurchase($purchase);
        $purchase->load(['supplier', 'purchaseOrder', 'createdBy', 'items.product', 'payments.treasuryAccount', 'payments.user']);

        // Para el modal de registro de pago (caja del personal / cuentas de tesorería)
        $accounts = TreasuryAccount::where('company_id', $purchase->company_id)
            ->where('active', true)->orderBy('name')->get();
        $cashSession = $this->currentOpenSession();

        return view('purchases.invoices.show', compact('purchase', 'accounts', 'cashSession'));
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorizePurchase($purchase);

        if ($purchase->payments()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar: la compra tiene pagos registrados.']);
        }

        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Compra eliminada.');
    }

    private function nextCode(int $companyId): string
    {
        $count = Purchase::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'COM-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizePurchase(Purchase $purchase): void
    {
        $this->authorizeCompany($purchase->company_id);
    }

    private function authorizeCompany(int $companyId): void
    {
        if (!auth()->user()->is_super_admin && $companyId !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $suppliers = Supplier::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $products  = Product::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        // Solo OCs que tengan al menos una recepción pendiente de facturar
        $orders = PurchaseOrder::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['sent', 'partial', 'received'])
            ->whereHas('receipts', fn ($q) => $q->whereNull('purchase_id'))
            ->latest()
            ->get();

        return compact('suppliers', 'products', 'orders');
    }
}
