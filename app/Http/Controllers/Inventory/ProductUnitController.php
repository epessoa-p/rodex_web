<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inventory\ProductUnit;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductUnitController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = ProductUnit::with('company')->orderBy('name');

        $cid = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
        if ($cid) {
            $query->where('company_id', $cid);
        }

        $units = $query->paginate(30);

        // Conteo de productos por nombre de unidad (una consulta) para el listado.
        $counts = Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->selectRaw('unit, COUNT(*) as c')
            ->groupBy('unit')
            ->pluck('c', 'unit');

        return view('inventory.units.index', compact('units', 'counts'));
    }

    public function create()
    {
        return view('inventory.units.create', $this->formData());
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
            'name'   => 'required|string|max:50',
            'active' => 'sometimes|boolean',
        ]);

        try {
            ProductUnit::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            return redirect()->route('product-units.index')
                ->with('success', 'Unidad creada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear unidad', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No se pudo crear (¿nombre repetido?).']);
        }
    }

    public function edit(ProductUnit $unit)
    {
        $this->authorizeUnit($unit);
        return view('inventory.units.edit', array_merge($this->formData(), compact('unit')));
    }

    public function update(ProductUnit $unit)
    {
        $this->authorizeUnit($unit);

        $validated = request()->validate([
            'name'   => 'required|string|max:50',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $unit->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('product-units.index')
                ->with('success', 'Unidad actualizada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'No se pudo actualizar (¿nombre repetido?).']);
        }
    }

    public function destroy(ProductUnit $unit)
    {
        $this->authorizeUnit($unit);

        try {
            $unit->delete();
            return redirect()->route('product-units.index')
                ->with('success', 'Unidad eliminada exitosamente.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function authorizeUnit(ProductUnit $unit): void
    {
        if (!auth()->user()->is_super_admin
            && $unit->company_id !== auth()->user()->getCurrentCompany()?->id) {
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
