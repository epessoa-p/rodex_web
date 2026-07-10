<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ProductCategory;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = ProductCategory::with('company')->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('inventory.categories.index', [
            'categories' => $query->paginate(20),
        ]);
    }

    public function create()
    {
        return view('inventory.categories.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id')
            : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay empresa activa.']);
        }

        $validated = request()->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            ProductCategory::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            return redirect()->route('product-categories.index')
                ->with('success', 'Categoría creada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear categoría', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(ProductCategory $category)
    {
        $this->authorizeCategory($category);
        return view('inventory.categories.edit', array_merge($this->formData(), compact('category')));
    }

    public function update(ProductCategory $category)
    {
        $this->authorizeCategory($category);

        $validated = request()->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            $category->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('product-categories.index')
                ->with('success', 'Categoría actualizada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(ProductCategory $category)
    {
        $this->authorizeCategory($category);

        try {
            $category->delete();
            return redirect()->route('product-categories.index')
                ->with('success', 'Categoría eliminada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function authorizeCategory(ProductCategory $category): void
    {
        if (!auth()->user()->is_super_admin
            && $category->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user      = auth()->user();
        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        return compact('companies');
    }
}
