<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchases\Supplier;
use Illuminate\Http\Request;

/**
 * Proveedores para el móvil: listado/búsqueda y alta rápida.
 * Aislamiento por empresa vía global scope (index) y tenant_company (store).
 */
class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('nit', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn (Supplier $s) => $this->payload($s));

        return response()->json(['data' => $suppliers]);
    }

    public function store(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'nit'          => 'nullable|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:500',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $supplier = Supplier::create([
            ...$validated,
            'company_id' => $company->id,
            'active'     => true,
        ]);

        return response()->json(['data' => $this->payload($supplier)], 201);
    }

    private function payload(Supplier $s): array
    {
        return [
            'id'           => $s->id,
            'name'         => $s->name,
            'nit'          => $s->nit,
            'contact_name' => $s->contact_name,
            'phone'        => $s->phone,
            'email'        => $s->email,
            'active'       => (bool) $s->active,
        ];
    }
}
