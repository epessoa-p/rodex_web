<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Purchases\Supplier;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Supplier::with('company')->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        if ($q = request('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        return view('purchases.suppliers.index', ['suppliers' => $query->paginate(15)]);
    }

    public function create()
    {
        return view('purchases.suppliers.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = request()->validate([
            'name'         => 'required|string|max:255',
            'nit'          => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:500',
            'notes'        => 'nullable|string',
            'active'       => 'sometimes|boolean',
        ]);

        try {
            Supplier::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);
            return redirect()->route('suppliers.index')->with('success', 'Proveedor creado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear proveedor', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);
        $supplier->load(['purchases' => fn ($q) => $q->latest()->limit(20), 'purchaseOrders' => fn ($q) => $q->latest()->limit(10)]);
        return view('purchases.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);
        return view('purchases.suppliers.edit', array_merge($this->formData(), compact('supplier')));
    }

    public function update(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);

        $validated = request()->validate([
            'name'         => 'required|string|max:255',
            'nit'          => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:500',
            'notes'        => 'nullable|string',
            'active'       => 'sometimes|boolean',
        ]);

        try {
            $supplier->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorizeSupplier($supplier);
        try {
            $supplier->delete();
            return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function authorizeSupplier(Supplier $supplier): void
    {
        if (!auth()->user()->is_super_admin && $supplier->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $companies = $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter();
        return compact('companies');
    }
}
