<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Concerns\ParsesProductImport;
use App\Models\Company;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductOrigin;
use App\Models\Inventory\ProductUnit;
use App\Models\Inventory\ProductPhoto;
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
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateProductCode((int) $companyId);
        }

        try {
            $product = Product::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            $product->motoModels()->sync($validated['moto_models'] ?? []);
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

        return view('inventory.products.show', compact(
            'product', 'stockByWarehouse', 'globalStock', 'recentMovements'
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

        if ($request->product_id)   $query->where('product_id',   $request->product_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->type)         $query->where('type',         $request->type);
        if ($request->date_from)    $query->whereDate('movement_date', '>=', $request->date_from);
        if ($request->date_to)      $query->whereDate('movement_date', '<=', $request->date_to);

        $movements = $query->orderByDesc('movement_date')->orderByDesc('id')->paginate(50);

        return view('inventory.kardex', compact('movements', 'warehouses', 'products'));
    }

    public function kardex(Product $product, Request $request)
    {
        $this->authorizeProduct($product);
        $product->load(['category', 'brand']);

        $warehouses = Warehouse::where('company_id', $product->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $query = $product->inventoryMovements()->with(['warehouse', 'destinationWarehouse', 'user']);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->date_from) {
            $query->whereDate('movement_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('movement_date', '<=', $request->date_to);
        }

        $movements = $query->orderBy('movement_date')->orderBy('id')->get();

        $balance   = 0;
        $movements = $movements->map(function ($mov) use (&$balance) {
            if (in_array($mov->type, ['in', 'adjustment'])) {
                $balance    += $mov->quantity;
                $mov->entry  = $mov->quantity;
                $mov->exit   = null;
            } else {
                $balance   -= $mov->quantity;
                $mov->entry = null;
                $mov->exit  = $mov->quantity;
            }
            $mov->balance = $balance;
            return $mov;
        });

        return view('inventory.products.kardex', compact('product', 'movements', 'warehouses'));
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
            'delete_photos'      => 'nullable|array',
            'delete_photos.*'    => 'exists:product_photos,id',
        ]);

        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateProductCode((int) $companyId);
        }

        try {
            $product->update([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', false),
            ]);

            $product->motoModels()->sync($validated['moto_models'] ?? []);

            // Delete photos
            if (request()->has('delete_photos')) {
                foreach (request('delete_photos') as $photoId) {
                    $photo = ProductPhoto::find($photoId);
                    if ($photo && $photo->product_id === $product->id) {
                        Storage::disk('public')->delete($photo->file_path);
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

        return compact('categories', 'brands', 'units', 'motoModels', 'companies');
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

        foreach (request()->file('photos') as $i => $file) {
            $path = $file->store("company/{$product->company_id}/products/{$product->id}", 'public');
            ProductPhoto::create([
                'product_id' => $product->id,
                'company_id' => $product->company_id,
                'file_path'  => $path,
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
