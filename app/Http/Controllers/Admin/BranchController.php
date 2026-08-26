<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Branch::with('company')->latest();

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

        $warehouses = $companyId
            ? Warehouse::where('company_id', $companyId)->orderBy('name')->get()
            : collect();

        return view('admin.branches.create', [
            'companies'          => $companies,
            'warehouses'         => $warehouses,
            'isSuperAdmin'       => $isSuperAdmin,
            'selectedCompanyId'  => $companyId,
            'canCreateWarehouse' => $isSuperAdmin || $user->hasPermissionInCompany('warehouses.create', $user->getCurrentCompany()),
        ]);
    }

    /**
     * Almacenes (JSON) de una empresa, para poblar el select según la empresa
     * elegida por el super_admin en el formulario de sucursal.
     */
    public function warehousesByCompany()
    {
        $user = auth()->user();

        $companyId = $user->is_super_admin
            ? (int) request()->integer('company_id')
            : (int) $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return response()->json([]);
        }

        // Un usuario no super_admin solo puede consultar los almacenes de SU empresa.
        if (!$user->is_super_admin && $companyId !== $user->getCurrentCompany()?->id) {
            abort(403);
        }

        $warehouses = Warehouse::where('company_id', $companyId)
            ->orderBy('name')
            ->get()
            ->map(fn (Warehouse $w) => [
                'id'   => $w->id,
                'name' => $w->name,
                'code' => $w->code,
            ]);

        return response()->json($warehouses);
    }

    public function store()
    {
        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

            if ($this->planLimitReached($companyId, 'branches')) {
                return back()->withInput()->withErrors(['error' => $this->planLimitMessage('sucursales')]);
            }

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->where('company_id', $companyId)],
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'manager_name' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:7',
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'active' => 'sometimes|boolean',
            ], [
                'code.unique' => 'Ya existe una sucursal con ese código en esta empresa.',
            ]);

            if ($user->is_super_admin && empty($companyId)) {
                return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
            }

            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            if ($warehouse->company_id !== $companyId) {
                abort(403);
            }

            Branch::create([
                'company_id' => $companyId,
                'warehouse_id' => $validated['warehouse_id'],
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'manager_name' => $validated['manager_name'] ?? null,
                'color' => $validated['color'] ?? null,
                'active' => request()->boolean('active', true),
            ]);

            return redirect()->route('branches.index')->with('success', 'Sucursal creada exitosamente.');
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

        return view('admin.branches.edit', [
            'branch' => $branch,
            'companies' => $user->is_super_admin ? Company::orderBy('name')->get() : collect([$user->getCurrentCompany()])->filter()->values(),
            'warehouses' => Warehouse::where('company_id', $branch->company_id)->orderBy('name')->get(),
            'isSuperAdmin' => $user->is_super_admin,
            'selectedCompanyId' => $branch->company_id,
            'canCreateWarehouse' => $user->is_super_admin || $user->hasPermissionInCompany('warehouses.create', $user->getCurrentCompany()),
        ]);
    }

    public function update(Branch $branch)
    {
        $this->authorizeBranch($branch);

        try {
            $user = auth()->user();
            $companyId = $user->is_super_admin ? request('company_id', $branch->company_id) : $branch->company_id;

            $validated = request()->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => 'required|string|max:255',
                'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch->id)->where('company_id', $companyId)],
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'manager_name' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:7',
                'warehouse_id' => ['required', 'exists:warehouses,id'],
                'active' => 'sometimes|boolean',
            ], [
                'code.unique' => 'Ya existe una sucursal con ese código en esta empresa.',
            ]);

            if ($user->is_super_admin && empty($companyId)) {
                return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
            }

            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
            if ($warehouse->company_id !== $companyId) {
                abort(403);
            }

            $branch->update([
                ...$validated,
                'company_id' => $companyId,
                'warehouse_id' => $validated['warehouse_id'],
                'active' => request()->boolean('active', false),
            ]);

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

    protected function authorizeBranch(Branch $branch): void
    {
        if (!auth()->user()->is_super_admin && $branch->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}