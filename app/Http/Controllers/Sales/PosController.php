<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Inventory\ProductCategory;
use App\Models\Motos\MotoModel;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    use HandlesSaleCreation;

    public function index()
    {
        $user    = auth()->user();
        $cid     = $user->getCurrentCompany()?->id;
        $session = $this->currentOpenSession();

        $products = Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->with(['category', 'brand', 'photos', 'motoModels.brand'])
            ->orderBy('name')
            ->get();

        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'id_number']);

        $categories = ProductCategory::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Modelos con al menos un producto asociado (para el filtro)
        $motoModels = MotoModel::with('brand')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereHas('products')
            ->orderBy('name')
            ->get();

        // Stock por almacén: el POS muestra el stock del almacén de la sucursal de la caja
        $warehouseId = $session?->cashRegister?->branch?->warehouse_id;
        $whMatrix    = $this->warehouseStockMatrix($cid);   // [wid => [pid => qty]]
        $activeStock = $whMatrix[$warehouseId] ?? [];

        // Almacenes de la empresa (para el modal "stock en otros almacenes")
        $warehouses = Warehouse::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);

        // Matriz para el modal: pid => [ {warehouse_id, qty} ]
        $productWhStock = [];
        foreach ($warehouses as $w) {
            foreach (($whMatrix[$w->id] ?? []) as $pid => $qty) {
                if ($qty == 0) continue;
                $productWhStock[$pid][] = ['wid' => $w->id, 'qty' => (float) $qty];
            }
        }

        // Fidelización: ¿el módulo está activo para esta empresa?
        $loyaltyEnabled = $cid
            ? (bool) optional(\App\Models\Loyalty\LoyaltySetting::where('company_id', $cid)->first())->enabled
            : false;

        return view('sales.pos.index', compact(
            'session', 'products', 'clients', 'categories', 'motoModels',
            'warehouseId', 'activeStock', 'warehouses', 'productWhStock', 'loyaltyEnabled'
        ));
    }

    /** Stock por almacén (calculado desde el Kardex) para todos los productos: [warehouse_id => [product_id => qty]]. */
    private function warehouseStockMatrix(?int $cid): array
    {
        $base = InventoryMovement::query()->when($cid, fn ($q) => $q->where('company_id', $cid));

        $inDirect = (clone $base)->whereIn('type', ['in', 'adjustment'])
            ->selectRaw('warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('warehouse_id', 'product_id')->get();
        $inTransfer = (clone $base)->where('type', 'transfer')->whereNotNull('destination_warehouse_id')
            ->selectRaw('destination_warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('destination_warehouse_id', 'product_id')->get();
        $outs = (clone $base)->whereIn('type', ['out', 'transfer'])
            ->selectRaw('warehouse_id wid, product_id pid, SUM(quantity) q')->groupBy('warehouse_id', 'product_id')->get();

        $map = [];
        $apply = function ($rows, $sign) use (&$map) {
            foreach ($rows as $r) {
                if (!$r->wid) continue;
                $map[$r->wid][$r->pid] = ($map[$r->wid][$r->pid] ?? 0) + $sign * (float) $r->q;
            }
        };
        $apply($inDirect, 1);
        $apply($inTransfer, 1);
        $apply($outs, -1);

        return $map;
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->getCurrentCompany()?->id;
        $session   = $this->currentOpenSession();

        if (!$session) {
            return back()->withErrors(['error' => 'Debes abrir tu caja antes de vender en el POS.']);
        }

        $validated = $request->validate([
            'client_id'          => 'nullable|exists:clients,id',
            'sale_type'          => 'required|in:cash,credit',
            'discount_pct'       => 'nullable|integer|min:0|max:100',
            'interest'           => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'       => 'required_without:items.*.product_id|nullable|string|max:255',
            'items.*.direct'     => 'nullable|boolean',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            // Crédito rápido
            'installments'             => 'nullable|array',
            'installments.*.due_date'  => 'required_with:installments|date',
            'installments.*.amount'    => 'required_with:installments|numeric|min:0.01',
            'down_payment'             => 'nullable|numeric|min:0',
        ]);

        // Crédito: exige un cliente real (no "Cliente general")
        if ($validated['sale_type'] === 'credit' && empty($validated['client_id'])) {
            return back()->withInput()->withErrors(['client_id' => 'Selecciona un cliente registrado para una venta a crédito (no "Cliente general").']);
        }

        // Descuento en % aplicado SOLO a la ganancia (precio − costo). Se calcula en el servidor.
        $pct = (int) ($validated['discount_pct'] ?? 0);
        $discount = 0.0;
        if ($pct > 0) {
            $costs = Product::whereIn('id', collect($validated['items'])->pluck('product_id')->filter())->pluck('cost', 'id');
            foreach ($validated['items'] as $it) {
                // Ítems de venta rápida sin product_id → costo 0 (toda la línea es ganancia).
                $cost   = (float) ($costs[$it['product_id'] ?? 0] ?? 0);
                $profit = ((float) $it['unit_price'] - $cost) * (float) $it['quantity'];
                if ($profit > 0) $discount += $profit * $pct / 100;
            }
            $discount = round($discount, 2);
        }

        try {
            $sale = $this->confirmSale([
                'company_id'   => $companyId,
                'branch_id'    => $session->cashRegister?->branch_id,
                'client_id'    => $validated['client_id'] ?? null,
                'sale_type'    => $validated['sale_type'],
                'sale_date'    => now()->toDateString(),
                'discount'     => $discount,
                'tax'          => 0,
                'interest'     => $validated['interest'] ?? 0,
                'notes'        => 'Venta POS',
                'items'        => $validated['items'],
                'installments' => $validated['installments'] ?? [],
                'down_payment' => $validated['down_payment'] ?? 0,
            ], $session);

            // Volver al POS para seguir vendiendo; el recibo térmico se imprime solo
            return redirect()->route('pos.index')
                ->with('success', 'Venta registrada: ' . $sale->code)
                ->with('print_receipt_id', $sale->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Error en venta POS', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }
}
