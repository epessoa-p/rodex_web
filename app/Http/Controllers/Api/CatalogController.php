<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductOrigin;
use App\Models\Motos\MotoBrand;
use App\Models\Motos\MotoModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Catálogos de inventario desde el móvil (hub "Inventario"): categorías,
 * marcas, orígenes, marcas de moto y modelos de moto. Un solo controlador
 * parametrizado por {type}; el permiso se resuelve en la ruta (middleware
 * `api.catalog`) con el módulo de cada tipo. Aislamiento por global scope.
 */
class CatalogController extends Controller
{
    /** type => [modelo, módulo de permisos, campos extra editables]. */
    public const TYPES = [
        'categories'  => [ProductCategory::class, 'product-categories', ['description']],
        'brands'      => [ProductBrand::class,    'product-brands',     ['description']],
        'origins'     => [ProductOrigin::class,   'product-origins',    []],
        'moto-brands' => [MotoBrand::class,       'moto-brands',        ['country']],
        'moto-models' => [MotoModel::class,       'moto-models',        ['moto_brand_id', 'engine_cc', 'year', 'suggested_price']],
    ];

    /** Listado completo (activos primero) con ?q= por nombre. */
    public function index(Request $request, string $type)
    {
        [$model] = $this->resolve($type);
        $q = trim((string) $request->query('q', ''));

        $query = $model::query()
            ->when($type === 'moto-models', fn ($w) => $w->with('brand'))
            ->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))
            ->orderByDesc('active')
            ->orderBy('name');

        return response()->json([
            'data' => $query->get()->map(fn ($m) => $this->payload($type, $m))->values(),
        ]);
    }

    /**
     * Alta. Si ya existe uno con el mismo nombre (sin distinguir mayúsculas;
     * en modelos, misma marca) lo reutiliza y lo reactiva: así el selector
     * "escribir y crear" de productos no duplica el catálogo.
     */
    public function store(Request $request, string $type)
    {
        [$model] = $this->resolve($type);
        $company = $request->attributes->get('tenant_company');
        $data    = $this->validated($request, $type);

        $existing = $this->findByName($type, $model, $data);
        if ($existing) {
            if (! $existing->active) {
                $existing->update(['active' => true]);
            }

            return response()->json(['data' => $this->payload($type, $existing->fresh()), 'created' => false]);
        }

        $item = $model::create([...$data, 'company_id' => $company->id, 'active' => true]);
        if ($type === 'moto-models') {
            $item->load('brand');
        }

        return response()->json(['data' => $this->payload($type, $item), 'created' => true], 201);
    }

    public function update(Request $request, string $type, int $id)
    {
        [$model] = $this->resolve($type);
        $item = $model::query()->findOrFail($id);
        $data = $this->validated($request, $type, $id);

        $dup = $this->findByName($type, $model, $data, $id);
        if ($dup) {
            return response()->json([
                'message' => 'Ya existe otro registro con ese nombre.',
                'code'    => 'catalog_name_taken',
            ], 422);
        }

        $item->update([...$data, 'active' => $request->boolean('active', true)]);
        if ($type === 'moto-models') {
            $item->load('brand');
        }

        return response()->json(['data' => $this->payload($type, $item->fresh($type === 'moto-models' ? ['brand'] : []))]);
    }

    private function resolve(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function validated(Request $request, string $type, ?int $ignoreId = null): array
    {
        $rules = [
            'name'   => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ];
        $rules += match ($type) {
            'categories', 'brands' => ['description' => ['nullable', 'string', 'max:500']],
            'moto-brands'          => ['country' => ['nullable', 'string', 'max:100']],
            'moto-models'          => [
                'moto_brand_id'   => ['required', Rule::exists('moto_brands', 'id')->where('company_id', $request->attributes->get('tenant_company')?->id)],
                'engine_cc'       => ['nullable', 'string', 'max:30'],
                'year'            => ['nullable', 'integer', 'min:1900', 'max:2100'],
                'suggested_price' => ['nullable', 'numeric', 'min:0'],
            ],
            default => [],
        };

        $data = $request->validate($rules);
        $data['name'] = mb_strtoupper(trim($data['name']));
        unset($data['active']);

        if ($type === 'moto-models') {
            $data['suggested_price'] = $data['suggested_price'] ?? 0;
        }

        return $data;
    }

    private function findByName(string $type, string $model, array $data, ?int $ignoreId = null): ?Model
    {
        return $model::query()
            ->whereRaw('UPPER(name) = ?', [mb_strtoupper($data['name'])])
            ->when($type === 'moto-models', fn ($w) => $w->where('moto_brand_id', $data['moto_brand_id']))
            ->when($ignoreId, fn ($w) => $w->where('id', '!=', $ignoreId))
            ->first();
    }

    private function payload(string $type, Model $m): array
    {
        $base = [
            'id'     => $m->id,
            'name'   => $m->name,
            'active' => (bool) $m->active,
        ];

        return $base + match ($type) {
            'categories', 'brands' => ['description' => $m->description],
            'moto-brands'          => ['country' => $m->country],
            'moto-models'          => [
                'moto_brand_id'   => $m->moto_brand_id,
                'brand'           => $m->brand?->name,
                'display_name'    => $m->display_name,
                'engine_cc'       => $m->engine_cc,
                'year'            => $m->year,
                'suggested_price' => (float) $m->suggested_price,
            ],
            default => [],
        };
    }
}
