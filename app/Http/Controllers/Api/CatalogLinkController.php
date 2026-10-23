<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Catalog\CatalogFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo público por sucursal para el móvil: enlace (para compartir y el QR)
 * y PDF de cada sucursal activa. Mismo dato que la vista web Inventario →
 * Catálogo público (`Inventory\CatalogController`). Los filtros (categorías,
 * marcas, modelos, solo disponibles) se agregan a esas URLs como
 * ?cat=&mar=&mod=&stock= (ver `CatalogFilter`).
 */
class CatalogLinkController extends Controller
{
    public function index(): JsonResponse
    {
        // Scoped a la empresa del token por el global scope.
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $branches->map(function (Branch $b) {
                $token = $b->ensurePublicToken();

                return [
                    'id'      => $b->id,
                    'name'    => $b->name,
                    'address' => $b->address,
                    'url'     => route('catalog.public', $token),
                    'pdf_url' => route('catalog.public.pdf', $token),
                ];
            })->values(),
            // Opciones para "Armar catálogo" (solo las que tienen productos activos).
            'filters'  => CatalogFilter::options(),
            'pdf_warn' => CatalogFilter::PDF_WARN,
            'photo_max' => CatalogFilter::PHOTO_MAX,
        ]);
    }

    /** Cuántos productos tendría el catálogo de la sucursal con esos filtros. */
    public function count(Request $request, Branch $branch): JsonResponse
    {
        abort_unless($branch->active, 404);

        $filter = CatalogFilter::fromRequest($request);
        $count = $filter->count($branch);

        return response()->json(['data' => ['count' => $count, 'blocked' => $filter->photoBlock($count)]]);
    }
}
