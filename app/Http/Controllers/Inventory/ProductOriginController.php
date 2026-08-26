<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductOrigin;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Catálogo de orígenes (países). Alta/edición por modal + AJAX (JSON); el listado
 * se actualiza sin recargar. Son pocos registros, por eso no hay paginación pesada.
 */
class ProductOriginController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $origins = ProductOrigin::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get();

        // Conteo de productos por origen (una consulta).
        $counts = Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->selectRaw('origin_id, COUNT(*) as c')
            ->groupBy('origin_id')
            ->pluck('c', 'origin_id');

        return view('inventory.origins.index', compact('origins', 'counts'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'No hay empresa activa.'], 422);
        }

        $validated = $this->validated($request, $companyId);

        try {
            $origin = ProductOrigin::create([
                'company_id' => $companyId,
                'name'       => $validated['name'],
                'active'     => $request->boolean('active', true),
            ]);

            return response()->json(['ok' => true, 'origin' => $this->present($origin)]);
        } catch (\Throwable $e) {
            Log::error('Error al crear origen', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'No se pudo crear el origen.'], 500);
        }
    }

    public function update(Request $request, ProductOrigin $origin)
    {
        $this->authorizeOrigin($origin);

        $validated = $this->validated($request, $origin->company_id, $origin->id);

        try {
            $origin->update([
                'name'   => $validated['name'],
                'active' => $request->boolean('active', false),
            ]);

            return response()->json(['ok' => true, 'origin' => $this->present($origin->fresh())]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'No se pudo actualizar el origen.'], 500);
        }
    }

    public function destroy(ProductOrigin $origin)
    {
        $this->authorizeOrigin($origin);

        try {
            // Borrado físico: la FK (nullOnDelete) deja los productos sin origen y
            // libera el nombre para poder recrearlo (unique company_id+name).
            $origin->forceDelete();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'No se pudo eliminar el origen.'], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function companyId(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? (int) request('company_id') : (int) $user->getCurrentCompany()?->id;
    }

    private function validated(Request $request, int $companyId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'   => [
                'required', 'string', 'max:80',
                Rule::unique('product_origins', 'name')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->ignore($ignoreId),
            ],
            'active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique'   => 'Ese origen ya existe.',
        ]);
    }

    private function present(ProductOrigin $origin): array
    {
        return [
            'id'     => $origin->id,
            'name'   => $origin->name,
            'active' => (bool) $origin->active,
            'count'  => Product::where('origin_id', $origin->id)->count(),
        ];
    }

    private function authorizeOrigin(ProductOrigin $origin): void
    {
        if (!auth()->user()->is_super_admin
            && $origin->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
