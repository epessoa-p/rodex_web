<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cargo;
use App\Models\Company;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonalController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $query = Personal::with(['cargo.role', 'company', 'user', 'branch'])->latest();

        if (!$authUser->is_super_admin) {
            $query->where('company_id', $authUser->getCurrentCompany()?->id);
        }

        $personals = $query->paginate(15);

        return view('admin.personal.index', compact('personals'));
    }

    public function create()
    {
        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin ? request()->integer('company_id') : $authUser->getCurrentCompany()?->id;

        // Sin empresa definida (super_admin que aún no elige una) no se listan cargos:
        // se cargan por AJAX al seleccionar la empresa. Así no se mezclan de varias.
        $cargos = $companyId
            ? Cargo::where('company_id', $companyId)
                ->where('active', true)
                ->with('role')
                ->orderBy('name')
                ->get()
            : collect();

        $branches = $companyId
            ? Branch::where('company_id', $companyId)->where('active', true)->orderBy('name')->get()
            : collect();

        return view('admin.personal.create', [
            'cargos' => $cargos,
            'branches' => $branches,
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
            // Cupo de usuarios del plan: si está lleno, no se puede crear acceso al sistema.
            'userLimitStatus' => $this->planLimitStatus($authUser->getCurrentCompany()?->id, 'users'),
            // Indicador de cupos: siempre los reales de la empresa.
            'userCupoStatus'  => $this->companyLimitStatus($authUser->getCurrentCompany()?->id, 'users'),
        ]);
    }

    /**
     * Devuelve (JSON) los cargos activos de una empresa, para poblar el select
     * de cargo cuando el super_admin elige una empresa en el formulario.
     */
    public function cargosByCompany(Request $request)
    {
        $authUser = auth()->user();

        $companyId = $authUser->is_super_admin
            ? (int) $request->integer('company_id')
            : (int) $authUser->getCurrentCompany()?->id;

        if (!$companyId) {
            return response()->json([]);
        }

        // Un usuario no super_admin solo puede consultar los cargos de SU empresa.
        if (!$authUser->is_super_admin && $companyId !== $authUser->getCurrentCompany()?->id) {
            abort(403);
        }

        $cargos = Cargo::where('company_id', $companyId)
            ->where('active', true)
            ->with('role')
            ->orderBy('name')
            ->get()
            ->map(fn (Cargo $c) => [
                'id'        => $c->id,
                'name'      => $c->name,
                'role_name' => $c->role?->name,
            ]);

        return response()->json($cargos);
    }

    /**
     * Sucursales (JSON) activas de una empresa, para poblar el select de sucursal
     * cuando el super_admin elige una empresa en el formulario.
     */
    public function branchesByCompany(Request $request)
    {
        $authUser = auth()->user();

        $companyId = $authUser->is_super_admin
            ? (int) $request->integer('company_id')
            : (int) $authUser->getCurrentCompany()?->id;

        if (!$companyId) {
            return response()->json([]);
        }

        if (!$authUser->is_super_admin && $companyId !== $authUser->getCurrentCompany()?->id) {
            abort(403);
        }

        $branches = Branch::where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name]);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id')
            : (int) $authUser->getCurrentCompany()?->id;

        // ¿Se crea también un usuario del sistema para este personal?
        $createUser = $request->boolean('create_user');

        // El usuario del sistema cuenta para el límite de "usuarios" del plan.
        if ($createUser && $this->planLimitReached($companyId, 'users')) {
            return back()->withInput()->withErrors(['error' => $this->planLimitMessage('usuarios')]);
        }

        try {
            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'cargo_id' => ['required', 'exists:cargos,id'],
                'branch_id' => ['nullable', 'exists:branches,id'],
                'full_name' => ['required', 'string', 'max:255'],
                'id_number' => ['nullable', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
                // Email/contraseña solo se exigen si se creará acceso al sistema.
                'email' => [$createUser ? 'required' : 'nullable', 'email', 'max:255', 'unique:users,email'],
                'address' => ['nullable', 'string', 'max:255'],
                'hire_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'password' => [$createUser ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
                'active' => ['nullable', 'boolean'],
            ], [
                'full_name.required' => 'El nombre completo es requerido.',
                'cargo_id.required'  => 'Debes seleccionar un cargo.',
                'email.required'     => 'El email es requerido para crear el acceso.',
                'email.unique'       => 'Ese email ya está registrado.',
                'password.required'  => 'La contraseña es requerida para crear el acceso.',
                'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);

            if (!$companyId) {
                return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
            }

            $cargo = Cargo::findOrFail($validated['cargo_id']);
            if ($cargo->company_id !== $companyId) {
                return back()->withInput()->withErrors(['cargo_id' => 'El cargo seleccionado no pertenece a la empresa actual.']);
            }

            $branchId = $this->resolveBranchId($validated['branch_id'] ?? null, $companyId);
            if ($branchId === false) {
                return back()->withInput()->withErrors(['branch_id' => 'La sucursal seleccionada no pertenece a la empresa.']);
            }

            DB::transaction(function () use ($validated, $companyId, $cargo, $branchId, $request, $createUser) {
                $userId = null;

                if ($createUser) {
                    $user = User::create([
                        'name' => $this->makeUniqueUsername($validated['full_name']),
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'phone' => $validated['phone'] ?? null,
                        'active' => $request->boolean('active', true),
                        'is_super_admin' => false,
                    ]);

                    // El usuario hereda el rol del cargo en esta empresa.
                    $user->companies()->syncWithoutDetaching([
                        $companyId => ['role_id' => $cargo->role_id, 'active' => true],
                    ]);

                    $userId = $user->id;
                }

                Personal::create([
                    'company_id' => $companyId,
                    'cargo_id' => $cargo->id,
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'full_name' => trim($validated['full_name']),
                    'id_number' => $validated['id_number'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'active' => $request->boolean('active', true),
                ]);
            });

            return redirect()->route('personal.index')->with('success',
                $createUser
                    ? 'Personal creado con acceso al sistema.'
                    : 'Personal creado (sin acceso al sistema).');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al crear personal', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible crear el personal.']);
        }
    }

    public function edit(Personal $personal)
    {
        $this->authorizePersonal($personal);
        $authUser = auth()->user();

        $companyId = $authUser->is_super_admin
            ? $personal->company_id
            : $authUser->getCurrentCompany()?->id;

        $cargos = Cargo::where('company_id', $companyId)
            ->where('active', true)
            ->with('role')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('company_id', $companyId)->where('active', true)->orderBy('name')->get();

        $personal->load('user', 'cargo.role', 'branch');

        return view('admin.personal.edit', [
            'personal' => $personal,
            'cargos' => $cargos,
            'branches' => $branches,
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
            // Bloqueo del toggle (exento para super_admin).
            'userLimitStatus' => $this->planLimitStatus($companyId, 'users'),
            // Indicador de cupos: siempre los reales de la empresa editada.
            'userCupoStatus'  => $this->companyLimitStatus($companyId, 'users'),
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $this->authorizePersonal($personal);

        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id', $personal->company_id)
            : (int) $personal->company_id;

        $personal->loadMissing('user');
        $hasUser = $personal->user !== null;
        // Si no tiene usuario, se puede crear uno ahora desde el formulario.
        $addUser = !$hasUser && $request->boolean('create_user');

        // Normalizar contraseña vacía: evita que `confirmed` falle por null !== ""
        if (empty($request->input('password'))) {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }

        try {
            $rules = [
                'company_id' => ['nullable', 'exists:companies,id'],
                'cargo_id'   => ['required', 'exists:cargos,id'],
                'branch_id'  => ['nullable', 'exists:branches,id'],
                'full_name'  => ['required', 'string', 'max:255'],
                'id_number'  => ['nullable', 'string', 'max:50'],
                'phone'      => ['nullable', 'string', 'max:30'],
                'address'    => ['nullable', 'string', 'max:255'],
                'hire_date'  => ['nullable', 'date'],
                'notes'      => ['nullable', 'string'],
                'active'     => ['nullable', 'boolean'],
            ];

            if ($hasUser) {
                // Editar el usuario existente.
                $rules['username'] = ['required', 'string', 'min:3', 'max:255', 'regex:/^\S+$/', \Illuminate\Validation\Rule::unique('users', 'name')->ignore($personal->user_id)];
                $rules['email']    = ['required', 'email', 'max:255', 'unique:users,email,' . $personal->user_id];
                $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
            } elseif ($addUser) {
                // Crear un usuario nuevo para este personal.
                $rules['email']    = ['required', 'email', 'max:255', 'unique:users,email'];
                $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            } else {
                // Sin usuario: el email es solo un dato de contacto opcional.
                $rules['email'] = ['nullable', 'email', 'max:255'];
            }

            $validated = $request->validate($rules, [
                'full_name.required' => 'El nombre completo es requerido.',
                'cargo_id.required'  => 'Debes seleccionar un cargo.',
                'email.required'     => 'El email es requerido para el acceso.',
                'email.unique'       => 'Ese email ya está registrado.',
                'password.required'  => 'La contraseña es requerida para crear el acceso.',
                'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'username.required'  => 'El nombre de usuario es requerido.',
                'username.regex'     => 'El nombre de usuario no puede contener espacios.',
                'username.unique'    => 'Este nombre de usuario ya está en uso.',
                'username.min'       => 'El nombre de usuario debe tener al menos 3 caracteres.',
            ]);

            $cargo = Cargo::findOrFail($validated['cargo_id']);
            if ($cargo->company_id !== $companyId) {
                return back()->withInput()->withErrors(['cargo_id' => 'El cargo seleccionado no pertenece a la empresa actual.']);
            }

            $branchId = $this->resolveBranchId($validated['branch_id'] ?? null, $companyId);
            if ($branchId === false) {
                return back()->withInput()->withErrors(['branch_id' => 'La sucursal seleccionada no pertenece a la empresa.']);
            }

            DB::transaction(function () use ($validated, $personal, $cargo, $branchId, $companyId, $request, $hasUser, $addUser) {
                $userId = $personal->user_id;

                if ($hasUser) {
                    $userData = [
                        'name'   => $validated['username'],
                        'email'  => $validated['email'],
                        'phone'  => $validated['phone'] ?? null,
                        'active' => $request->boolean('active', false),
                    ];
                    if (!empty($validated['password'])) {
                        $userData['password'] = Hash::make($validated['password']);
                    }
                    $personal->user->update($userData);
                    $personal->user->companies()->syncWithoutDetaching([
                        $companyId => ['role_id' => $cargo->role_id, 'active' => true],
                    ]);
                } elseif ($addUser) {
                    $user = User::create([
                        'name' => $this->makeUniqueUsername($validated['full_name']),
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'phone' => $validated['phone'] ?? null,
                        'active' => $request->boolean('active', true),
                        'is_super_admin' => false,
                    ]);
                    $user->companies()->syncWithoutDetaching([
                        $companyId => ['role_id' => $cargo->role_id, 'active' => true],
                    ]);
                    $userId = $user->id;
                }

                $personal->update([
                    'company_id' => $companyId,
                    'cargo_id'   => $cargo->id,
                    'branch_id'  => $branchId,
                    'user_id'    => $userId,
                    'full_name'  => trim($validated['full_name']),
                    'id_number'  => $validated['id_number'] ?? null,
                    'phone'      => $validated['phone'] ?? null,
                    'email'      => $validated['email'] ?? null,
                    'address'    => $validated['address'] ?? null,
                    'hire_date'  => $validated['hire_date'] ?? null,
                    'notes'      => $validated['notes'] ?? null,
                    'active'     => $request->boolean('active', false),
                ]);
            });

            return redirect()->route('personal.index')->with('success', 'Personal actualizado exitosamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar personal', ['personal_id' => $personal->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el personal.']);
        }
    }

    public function destroy(Personal $personal)
    {
        $this->authorizePersonal($personal);

        $user = $personal->user;

        // No permitir eliminar si su usuario tiene historial operativo (ventas, cajas, pagos, etc.)
        if ($user && ($refs = $user->operationalReferences())) {
            return redirect()->route('personal.index')
                ->with('error', $this->buildReferencesMessage($personal->full_name, $refs));
        }

        DB::transaction(function () use ($personal, $user) {
            $personal->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('personal.index')->with('success', 'Registro de personal eliminado.');
    }

    /** Construye un mensaje legible con los registros que bloquean la eliminación. */
    protected function buildReferencesMessage(string $name, array $refs): string
    {
        $detail = collect($refs)
            ->map(fn ($count, $label) => "{$label} ({$count})")
            ->implode(', ');

        return "No se puede eliminar a {$name}: su usuario tiene registros asociados — {$detail}. "
            . 'Desactívalo en lugar de eliminarlo para conservar el historial.';
    }

    protected function authorizePersonal(Personal $personal): void
    {
        $authUser = auth()->user();
        if (!$authUser->is_super_admin && $personal->company_id !== $authUser->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    /**
     * Valida la sucursal (opcional): devuelve su id, null (sin sucursal) o
     * false si no pertenece a la empresa (para rebotar con error).
     */
    protected function resolveBranchId($branchId, int $companyId)
    {
        if (empty($branchId)) {
            return null;
        }

        $branch = Branch::find($branchId);

        return ($branch && $branch->company_id === $companyId) ? (int) $branch->id : false;
    }

    protected function makeUniqueUsername(string $fullName): string
    {
        $base = Str::of($fullName)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', '')
            ->trim()
            ->replace(' ', '_')
            ->toString();

        if ($base === '') {
            $base = 'usuario';
        }

        $candidate = $base;
        $counter = 1;

        while (User::where('name', $candidate)->exists()) {
            $candidate = $base . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
