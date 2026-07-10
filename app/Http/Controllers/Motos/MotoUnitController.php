<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Motos\MotoBrand;
use App\Models\Motos\MotoModel;
use App\Models\Motos\MotoUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MotoUnitController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = MotoUnit::with(['model.brand', 'branch'])->latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        if ($request->status)        $query->where('status', $request->status);
        if ($request->moto_model_id) $query->where('moto_model_id', $request->moto_model_id);
        if ($q = $request->q) {
            $query->where(fn ($s) => $s->where('chassis_number', 'like', "%{$q}%")->orWhere('engine_number', 'like', "%{$q}%"));
        }

        $cid    = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
        $models = MotoModel::with('brand')->when($cid, fn ($x) => $x->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return view('motos.units.index', ['units' => $query->paginate(15)->withQueryString(), 'models' => $models]);
    }

    public function create()
    {
        return view('motos.units.create', $this->formData());
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }
        $validated = $this->validateUnit($companyId);
        try {
            MotoUnit::create([...$validated, 'company_id' => $companyId, 'status' => 'disponible']);
            return redirect()->route('moto-units.index')->with('success', 'Unidad registrada en inventario.');
        } catch (\Throwable $e) {
            Log::error('Error al crear unidad moto', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        $unit->load(['model.brand', 'branch', 'sale.client', 'warranties']);
        return view('motos.units.show', compact('unit'));
    }

    public function edit(MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        if (in_array($unit->status, ['vendida', 'entregada'])) {
            return back()->withErrors(['error' => 'No se puede editar una unidad vendida o entregada.']);
        }
        return view('motos.units.edit', array_merge($this->formData(), compact('unit')));
    }

    public function update(MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        if (in_array($unit->status, ['vendida', 'entregada'])) {
            return back()->withErrors(['error' => 'No se puede editar una unidad vendida o entregada.']);
        }
        $validated = $this->validateUnit($unit->company_id, $unit->id);
        $unit->update($validated);
        return redirect()->route('moto-units.show', $unit)->with('success', 'Unidad actualizada.');
    }

    public function destroy(MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        if (in_array($unit->status, ['vendida', 'entregada'])) {
            return back()->withErrors(['error' => 'No se puede eliminar una unidad vendida o entregada.']);
        }
        $unit->delete();
        return redirect()->route('moto-units.index')->with('success', 'Unidad eliminada.');
    }

    private function validateUnit(int $companyId, ?int $ignoreId = null): array
    {
        return request()->validate([
            'moto_model_id'  => 'required|exists:moto_models,id',
            'branch_id'      => 'nullable|exists:branches,id',
            'chassis_number' => [
                'required', 'string', 'max:80',
                Rule::unique('moto_units', 'chassis_number')->where('company_id', $companyId)->ignore($ignoreId)->whereNull('deleted_at'),
            ],
            'engine_number'  => 'nullable|string|max:80',
            'color'          => 'nullable|string|max:50',
            'placa'          => 'nullable|string|max:20',
            'year'           => 'nullable|integer|min:1900|max:2100',
            'cost'           => 'required|numeric|min:0',
            'price'          => 'required|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);
    }

    private function authorizeUnit(MotoUnit $unit): void
    {
        if (!auth()->user()->is_super_admin && $unit->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $cid    = auth()->user()->getCurrentCompany()?->id;
        $models = MotoModel::with('brand')->when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        // Todas las marcas activas (no solo las que tienen modelos relacionados).
        $brands = MotoBrand::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        return compact('models', 'branches', 'brands');
    }
}
