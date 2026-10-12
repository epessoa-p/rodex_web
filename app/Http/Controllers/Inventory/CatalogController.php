<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Catalog\CatalogFilter;
use Illuminate\Http\Request;

/**
 * Administración del catálogo público: lista las sucursales con su enlace y QR,
 * y permite armar el PDF / enlace con solo una parte del catálogo.
 */
class CatalogController extends Controller
{
    public function index()
    {
        // Scoped a la empresa activa por el global scope.
        $branches = Branch::where('active', true)->orderBy('name')->get();

        // Asegura un token público (y por tanto un enlace) por sucursal.
        $branches->each->ensurePublicToken();

        $filterOptions = CatalogFilter::options();
        $pdfWarn = CatalogFilter::PDF_WARN;

        return view('inventory.catalog.index', compact('branches', 'filterOptions', 'pdfWarn'));
    }

    /** AJAX: cuántos productos tendría el catálogo de la sucursal con esos filtros. */
    public function count(Request $request)
    {
        $branch = Branch::where('active', true)->findOrFail((int) $request->query('branch_id'));

        return response()->json(['count' => CatalogFilter::fromRequest($request)->count($branch)]);
    }
}
