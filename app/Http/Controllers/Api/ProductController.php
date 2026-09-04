<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductPhoto;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** Catálogos para el alta rápida de producto: categorías, marcas, almacenes. */
    public function formData(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $opts = fn ($q) => $q->where('company_id', $cid)->where('active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values();

        return response()->json(['data' => [
            'categories' => $opts(ProductCategory::query()),
            'brands'     => $opts(ProductBrand::query()),
            'warehouses' => $opts(Warehouse::query()),
        ]]);
    }

    /**
     * Alta rápida de producto desde el móvil: nombre + precio (mínimo), con
     * costo, unidad, código de barras, categoría/marca y stock inicial opcional.
     * El SKU se autogenera por empresa. Requiere permiso products.create.
     */
    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
            'cost'          => ['nullable', 'numeric', 'min:0'],
            'unit'          => ['nullable', 'string', 'max:50'],
            'barcode'       => ['nullable', 'string', 'max:100'],
            'category_id'   => ['nullable', Rule::exists('product_categories', 'id')->where('company_id', $cid)],
            'brand_id'      => ['nullable', Rule::exists('product_brands', 'id')->where('company_id', $cid)],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
            'warehouse_id'  => ['nullable', Rule::exists('warehouses', 'id')->where('company_id', $cid)],
            'photo'         => ['nullable', 'image', 'max:5120'],
        ]);

        $initial = (float) ($data['initial_stock'] ?? 0);
        if ($initial > 0 && empty($data['warehouse_id'])) {
            return response()->json(['message' => 'Selecciona un almacén para el stock inicial.'], 422);
        }

        $product = DB::transaction(function () use ($data, $cid, $initial) {
            $product = Product::create([
                'company_id'    => $cid,
                'name'          => trim($data['name']),
                'sku'           => $this->generateProductSku($cid),
                'price'         => (float) $data['price'],
                'cost'          => (float) ($data['cost'] ?? 0),
                'unit'          => $data['unit'] ?: 'unidad',
                'barcode'       => $data['barcode'] ?? null,
                'category_id'   => $data['category_id'] ?? null,
                'brand_id'      => $data['brand_id'] ?? null,
                'current_stock' => 0,
                'active'        => true,
            ]);

            if ($initial > 0) {
                InventoryMovement::create([
                    'company_id'    => $cid,
                    'warehouse_id'  => $data['warehouse_id'],
                    'product_id'    => $product->id,
                    'user_id'       => auth()->id(),
                    'type'          => 'in',
                    'quantity'      => $initial,
                    'unit_cost'     => $product->cost,
                    'reference'     => 'ALTA-MOVIL',
                    'notes'         => 'Stock inicial (alta desde móvil)',
                    'movement_date' => now(),
                ]);
                Product::where('id', $product->id)->increment('current_stock', $initial);
            }

            return $product->fresh();
        });

        // Foto opcional: se guarda en el disco público y se marca como principal.
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store("company/{$cid}/products/{$product->id}", 'public');
            ProductPhoto::create([
                'product_id' => $product->id,
                'company_id' => $cid,
                'file_path'  => $path,
                'file_name'  => $file->getClientOriginalName(),
                'is_main'    => true,
                'sort_order' => 0,
            ]);
            $product->load('photos');
        }

        return response()->json(['data' => $this->payload($product)], 201);
    }

    /** SKU interno correlativo y único por empresa: {prefijo}-{correlativo}. */
    private function generateProductSku(int $companyId): string
    {
        $prefix = config('inventory.code_prefix', 'PRD');
        $seq    = Product::withTrashed()->where('company_id', $companyId)->count() + 1;

        do {
            $sku = $prefix . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            $seq++;
        } while (Product::withTrashed()->where('company_id', $companyId)->where('sku', $sku)->exists());

        return $sku;
    }
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
        $product->load(['category:id,name', 'brand:id,name', 'origin:id,name', 'motoModels', 'photos']);

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
                'photos'            => $product->photos->map(fn ($ph) => $ph->url)->values(),
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
            ->with('photos')
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
            'image_url'     => $this->mainPhotoUrl($p),
        ];
    }

    /**
     * URL de la foto principal del producto (o la primera). Usa la relación ya
     * cargada si está disponible para evitar consultas N+1 en el listado.
     */
    private function mainPhotoUrl(Product $p): ?string
    {
        $photo = $p->relationLoaded('photos')
            ? ($p->photos->firstWhere('is_main', true) ?? $p->photos->first())
            : $p->mainPhoto();

        return $photo?->url;
    }
}
