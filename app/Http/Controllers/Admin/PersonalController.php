<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $query = Personal::with(['cargo.role', 'company', 'user'])->latest();

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

        $cargos = Cargo::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('active', true)
            ->with('role')
            ->orderBy('name')
            ->get();

        return view('admin.personal.create', [
            'cargos' => $cargos,
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id')
            : (int) $authUser->getCurrentCompany()?->id;

        try {
            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'cargo_id' => ['required', 'exists:cargos,id'],
                'full_name' => ['required', 'string', 'max:255'],
                'id_number' => ['nullable', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'address' => ['nullable', 'string', 'max:255'],
                'hire_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'active' => ['nullable', 'boolean'],
            ]);

            if (!$companyId) {
                return back()->withInput()->withErrors(['company_id' => 'Debes seleccionar una empresa.']);
            }

            $cargo = Cargo::findOrFail($validated['cargo_id']);
            if ($cargo->company_id !== $companyId) {
                return back()->withInput()->withErrors(['cargo_id' => 'El cargo seleccionado no pertenece a la empresa actual.']);
            }

            DB::transaction(function () use ($validated, $companyId, $cargo, $request) {
                $username = $this->makeUniqueUsername($validated['full_name']);

                $user = User::create([
                    'name' => $username,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'phone' => $validated['phone'] ?? null,
                    'active' => $request->boolean('active', true),
                    'is_super_admin' => false,
                ]);

                $user->companies()->syncWithoutDetaching([
                    $companyId => [
                        'role_id' => $cargo->role_id,
                        'active' => true,
                    ],
                ]);

                Personal::create([
                    'company_id' => $companyId,
                    'cargo_id' => $cargo->id,
                    'user_id' => $user->id,
                    'full_name' => trim($validated['full_name']),
                    'id_number' => $validated['id_number'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'],
                    'address' => $validated['address'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'active' => $request->boolean('active', true),
                ]);
            });

            return redirect()->route('personal.index')->with('success', 'Personal creado y usuario generado automáticamente.');
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

        $personal->load('user', 'cargo.role');

        return view('admin.personal.edit', [
            'personal' => $personal,
            'cargos' => $cargos,
            'companies' => $authUser->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$authUser->getCurrentCompany()])->filter(),
        ]);
    }

    public function update(Request $request, Personal $personal)
    {
        $this->authorizePersonal($personal);

        $authUser = auth()->user();
        $companyId = $authUser->is_super_admin
            ? (int) $request->input('company_id', $personal->company_id)
            : (int) $personal->company_id;

        // Normalizar contraseña vacía: evita que `confirmed` falle por null !== ""
        if (empty($request->input('password'))) {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }

        try {
            $validated = $request->validate([
                'company_id' => ['nullable', 'exists:companies,id'],
                'cargo_id'   => ['required', 'exists:cargos,id'],
                'full_name'  => ['required', 'string', 'max:255'],
                'id_number'  => ['nullable', 'string', 'max:50'],
                'phone'      => ['nullable', 'string', 'max:30'],
                'username'   => ['required', 'string', 'min:3', 'max:255', 'regex:/^\S+$/', \Illuminate\Validation\Rule::unique('users', 'name')->ignore($personal->user_id)],
                'email'      => ['required', 'email', 'max:255', 'unique:users,email,' . $personal->user_id],
                'address'    => ['nullable', 'string', 'max:255'],
                'hire_date'  => ['nullable', 'date'],
                'notes'      => ['nullable', 'string'],
                'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
                'active'     => ['nullable', 'boolean'],
            ], [
                'username.required' => 'El nombre de usuario es requerido.',
                'username.regex'    => 'El nombre de usuario no puede contener espacios.',
                'username.unique'   => 'Este nombre de usuario ya está en uso.',
                'username.min'      => 'El nombre de usuario debe tener al menos 3 caracteres.',
            ]);

            $cargo = Cargo::findOrFail($validated['cargo_id']);
            if ($cargo->company_id !== $companyId) {
                return back()->withInput()->withErrors(['cargo_id' => 'El cargo seleccionado no pertenece a la empresa actual.']);
            }

            DB::transaction(function () use ($validated, $personal, $cargo, $companyId, $request) {
                $user = $personal->user;

                $userData = [
                    'name'   => $validated['username'],
                    'email'  => $validated['email'],
                    'phone'  => $validated['phone'] ?? null,
                    'active' => $request->boolean('active', false),
                ];

                if (!empty($validated['password'])) {
                    $userData['password'] = Hash::make($validated['password']);
                }

                $user->update($userData);

                $user->companies()->syncWithoutDetaching([
                    $companyId => [
                        'role_id' => $cargo->role_id,
                        'active' => true,
                    ],
                ]);

                $personal->update([
                    'company_id' => $companyId,
                    'cargo_id' => $cargo->id,
                    'full_name' => trim($validated['full_name']),
                    'id_number' => $validated['id_number'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'],
                    'address' => $validated['address'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'active' => $request->boolean('active', false),
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
