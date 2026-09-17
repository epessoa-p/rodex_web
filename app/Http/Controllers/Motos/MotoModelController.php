<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoBrand;
use App\Models\Motos\MotoModel;
use Illuminate\Support\Facades\Log;

class MotoModelController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = MotoModel::with('brand')->withCount('units')->latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        return view('motos.models.index', ['models' => $query->paginate(20)]);
    }

    public function create()
    {
        return view('motos.models.create', $this->formData());
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }
        $validated = $this->validateModel();
        try {
            MotoModel::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('moto-models.index')->with('success', 'Modelo creado.');
        } catch (\Throwable $e) {
            Log::error('Error al crear modelo moto', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    /**
     * Alta rápida vía AJAX desde el formulario de unidades.
     * Si ya existe un modelo con el mismo nombre para esa marca, lo reutiliza (y lo reactiva).
     */
    public function quickStore()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'No hay una empresa activa.'], 422);
        }

        $validated = request()->validate([
            'moto_brand_id'   => 'required|integer',
            'name'            => 'required|string|max:255',
            'engine_cc'       => 'nullable|string|max:30',
            'year'            => 'nullable|integer|min:1900|max:2100',
            'suggested_price' => 'nullable|numeric|min:0',
        ]);

        $brand = MotoBrand::where('company_id', $companyId)->find($validated['moto_brand_id']);
        if (!$brand) {
            return response()->json(['ok' => false, 'message' => 'La marca seleccionada no existe.'], 422);
        }

        try {
            $name  = mb_strtoupper(trim($validated['name']));
            $model = MotoModel::where('company_id', $companyId)
                ->where('moto_brand_id', $brand->id)
                ->whereRaw('UPPER(name) = ?', [$name])
                ->first();

            if ($model) {
                if (!$model->active) {
                    $model->update(['active' => true]);
                }
            } else {
                $model = MotoModel::create([
                    'company_id'      => $companyId,
                    'moto_brand_id'   => $brand->id,
                    'name'            => $name,
                    'engine_cc'       => $validated['engine_cc'] ?? null,
                    'year'            => $validated['year'] ?? null,
                    'suggested_price' => $validated['suggested_price'] ?? 0,
                    'active'          => true,
                ]);
            }
            $model->setRelation('brand', $brand);

            return response()->json([
                'ok'    => true,
                'model' => [
                    'id'              => $model->id,
                    'name'            => $model->name,
                    'display_name'    => $model->display_name,
                    'moto_brand_id'   => $brand->id,
                    'suggested_price' => (float) $model->suggested_price,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en alta rápida de modelo moto', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'No se pudo guardar el modelo.'], 500);
        }
    }

    public function edit(MotoModel $model)
    {
        $this->authorizeModel($model);
        return view('motos.models.edit', array_merge($this->formData(), compact('model')));
    }

    public function update(MotoModel $model)
    {
        $this->authorizeModel($model);
        $validated = $this->validateModel();
        $model->update([...$validated, 'active' => request()->boolean('active', false)]);
        return redirect()->route('moto-models.index')->with('success', 'Modelo actualizado.');
    }

    public function destroy(MotoModel $model)
    {
        $this->authorizeModel($model);
        try {
            $model->delete();
            return redirect()->route('moto-models.index')->with('success', 'Modelo eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validateModel(): array
    {
        $data = request()->validate([
            'moto_brand_id'   => 'required|exists:moto_brands,id',
            'name'            => 'required|string|max:255',
            'engine_cc'       => 'nullable|string|max:30',
            'year'            => 'nullable|integer|min:1900|max:2100',
            'suggested_price' => 'nullable|numeric|min:0',
            'description'     => 'nullable|string',
            'active'          => 'sometimes|boolean',
        ]);

        // La columna es NOT NULL DEFAULT 0.00: si no se indica, guardar 0.
        $data['suggested_price'] = $data['suggested_price'] ?? 0;

        return $data;
    }

    private function authorizeModel(MotoModel $model): void
    {
        if (!auth()->user()->is_super_admin && $model->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        $brands = MotoBrand::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        return compact('brands');
    }
}
