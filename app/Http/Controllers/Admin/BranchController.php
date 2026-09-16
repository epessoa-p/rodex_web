<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Admin\BranchWarehouseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Sucursales. Cada sucursal nace con SU almacén (creado por el sistema) y al
 * editarla el almacén copia nombre/dirección/estado — ver BranchWarehouseService.
 */
class BranchController extends Controller
{
    public function __construct(private BranchWarehouseService $warehouses) {}

    public function index()
    {
        $user = auth()->user();
        $query = Branch::with(['company', 'warehouse'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('admin.branches.index', [
            'branches'    => $query->paginate(15),
            'limitStatus' => $this->planLimitStatus($user->getCurrentCompany()?->id, 'branches'),
        ]);
    }

    public function create()
    {
        $user         = auth()->user();
        $isSuperAdmin = $user->is_super_admin;

        if ($this->planLimitReached($user->getCurrentCompany()?->id, 'branches')) {
            return redirect()->route('branches.index')
                ->withErrors(['error' => $this->planLimitMessage('sucursales')]);
        }

        $companies = $isSuperAdmin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter()->values();

        // Empresa por defecto: la elegida en la petición o la primera de la lista.
        $companyId = $isSuperAdmin
            ? ((int) request('company_id') ?: $companies->first()?->id)
            : $user->getCurrentCompany()?->id;

        return view('admin.branches.create', [
            'companies'         => $companies,
            'isSuperAdmin'      => $isSuperAdmin,
            'selectedCompanyId' => $companyId,
        ]);
    }

    public function store()
    {
        $user = auth()->user();
        $companyId = $user->is_super_admin ? (int) request('company_id') : (int) $user->getCurrentCompany()?->id;

        if (empty($companyId)) {
            return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
        }

        if ($this->planLimitReached($companyId, 'branches')) {
            return back()->withInput()->withErrors(['error' => $this->planLimitMessage('sucursales')]);
        }

        // La validación va FUERA del try para que sus errores se muestren en la
        // vista (antes los tragaba el catch genérico).
        $validated = $this->validateBranch($companyId);

        try {
            $this->warehouses->create($companyId, [
                ...$validated,
                'active' => request()->boolean('active', true),
            ]);

            return redirect()->route('branches.index')
                ->with('success', 'Sucursal creada exitosamente, junto con su almacén.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear sucursal', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible crear la sucursal.']);
        }
    }

    public function show(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $branch->load('company', 'warehouse');
        return view('admin.branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $this->authorizeBranch($branch);
        $user = auth()->user();
        $branch->load('warehouse');

        return view('admin.branches.edit', [
            'branch'            => $branch,
            'companies'         => $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter()->values(),
            'isSuperAdmin'      => $user->is_super_admin,
            'selectedCompanyId' => $branch->company_id,
        ]);
    }

    public function update(Branch $branch)
    {
        $this->authorizeBranch($branch);

        $user = auth()->user();
        $companyId = $user->is_super_admin ? (int) request('company_id', $branch->company_id) : (int) $branch->company_id;

        if (empty($companyId)) {
            return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
        }

        $validated = $this->validateBranch($companyId, $branch);

        try {
            $branch->update([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', false),
            ]);
            // El almacén de la sucursal copia nombre / dirección / estado.
            $this->warehouses->sync($branch->fresh());

            return redirect()->route('branches.index')->with('success', 'Sucursal actualizada exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar sucursal', ['branch_id' => $branch->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar la sucursal.']);
        }
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeBranch($branch);

        try {
            $branch->delete();
            return redirect()->route('branches.index')->with('success', 'Sucursal eliminada exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar sucursal', ['branch_id' => $branch->id, 'message' => $exception->getMessage()]);
            return back()->withErrors(['error' => 'No fue posible eliminar la sucursal.']);
        }
    }

    /** Reglas del formulario (sin almacén: lo gestiona el sistema). */
    private function validateBranch(int $companyId, ?Branch $branch = null): array
    {
        $unique = Rule::unique('branches', 'code')->where('company_id', $companyId);
        if ($branch) {
            $unique = $unique->ignore($branch->id);
        }

        $validated = request()->validate([
            'company_id'   => ['nullable', 'exists:companies,id'],
            'name'         => 'required|string|max:255',
            'code'         => ['nullable', 'string', 'max:50', $unique],
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:255',
            'manager_name' => 'nullable|string|max:255',
            'color'        => 'nullable|string|max:7',
        ], [
            'code.unique' => 'Ya existe una sucursal con ese código en esta empresa.',
        ]);

        unset($validated['company_id']);

        return $validated;
    }

    protected function authorizeBranch(Branch $branch): void
    {
        if (!auth()->user()->is_super_admin && $branch->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
