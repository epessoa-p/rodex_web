<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\Tenancy;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Catálogo público de productos por sucursal (solo consulta, sin login).
 *
 * Resuelve la empresa sin sesión con Tenancy::runAs (igual que el catálogo de
 * loyalty): primero busca la sucursal por su token único (contexto "ver todo"),
 * luego fija la empresa dueña para que el global scope aísle los datos.
 */
class PublicCatalogController extends Controller
{
    /** Página HTML del catálogo de una sucursal. */
    public function show(string $token)
    {
        return $this->withCatalog($token, fn ($data) => view('catalog.branch', $data));
    }

    /** Descarga del catálogo en PDF (dompdf). */
    public function pdf(string $token)
    {
        return $this->withCatalog($token, function ($data) {
            $data['logoData'] = $this->logoBase64($data['company']);

            $pdf = Pdf::loadView('catalog.branch-pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOption(['isRemoteEnabled' => true]);

            $name = 'catalogo_' . \Illuminate\Support\Str::slug($data['branch']->name) . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->download($name);
        });
    }

    /**
     * Resuelve la sucursal por token y arma los datos del catálogo, ejecutando
     * $render dentro del contexto de la empresa dueña.
     */
    private function withCatalog(string $token, callable $render)
    {
        $tenancy = app(Tenancy::class);

        // El token es único global: se busca sin filtro de empresa.
        $branch = $tenancy->runAs(null, fn () =>
            Branch::where('public_token', $token)->with('company')->first()
        );

        abort_if(! $branch || ! $branch->active || ! $branch->company?->active, 404);

        return $tenancy->runAs($branch->company_id, function () use ($branch, $render) {
            $products = Product::where('active', true)
                ->with(['category', 'brand', 'photos'])
                ->orderBy('name')
                ->get();

            $branches = Branch::where('active', true)->get();
            $availability = $this->availability($branch, $branches, $products);

            return $render([
                'company'      => $branch->company,
                'branch'       => $branch,
                'products'     => $products,
                'availability' => $availability,
                'generatedAt'  => now()->format('d/m/Y H:i'),
            ]);
        });
    }

    /**
     * Para cada producto: ¿disponible en ESTA sucursal? y ¿en qué OTRAS?
     * Devuelve [product_id => ['here' => bool, 'others' => [nombres]]].
     */
    private function availability(Branch $branch, $branches, $products): array
    {
        $warehouseIds = $branches->pluck('warehouse_id')->filter()->unique()->values();

        // Stock por almacén: [warehouse_id => [product_id => qty]]
        $stock = [];
        foreach ($warehouseIds as $whId) {
            $stock[$whId] = $this->warehouseStockMap($branch->company_id, (int) $whId);
        }

        $currentWh = $branch->warehouse_id;
        $result = [];

        foreach ($products as $p) {
            $here = ($stock[$currentWh][$p->id] ?? 0) > 0;

            $others = [];
            foreach ($branches as $b) {
                if ($b->id === $branch->id) {
                    continue;
                }
                if (($stock[$b->warehouse_id][$p->id] ?? 0) > 0) {
                    $others[] = $b->name;
                }
            }

            $result[$p->id] = ['here' => $here, 'others' => $others];
        }

        return $result;
    }

    /**
     * Stock neto por producto en un almacén (derivado de inventory_movements).
     * Replica la lógica de StockController::warehouseStockMap.
     */
    private function warehouseStockMap(int $companyId, int $warehouseId): array
    {
        $in = InventoryMovement::where('company_id', $companyId)
            ->where(function ($q) use ($warehouseId) {
                $q->where(fn ($w) => $w->where('warehouse_id', $warehouseId)->whereIn('type', ['in', 'adjustment']))
                  ->orWhere(fn ($w) => $w->where('destination_warehouse_id', $warehouseId)->where('type', 'transfer'));
            })
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')
            ->pluck('q', 'product_id');

        $out = InventoryMovement::where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', ['out', 'transfer'])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')
            ->pluck('q', 'product_id');

        $map = [];
        foreach ($in as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) + (float) $q;
        }
        foreach ($out as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) - (float) $q;
        }

        return $map;
    }

    /** Logo de la empresa embebido en base64 para el PDF (dompdf no resuelve URLs). */
    private function logoBase64($company): ?string
    {
        $path = $company?->logo_file;
        if (! $path || ! is_file($path)) {
            return null;
        }

        return 'data:' . (mime_content_type($path) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($path));
    }
}
