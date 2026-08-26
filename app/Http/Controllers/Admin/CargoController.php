<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CargoController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $query = Cargo::with(['company', 'role'])->latest();

        if (!$authUser->is_super_admin) {
            $query->where('company_id', $authUser->getCurrentCompany()?->id);
        }

        $cargos = $query->paginate(15);

        return view('admin.cargos.index', compact('cargos'));
    }

    public function create()
    {
        $authUser = auth()->user();

        return view('admin.cargos.create', [
            'roles' => Role::orderBy('name')->get(),
            'permissions' => Permission::all()->groupBy('module'),
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
            // Módulos del plan para gatear el panel de permisos. super_admin no
            // tiene empresa fija al crear => null (se resuelve por AJAX al elegir).
            'planFeatures' => $authUser->is_super_admin
                ? null
                : $this->planFeaturesFor($authUser->getCurrentCompany()?->id),
        ]);
    }

    /**
     * Módulos efectivos de una empresa para gatear el panel de permisos.
     * Devuelve array de features, o null si no hay empresa (no gatear).
     * Empresa sin suscripción => [] (se gatean todos los módulos de plan).
     */
    private function planFeaturesFor(?int $companyId): ?array
    {
        if (!$companyId) {
            return null;
        }

        return Company::find($companyId)?->effectiveFeatures() ?? [];
    }

    /** AJAX: features del plan de una empresa (para el panel de permisos). */
    public function planFeatures(Request $request)
    {
        return response()->json($this->planFeaturesFor((int) $request->query('company_id')) ?? []);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id')
            : (int) $authUser->getCurrentCompany()?->id;

        try {
            // Al crear un cargo SIEMPRE se genera un rol nuevo con el mismo nombre;
            // por eso no se piden aquí role_mode / role_id / new_role_name.
            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('cargos', 'name')->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
                'description' => ['nullable', 'string', 'max:2000'],
                'active' => ['nullable', 'boolean'],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['exists:permissions,id'],
            ]);

            if (!$companyId) {
                return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
            }

            DB::transaction(function () use ($validated, $companyId, $request) {
                $role = Role::create([
                    'name' => trim($validated['name']),
                    'slug' => $this->uniqueRoleSlug($validated['name']),
                ]);

                if ($request->has('permissions')) {
                    $role->permissions()->sync($validated['permissions'] ?? []);
                }

                Cargo::create([
                    'company_id' => $companyId,
                    'role_id' => $role->id,
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'active' => $request->boolean('active', true),
                ]);
            });

            return redirect()->route('cargos.index')->with('success', 'Cargo creado exitosamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al crear cargo', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible crear el cargo.']);
        }
    }

    /** Genera un slug único para un rol a partir de un nombre. */
    protected function uniqueRoleSlug(string $name): string
    {
        $base = Str::of($name)->lower()->ascii()->replace(' ', '_')->toString() ?: 'role';
        $slug = $base;
        $counter = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = $base . '_' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function edit(Cargo $cargo)
    {
        $this->authorizeCargo($cargo);
        $authUser = auth()->user();

        $cargo->load('role.permissions');

        return view('admin.cargos.edit', [
            'cargo' => $cargo,
            'roles' => Role::orderBy('name')->get(),
            'permissions' => Permission::all()->groupBy('module'),
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
            // El cargo siempre pertenece a una empresa => gateamos por su plan.
            'planFeatures' => $this->planFeaturesFor($cargo->company_id),
        ]);
    }

    public function update(Request $request, Cargo $cargo)
    {
        $this->authorizeCargo($cargo);

        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id', $cargo->company_id)
            : (int) $cargo->company_id;

        try {
            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'role_mode' => ['required', 'in:existing,new'],
                'role_id' => ['required_if:role_mode,existing', 'nullable', 'exists:roles,id'],
                'new_role_name' => ['required_if:role_mode,new', 'nullable', 'string', 'max:255'],
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('cargos', 'name')
                        ->ignore($cargo->id)
                        ->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
                'description' => ['nullable', 'string', 'max:2000'],
                'active' => ['nullable', 'boolean'],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['exists:permissions,id'],
            ]);

            DB::transaction(function () use ($validated, $cargo, $companyId, $request) {
                if ($validated['role_mode'] === 'new') {
                    $role = Role::create([
                        'name' => trim($validated['new_role_name']),
                        'slug' => $this->uniqueRoleSlug($validated['new_role_name']),
                    ]);
                } else {
                    $role = Role::findOrFail($validated['role_id']);
                }

                // Sync permissions to the role
                if ($request->has('permissions')) {
                    $role->permissions()->sync($validated['permissions'] ?? []);
                }

                $cargo->update([
                    'company_id' => $companyId,
                    'role_id' => $role->id,
                    'name' => trim($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'active' => $request->boolean('active', false),
                ]);
            });

            return redirect()->route('cargos.index')->with('success', 'Cargo actualizado exitosamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar cargo', ['cargo_id' => $cargo->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el cargo.']);
        }
    }

    public function destroy(Cargo $cargo)
    {
        $this->authorizeCargo($cargo);

        if ($cargo->personals()->exists()) {
            return back()->withErrors(['error' => 'No puedes eliminar un cargo con personal asignado.']);
        }

        $cargo->delete();

        return redirect()->route('cargos.index')->with('success', 'Cargo eliminado exitosamente.');
    }

    /**
     * API: return permissions for a given role (AJAX).
     */
    public function rolePermissions(Role $role)
    {
        return response()->json($role->permissions->pluck('id'));
    }

    protected function authorizeCargo(Cargo $cargo): void
    {
        $authUser = auth()->user();

        if (!$authUser->is_super_admin && $cargo->company_id !== $authUser->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
