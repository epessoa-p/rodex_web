<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

/**
 * Sucursales desde el móvil (Ajustes → Sucursales). Alcance deliberadamente
 * ACOTADO: solo se listan y se editan nombre, dirección y teléfono. El alta,
 * la baja y el resto de campos (almacén, código, color, estado) siguen siendo
 * exclusivos del panel web, donde además se controla el cupo del plan.
 *
 * Módulo administrativo: no depende de ningún feature de plan, solo del permiso.
 */
class BranchController extends Controller
{
    /** Sucursales de la empresa activa (el global scope aísla por empresa). */
    public function index()
    {
        $branches = Branch::orderBy('name')->get()
            ->map(fn (Branch $b) => $this->item($b));

        return response()->json(['data' => $branches]);
    }

    /** Edita solo los datos de contacto de la sucursal. */
    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
        ]);

        $branch->update($data);

        return response()->json(['data' => $this->item($branch)]);
    }

    private function item(Branch $b): array
    {
        return [
            'id'      => $b->id,
            'name'    => $b->name,
            'address' => $b->address,
            'phone'   => $b->phone,
            'active'  => (bool) $b->active,
        ];
    }
}
