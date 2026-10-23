<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Services\Catalog\CatalogFilter;
use App\Support\Tenancy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Catálogo público de productos por sucursal (solo consulta, sin login).
 *
 * Resuelve la empresa sin sesión con Tenancy::runAs (igual que el catálogo de
 * loyalty): primero busca la sucursal por su token único (contexto "ver todo"),
 * luego fija la empresa dueña para que el global scope aísle los datos.
 */
class PublicCatalogController extends Controller
{
    /** Productos por página en la vista web (múltiplo de 3 y 2 columnas). */
    private const PER_PAGE = 24;

    /**
     * Página HTML del catálogo de una sucursal: paginada y con búsqueda en el
     * servidor (?q=) para que no pese con catálogos grandes.
     */
    public function show(Request $request, string $token)
    {
        $q = trim((string) $request->query('q', ''));
        $filter = CatalogFilter::fromRequest($request);

        return $this->withCatalog($token, fn ($data) => view('catalog.branch', $data + ['q' => $q]), $q, true, $filter);
    }

    /**
     * Descarga del catálogo en PDF (dompdf). Acepta los mismos filtros que la
     * página (?cat=&mar=&mod=&stock=) para mandar solo una parte del catálogo.
     */
    public function pdf(Request $request, string $token)
    {
        $filter = CatalogFilter::fromRequest($request);

        return $this->withCatalog($token, function ($data) use ($filter) {
            // PDF con fotos sin filtro (o demasiados productos): no se genera.
            if (isset($data['blocked'])) {
                return response()->view('catalog.pdf-blocked', $data, 422);
            }

            $data['logoData'] = $this->logoBase64($data['company']);
            $data['withPhotos'] = $filter->photos;
            $data['photoData'] = $filter->photos
                ? app(\App\Services\Catalog\CatalogPdfImages::class)->forProducts($data['products'])
                : [];

            $pdf = Pdf::loadView('catalog.branch-pdf', $data)
                ->setPaper('a4', 'portrait')
                // Recorte de fuente: solo se incrustan las letras usadas (sin esto
                // el PDF pesa ~900 KB aunque tenga pocos productos).
                ->setOption(['isRemoteEnabled' => true, 'isFontSubsettingEnabled' => true]);

            $slug = $filter->slug();
            $name = 'catalogo_' . \Illuminate\Support\Str::slug($data['branch']->name)
                . ($slug !== '' ? '_' . $slug : '') . ($filter->photos ? '_fotos' : '') . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->download($name);
        }, '', false, $filter);
    }

    /**
     * Conteo para el armador de la página pública (sin login): cuántos
     * productos tendría el PDF con esos filtros.
     */
    public function count(Request $request, string $token)
    {
        $filter = CatalogFilter::fromRequest($request);
        $tenancy = app(Tenancy::class);
        $branch = $tenancy->runAs(null, fn () => Branch::where('public_token', $token)->with('company')->first());
        abort_if(! $branch || ! $branch->active || ! $branch->company?->active, 404);

        $n = $tenancy->runAs($branch->company_id, fn () => $filter->count($branch));

        return response()->json([
            'count'   => $n,
            'blocked' => $filter->photoBlock($n),
        ]);
    }

    /**
     * Resuelve la sucursal por token y arma los datos del catálogo, ejecutando
     * $render dentro del contexto de la empresa dueña.
     */
    private function withCatalog(string $token, callable $render, string $q = '', bool $paginate = false, ?CatalogFilter $filter = null)
    {
        $filter ??= new CatalogFilter();
        $tenancy = app(Tenancy::class);

        // El token es único global: se busca sin filtro de empresa.
        $branch = $tenancy->runAs(null, fn () =>
            Branch::where('public_token', $token)->with('company')->first()
        );

        abort_if(! $branch || ! $branch->active || ! $branch->company?->active, 404);

        return $tenancy->runAs($branch->company_id, function () use ($branch, $render, $q, $paginate, $filter) {
            $query = Product::where('active', true)
                ->with(['category', 'brand', 'photos'])
                ->orderBy('name');

            // Parte del catálogo elegida (categorías, marcas, modelos, solo disponibles).
            $filter->apply($query, $branch);

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $like = '%' . $q . '%';
                    $w->where('name', 'like', $like)
                      ->orWhere('sku', 'like', $like)
                      ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                      ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like));
                });
            }

            // PDF con fotos: se valida antes de cargar los productos (puede ser pesado).
            if (! $paginate && ($block = $filter->photoBlock((clone $query)->count()))) {
                return $render([
                    'blocked'     => $block,
                    'company'     => $branch->company,
                    'branch'      => $branch,
                    'filterQuery' => $filter->query(),
                ]);
            }

            // Web: paginado (24 por página, conserva ?q=). PDF: todo el catálogo.
            $products = $paginate
                ? $query->paginate(self::PER_PAGE)->withQueryString()
                : $query->get();

            $branches = Branch::where('active', true)->get();
            // Disponibilidad solo de los productos que se van a mostrar.
            $availability = $this->availability($branch, $branches, $products, $this->productIds($products));

            return $render([
                'company'      => $branch->company,
                'branch'       => $branch,
                'products'     => $products,
                'availability' => $availability,
                'generatedAt'  => now()->format('d/m/Y H:i'),
                'filterLabel'  => $filter->labels(),
                'filterQuery'  => $filter->query(),
                // Armador del PDF en la página pública.
                'filterOptions' => $paginate ? CatalogFilter::options() : [],
                'filter'       => $filter,
            ]);
        });
    }

    /**
     * Para cada producto: ¿disponible en ESTA sucursal? y ¿en qué OTRAS?
     * Devuelve [product_id => ['here' => bool, 'others' => [nombres]]].
     */
    private function availability(Branch $branch, $branches, $products, ?array $productIds = null): array
    {
        $warehouseIds = $branches->pluck('warehouse_id')->filter()->unique()->values();

        // Stock por almacén: [warehouse_id => [product_id => qty]]
        $stock = [];
        foreach ($warehouseIds as $whId) {
            $stock[$whId] = CatalogFilter::stockMap($branch->company_id, (int) $whId, $productIds);
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

    /** IDs de los productos a mostrar (colección o paginador). */
    private function productIds($products): array
    {
        $items = $products instanceof \Illuminate\Contracts\Pagination\Paginator
            ? collect($products->items())
            : $products;

        return $items->pluck('id')->map(fn ($id) => (int) $id)->all();
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
