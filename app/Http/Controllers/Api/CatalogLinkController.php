<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;

/**
 * Catálogo público por sucursal para el móvil: enlace (para compartir y el QR)
 * y PDF de cada sucursal activa. Mismo dato que la vista web Inventario →
 * Catálogo público (`Inventory\CatalogController`).
 */
class CatalogLinkController extends Controller
{
    public function index(): JsonResponse
    {
        // Scoped a la empresa del token por el global scope.
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return response()->json(['data' => $branches->map(function (Branch $b) {
            $token = $b->ensurePublicToken();

            return [
                'id'      => $b->id,
                'name'    => $b->name,
                'address' => $b->address,
                'url'     => route('catalog.public', $token),
                'pdf_url' => route('catalog.public.pdf', $token),
            ];
        })->values()]);
    }
}
