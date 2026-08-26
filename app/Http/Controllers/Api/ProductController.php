<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Ajuste rápido de stock desde el móvil: entrada (in), salida (out) o fijar
     * a una cantidad (set) en un almacén, con motivo. Crea el movimiento de
     * inventario y actualiza el stock. Requiere permiso products.edit.
     */
    public function adjustStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $product->company_id)],
            'type'         => ['required', 'in:in,out,set'],
            'quantity'     => ['required', 'numeric', 'min:0'],
            'reason'       => ['nullable', 'string', 'max:255'],
        ]);

        $wid = (int) $data['warehouse_id'];
        $qty = (float) $data['quantity'];

        if (in_array($data['type'], ['in', 'out'], true) && $qty <= 0) {
            return response()->json(['message' => 'La cantidad debe ser mayor a 0.'], 422);
        }

        try {
            DB::transaction(function () use ($product, $wid, $qty, $data) {
                $current = (float) $product->stockInWarehouse($wid);
                $delta = match ($data['type']) {
                    'in'  => $qty,
                    'out' => -$qty,
                    'set' => $qty - $current,
                };

                if (abs($delta) < 0.0001) {
                    return;
                }

                InventoryMovement::create([
                    'company_id'        => $product->company_id,
                    'warehouse_id'      => $wid,
                    'product_id'        => $product->id,
                    'user_id'           => auth()->id(),
                    'type'              => $delta > 0 ? 'in' : 'out',
                    'quantity'          => abs($delta),
                    'unit_cost'         => $product->cost,
                    'reference'         => 'AJUSTE-MOVIL',
                    'notes'             => $data['reason'] ?? 'Ajuste desde el móvil',
                    'adjustment_reason' => $data['type'] === 'set' ? ('Ajuste a ' . $qty) : ($data['reason'] ?? null),
                    'movement_date'     => now(),
                ]);

                Product::where('id', $product->id)->increment('current_stock', $delta);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error ajuste stock móvil', ['product' => $product->id, 'msg' => $e->getMessage()]);
            return response()->json(['message' => 'No se pudo ajustar el stock.'], 500);
        }

        $product->refresh();

        return response()->json(['data' => [
            'warehouse_stock' => (float) $product->stockInWarehouse($wid),
            'current_stock'   => (float) $product->current_stock,
        ]]);
    }
    /**
     * Ficha de un producto: además del resumen, incluye categoría, marca,
     * origen, modelos compatibles y stock por almacén. Scoped por global scope.
     */
    public function show(Product $product)
    {
        $product->load(['category:id,name', 'brand:id,name', 'origin:id,name', 'motoModels']);

        $stockByWarehouse = Warehouse::where('company_id', $product->company_id)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (Warehouse $w) => [
                'id'        => $w->id,
                'warehouse' => $w->name,
                'qty'       => (float) $product->stockInWarehouse($w->id),
            ])->values();

        return response()->json([
            'data' => $this->payload($product) + [
                'category'          => $product->category?->name,
                'brand'             => $product->brand?->name,
                'origin'            => $product->origin?->name,
                'compatible_models' => $product->motoModels->pluck('display_name')->values(),
                'stock_by_warehouse' => $stockByWarehouse,
            ],
        ]);
    }

    /**
     * Listado/búsqueda de productos de la empresa activa (con stock).
     * El aislamiento por empresa lo aplica el global scope automáticamente.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->where('active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('barcode', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Product $p) => $this->payload($p));

        return response()->json(['data' => $products]);
    }

    private function payload(Product $p): array
    {
        return [
            'id'            => $p->id,
            'name'          => $p->name,
            'sku'           => $p->sku,
            'code'          => $p->code,
            'barcode'       => $p->barcode,
            'unit'          => $p->unit,
            'price'         => (float) $p->price,
            'current_stock' => (float) $p->current_stock,
        ];
    }
}
