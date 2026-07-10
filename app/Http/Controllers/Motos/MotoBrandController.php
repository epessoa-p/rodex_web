<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoBrand;
use Illuminate\Support\Facades\Log;

class MotoBrandController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = MotoBrand::withCount('models')->latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        return view('motos.brands.index', ['brands' => $query->paginate(20)]);
    }

    public function create()
    {
        return view('motos.brands.create');
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }
        $validated = $this->validateBrand();
        try {
            MotoBrand::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('moto-brands.index')->with('success', 'Marca creada.');
        } catch (\Throwable $e) {
            Log::error('Error al crear marca moto', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit(MotoBrand $brand)
    {
        $this->authorizeBrand($brand);
        return view('motos.brands.edit', compact('brand'));
    }

    public function update(MotoBrand $brand)
    {
        $this->authorizeBrand($brand);
        $validated = $this->validateBrand();
        $brand->update([...$validated, 'active' => request()->boolean('active', false)]);
        return redirect()->route('moto-brands.index')->with('success', 'Marca actualizada.');
    }

    public function destroy(MotoBrand $brand)
    {
        $this->authorizeBrand($brand);
        try {
            $brand->delete();
            return redirect()->route('moto-brands.index')->with('success', 'Marca eliminada.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validateBrand(): array
    {
        return request()->validate([
            'name'    => 'required|string|max:255',
            'country' => 'nullable|string|max:100',
            'active'  => 'sometimes|boolean',
        ]);
    }

    private function authorizeBrand(MotoBrand $brand): void
    {
        if (!auth()->user()->is_super_admin && $brand->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
