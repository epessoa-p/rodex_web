<?php

namespace App\Http\Controllers\Inventory;

use App\Services\Inventory\KardexMath;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ParsesProductImport;
use App\Models\Company;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductOrigin;
use App\Models\Inventory\ProductUnit;
use App\Models\Inventory\ProductPhoto;
use App\Services\Inventory\PhotoThumbnailService;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    use ParsesProductImport;

    public function index()
    {
        $user  = auth()->user();
        $cid   = $user->getCurrentCompany()?->id;
        $query = Product::with(['company', 'category', 'brand', 'origin', 'photos'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $cid);
        }

        // Orígenes de la empresa para el selector editable del listado.
        $origins = ProductOrigin::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        return view('inventory.products.index', [
            'products'    => $query->paginate(15),
            'origins'     => $origins,
            'limitStatus' => $this->planLimitStatus($cid, 'products'),
        ]);
    }

    /**
     * Etiquetas imprimibles de productos (QR o código de barras) para pegar en
     * el estante y escanear desde el móvil. Los códigos se generan en el
     * navegador, así que no requiere ninguna librería extra en el servidor.
     */
    public function labels()
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $query = Product::query()->where('active', true)->orderBy('name');

        if (!$user->is_super_admin) {
            $query->where('company_id', $cid);
        }

        return view('inventory.products.labels', [
            'products' => $query->get(['id', 'name', 'sku', 'code', 'barcode', 'price']),
        ]);
    }

    public function create()
    {
        if ($this->planLimitReached(auth()->user()->getCurrentCompany()?->id, 'products')) {
            return redirect()->route('products.index')
                ->withErrors(['error' => $this->planLimitMessage('productos')]);
        }

        return view('inventory.products.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id')
            : $user->getCurrentCompany()?->id;

        if ($this->planLimitReached($companyId, 'products')) {
            return back()->withInput()->withErrors(['error' => $this->planLimitMessage('productos')]);
        }

        $validated = request()->validate([
            'company_id'         => ['nullable', 'exists:companies,id'],
            'name'               => 'required|string|max:255',
            'sku'                => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'code'               => 'nullable|string|max:100',
            'barcode'            => 'nullable|string|max:100',
            'description'        => 'nullable|string',
            'moto_models'        => 'nullable|array',
            'moto_models.*'      => 'exists:moto_models,id',
            'unit'               => 'required|string|max:50',
            'cost'               => 'required|numeric|min:0',
            'price'              => 'required|numeric|min:0',
            'min_stock'          => 'nullable|numeric|min:0',
            'category_id'        => 'nullable|exists:product_categories,id',
            'brand_id'           => 'nullable|exists:product_brands,id',
            'active'             => 'sometimes|boolean',
            'photos'             => 'nullable|array|max:8',
            'photos.*'           => 'image|max:3072',
            'main_photo_index'   => 'nullable|integer',
            // Ubicación por almacén: [warehouse_id => "PA5-VI2"]
            'locations'          => 'nullable|array',
            'locations.*'        => 'nullable|string|max:40',
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateProductCode((int) $companyId);
        }

        try {
            unset($validated['locations']);
            $product = Product::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            $product->motoModels()->sync($validated['moto_models'] ?? []);
            $this->saveLocations($product, request()->input('locations', []));
            $this->handlePhotos($product);

            return redirect()->route('products.index')
                ->with('success', 'Producto creado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear producto', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function show(Product $product)
    {
        $this->authorizeProduct($product);
        $product->load(['category', 'brand', 'photos', 'motoModels.brand']);
        if (Product::locationsReady()) {
            $product->load('locations');
        }

        $warehouses        = Warehouse::where('company_id', $product->company_id)->where('active', true)->get();
        $stockByWarehouse  = $warehouses->mapWithKeys(fn ($w) => [
            $w->id => ['warehouse' => $w, 'stock' => $product->stockInWarehouse($w->id)],
        ]);
        $globalStock = $stockByWarehouse->sum('stock');

        $recentMovements = $product->inventoryMovements()
            ->with(['warehouse', 'destinationWarehouse', 'user'])
            ->latest('movement_date')
            ->limit(10)
            ->get();

        // Historial de precios: solo para quien ve la información del dueño.
        $user = auth()->user();
        $priceChanges = (\Illuminate\Support\Facades\Schema::hasTable('product_price_changes')
            && ($user->is_super_admin || $user->hasPermissionInCompany('income-statement.view', $user->getCurrentCompany())))
            ? $product->priceChanges()->with('user:id,name')->limit(10)->get()
            : null;

        return view('inventory.products.show', compact(
            'product', 'stockByWarehouse', 'globalStock', 'recentMovements', 'priceChanges'
        ));
    }

    public function kardexGeneral(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $warehouses = Warehouse::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('active', true)->orderBy('name')->get();

        $products = Product::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('active', true)->orderBy('name')->get();

        $query = \App\Models\InventoryMovement::with(['product', 'warehouse', 'destinationWarehouse', 'user'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId));

        $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;

        if ($request->product_id)   $query->where('product_id',   $request->product_id);
        // Con almacén: también las transferencias que llegan a él (son entradas).
        KardexMath::whereWarehouse($query, $warehouseId);
        if ($request->type)         $query->where('type',         $request->type);
        if ($request->date_from)    $query->whereDate('movement_date', '>=', $request->date_from);
        if ($request->date_to)      $query->whereDate('movement_date', '<=', $request->date_to);

        // Totales de todo lo filtrado (no solo de la página de 50).
        $totals = KardexMath::totals($query, $warehouseId);
        $totals['single_product'] = (bool) $request->product_id;

        $movements = $query->orderByDesc('movement_date')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('inventory.kardex', compact('movements', 'warehouses', 'products', 'totals', 'warehouseId'));
    }

    public function kardex(Product $product, Request $request)
    {
        $this->authorizeProduct($product);
        $product->load(['category', 'brand']);

        $warehouses = Warehouse::where('company_id', $product->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;

        $query = $product->inventoryMovements()->getQuery()->with(['warehouse', 'destinationWarehouse', 'user']);
        // Con almacén: también las transferencias que llegan a él (son entradas).
        KardexMath::whereWarehouse($query, $warehouseId);

        // Saldo inicial: todo lo anterior a "desde" (antes el saldo arrancaba en 0
        // aunque hubiera stock previo).
        $opening = 0.0;
        if ($request->date_from) {
            $before = (clone $query)->whereDate('movement_date', '<', $request->date_from);
            $opening = KardexMath::totals($before, $warehouseId)['net'];
            $query->whereDate('movement_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('movement_date', '<=', $request->date_to);
        }

        $movements = $query->orderBy('movement_date')->orderBy('id')->get();

        $balance   = $opening;
        $movements = $movements->map(function ($mov) use (&$balance, $warehouseId) {
            $delta = KardexMath::delta($mov, $warehouseId);
            $balance += $delta;
            $mov->entry = $delta > 0 ? $delta : null;
            $mov->exit  = $delta < 0 ? -$delta : null;
            // Transferencia sin almacén elegido: no cambia el total.
            $mov->neutral = $delta == 0 && $mov->type === 'transfer' ? (float) $mov->quantity : null;
            $mov->balance = $balance;
            return $mov;
        });

        $totals = [
            'opening' => $opening,
            'entries' => (float) $movements->sum(fn ($m) => $m->entry ?? 0),
            'exits'   => (float) $movements->sum(fn ($m) => $m->exit ?? 0),
            'closing' => $balance,
            'from'    => $request->date_from,
        ];

        return view('inventory.products.kardex', compact('product', 'movements', 'warehouses', 'totals', 'warehouseId'));
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);
        $product->load('photos');

        return view('inventory.products.edit', array_merge(
            $this->formData($product->company_id),
            compact('product')
        ));
    }

    public function update(Product $product)
    {
        $this->authorizeProduct($product);

        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id', $product->company_id)
            : $product->company_id;

        $validated = request()->validate([
            'company_id'         => ['nullable', 'exists:companies,id'],
            'name'               => 'required|string|max:255',
            'sku'                => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'code'               => 'nullable|string|max:100',
            'barcode'            => 'nullable|string|max:100',
            'description'        => 'nullable|string',
            'moto_models'        => 'nullable|array',
            'moto_models.*'      => 'exists:moto_models,id',
            'unit'               => 'required|string|max:50',
            'cost'               => 'required|numeric|min:0',
            'price'              => 'required|numeric|min:0',
            'min_stock'          => 'nullable|numeric|min:0',
            'category_id'        => 'nullable|exists:product_categories,id',
            'brand_id'           => 'nullable|exists:product_brands,id',
            'active'             => 'sometimes|boolean',
            'photos'             => 'nullable|array|max:8',
            'photos.*'           => 'image|max:3072',
            'main_photo_index'   => 'nullable|integer',
            // Ubicación por almacén: [warehouse_id => "PA5-VI2"]
            'locations'          => 'nullable|array',
            'locations.*'        => 'nullable|string|max:40',
            'delete_photos'      => 'nullable|array',
            'delete_photos.*'    => 'exists:product_photos,id',
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateProductCode((int) $companyId);
        }

        // Sin permiso de precios: se guarda todo lo demás y el precio/costo no cambian.
        if (! \App\Support\PricePermission::allowed()) {
            $changed = abs((float) $validated['price'] - (float) $product->price) >= 0.005
                || abs((float) $validated['cost'] - (float) $product->cost) >= 0.005;
            if ($changed) {
                return back()->withInput()->withErrors(['price' => \App\Support\PricePermission::DENIED]);
            }
            unset($validated['price'], $validated['cost']);
        }

        try {
            unset($validated['locations']);
            $product->update([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', false),
            ]);

            $product->motoModels()->sync($validated['moto_models'] ?? []);
            $this->saveLocations($product, request()->input('locations', []));

            // Delete photos
            if (request()->has('delete_photos')) {
                foreach (request('delete_photos') as $photoId) {
                    $photo = ProductPhoto::find($photoId);
                    if ($photo && $photo->product_id === $product->id) {
                        Storage::disk('public')->delete($photo->file_path);
                        app(PhotoThumbnailService::class)->delete($photo->thumb_path);
                        $photo->delete();
                    }
                }
            }

            $this->handlePhotos($product);

            return redirect()->route('products.index')
                ->with('success', 'Producto actualizado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar producto', ['product_id' => $product->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);

        try {
            $product->delete();
            return redirect()->route('products.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar producto', ['product_id' => $product->id, 'message' => $exception->getMessage()]);
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function destroyPhoto(ProductPhoto $photo)
    {
        $product = $photo->product;
        $this->authorizeProduct($product);

        Storage::disk('public')->delete($photo->file_path);
        app(PhotoThumbnailService::class)->delete($photo->thumb_path);
        $wasMain = $photo->is_main;
        $photo->delete();

        if ($wasMain) {
            $first = $product->photos()->first();
            if ($first) {
                $first->update(['is_main' => true]);
            }
        }

        return back()->with('success', 'Foto eliminada.');
    }

    protected function authorizeProduct(Product $product): void
    {
        if (!auth()->user()->is_super_admin
            && $product->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    protected function formData(?int $companyId = null): array
    {
        $user       = auth()->user();
        $cid        = $companyId ?? $user->getCurrentCompany()?->id;
        $categories = ProductCategory::where('company_id', $cid)->where('active', true)->orderBy('name')->get();
        $brands     = ProductBrand::where('company_id', $cid)->where('active', true)->orderBy('name')->get();
        $units      = ProductUnit::where('company_id', $cid)->where('active', true)->orderBy('name')->get();
        $motoModels = \App\Models\Motos\MotoModel::with('brand')->where('company_id', $cid)->orderBy('name')->get();
        $companies  = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        // Ubicación por almacén (un campo por almacén activo).
        $warehouses = Warehouse::where('company_id', $cid)->where('active', true)->orderBy('name')->get(['id', 'name']);

        return compact('categories', 'brands', 'units', 'motoModels', 'companies', 'warehouses');
    }

    /** Guarda la ubicación por almacén (solo almacenes de la empresa del producto). */
    private function saveLocations(Product $product, $locations): void
    {
        if (! is_array($locations) || ! $locations) {
            return;
        }
        $valid = Warehouse::where('company_id', $product->company_id)->pluck('id')->all();
        foreach ($locations as $warehouseId => $value) {
            if (in_array((int) $warehouseId, $valid, true)) {
                $product->setLocation((int) $warehouseId, $value);
            }
        }
    }

    // ── Importación desde Excel ───────────────────────────────

    public function import()
    {
        return view('inventory.products.import');
    }

    public function processImport()
    {
        request()->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withErrors(['error' => 'No hay una empresa activa seleccionada.']);
        }

        try {
            $path     = request()->file('file')->store('imports', 'local');
            $fullPath = Storage::disk('local')->path($path);

            // Parser genérico por encabezado (compartido con el importador de stock).
            $rows     = $this->parseImportRows($fullPath);
            $counters = ['created' => 0, 'updated' => 0];
            $errors   = [];

            foreach ($rows as $d) {
                try {
                    $this->upsertProduct($d, (int) $companyId, $counters);
                } catch (\Throwable $e) {
                    $errors[] = "«{$d['name']}»: " . $e->getMessage();
                    Log::warning('Import product row failed', ['name' => $d['name'] ?? '', 'msg' => $e->getMessage()]);
                }
            }

            Storage::disk('local')->delete($path);

            return back()->with('import_result', [
                'imported' => $counters['created'],
                'skipped'  => $counters['updated'],   // actualizados (por nombre)
                'errors'   => $errors,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al importar productos', ['msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo procesar el archivo: ' . $e->getMessage()]);
        }
    }

    private function handlePhotos(Product $product): void
    {
        if (!request()->hasFile('photos')) {
            return;
        }

        $mainIndex    = (int) request('main_photo_index', 0);
        $currentCount = $product->photos()->count();

        $thumbs = app(PhotoThumbnailService::class);

        foreach (request()->file('photos') as $i => $file) {
            $dir  = "company/{$product->company_id}/products/{$product->id}";
            $path = $file->store($dir, 'public');
            ProductPhoto::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'file_path'  => $path,
                'thumb_path' => $thumbs->store($file, $dir),
                'file_name'  => $file->getClientOriginalName(),
                'is_main'    => ($i === $mainIndex && $currentCount === 0),
                'sort_order' => $currentCount + $i,
            ]);
        }

        if (!$product->photos()->where('is_main', true)->exists()) {
            $first = $product->photos()->first();
            if ($first) {
                $first->update(['is_main' => true]);
            }
        }
    }
}
