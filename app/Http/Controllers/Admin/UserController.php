<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->is_super_admin) {
            $users = User::paginate(15);
        } else {
            $company = $user->getCurrentCompany();
            $users = $company->users()->paginate(15);
        }

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $user = auth()->user();
        $roles = Role::all();
        $companies = $user->is_super_admin ? Company::all() : $user->companies()->get();

        return view('admin.users.create', compact('roles', 'companies'));
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'phone' => $request->phone,
                'is_super_admin' => $request->boolean('is_super_admin', false),
            ]);

            if ($request->has('companies') && $request->companies) {
                foreach ($request->companies as $companyId => $roleId) {
                    if ($roleId) {
                        $user->companies()->attach($companyId, ['role_id' => $roleId]);
                    }
                }
            }

            return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente');
        } catch (\Throwable $exception) {
            Log::error('Error al crear usuario', [
                'user' => $request->only('name', 'email'),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible crear el usuario.']);
        }
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $authUser = auth()->user();
        $companies = $authUser->is_super_admin ? Company::all() : $authUser->companies()->get();

        return view('admin.users.edit', compact('user', 'roles', 'companies'));
    }

    public function update(StoreUserRequest $request, User $user)
    {
        Log::info('[UserController::update] inicio', [
            'target_user_id' => $user->id,
            'auth_user_id'   => auth()->id(),
            'input_keys'     => array_keys($request->all()),
        ]);

        try {
            $data = $request->validated();

            Log::info('[UserController::update] validated', [
                'data_keys' => array_keys($data),
                'name'      => $data['name'] ?? '(ausente)',
                'email'     => $data['email'] ?? '(ausente)',
                'has_pass'  => !empty($data['password']),
            ]);

            if (!auth()->user()->is_super_admin) {
                unset($data['is_super_admin']);
            }

            if (empty($data['password'])) {
                unset($data['password']);
            }

            $user->update($data);

            Log::info('[UserController::update] guardado OK', ['user_id' => $user->id]);

            return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente');
        } catch (\Throwable $exception) {
            Log::error('[UserController::update] excepción', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el usuario. Revisa los logs para más detalles.']);
        }
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // No permitir eliminar si tiene historial operativo (ventas, cajas, pagos, etc.)
        if ($refs = $user->operationalReferences()) {
            $detail = collect($refs)
                ->map(fn ($count, $label) => "{$label} ({$count})")
                ->implode(', ');

            return redirect()->route('users.index')->with('error',
                "No se puede eliminar el usuario «{$user->name}»: tiene registros asociados — {$detail}. "
                . 'Desactívalo en lugar de eliminarlo para conservar el historial.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente');
    }

    /** Verifica disponibilidad del nombre de usuario (AJAX). */
    public function checkUsername(\Illuminate\Http\Request $request)
    {
        $name   = trim($request->query('name', ''));
        $ignore = (int) $request->query('ignore', 0);

        if ($name === '') {
            return response()->json(['available' => false, 'message' => 'Ingresa un nombre de usuario.']);
        }
        if (preg_match('/\s/', $name)) {
            return response()->json(['available' => false, 'message' => 'No se permiten espacios.']);
        }
        if (mb_strlen($name) < 3) {
            return response()->json(['available' => false, 'message' => 'Mínimo 3 caracteres.']);
        }

        $exists = User::where('name', $name)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists();

        return response()->json([
            'available' => !$exists,
            'message'   => $exists ? 'Este nombre de usuario ya está en uso.' : 'Disponible.',
        ]);
    }

    /**
     * Asignar rol a usuario en una empresa
     */
    public function assignRole(User $user, Company $company, Role $role)
    {
        $authUser = auth()->user();

        if (!$authUser->is_super_admin && $authUser->getCurrentCompany()->id !== $company->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $user->companies()->syncWithoutDetaching([$company->id => ['role_id' => $role->id]]);

        return back()->with('success', 'Rol asignado exitosamente');
    }
}
