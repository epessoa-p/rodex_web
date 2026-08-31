<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Mechanic;
use Illuminate\Http\Request;

/**
 * Gestión de mecánicos desde el móvil: listado completo (activos e inactivos)
 * y alta/edición con todos los campos.
 */
class MechanicController extends Controller
{
    /** Listado completo para administrar (incluye inactivos). */
    public function index()
    {
        $mechanics = Mechanic::orderBy('name')->get()
            ->map(fn (Mechanic $m) => $this->payload($m));

        return response()->json(['data' => $mechanics]);
    }

    public function store(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $data = $this->validateData($request);

        $mechanic = Mechanic::create([
            ...$data,
            'company_id' => $company->id,
            'active'     => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->payload($mechanic)], 201);
    }

    public function update(Request $request, Mechanic $mechanic)
    {
        $data = $this->validateData($request);

        $mechanic->update([
            ...$data,
            'active' => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->payload($mechanic->fresh())]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'specialty'       => ['nullable', 'string', 'max:150'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'active'          => ['sometimes', 'boolean'],
        ]);
    }

    private function payload(Mechanic $m): array
    {
        return [
            'id'              => $m->id,
            'name'            => $m->name,
            'specialty'       => $m->specialty,
            'phone'           => $m->phone,
            'commission_rate' => (float) ($m->commission_rate ?? 0),
            'active'          => (bool) $m->active,
        ];
    }
}
