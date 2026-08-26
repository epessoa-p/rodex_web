<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;

/**
 * Administración del catálogo público: lista las sucursales con su enlace y QR.
 */
class CatalogController extends Controller
{
    public function index()
    {
        // Scoped a la empresa activa por el global scope.
        $branches = Branch::where('active', true)->orderBy('name')->get();

        // Asegura un token público (y por tanto un enlace) por sucursal.
        $branches->each->ensurePublicToken();

        return view('inventory.catalog.index', compact('branches'));
    }
}
