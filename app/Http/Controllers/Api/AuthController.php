<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login por token (email o nombre + contraseña). Devuelve el token y las
     * empresas activas del usuario para que la app elija cuál usar.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'string'],   // acepta email o nombre de usuario
            'password' => ['required', 'string'],
            'device'   => ['nullable', 'string', 'max:255'],
        ]);

        $login = trim($data['email']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => ['Tu usuario ha sido desactivado.'],
            ]);
        }

        $token = $user->createToken($data['device'] ?? 'mobile')->plainTextToken;

        $companies = $user->is_super_admin
            ? \App\Models\Company::where('active', true)->orderBy('name')->get()
            : $user->activeCompanies()->get();

        return response()->json([
            'token'        => $token,
            'user'         => $this->userPayload($user),
            'is_super_admin' => (bool) $user->is_super_admin,
            'companies'    => $companies->map(fn ($c) => $this->companyPayload($c))->values(),
        ]);
    }

    /** Revoca el token con el que se hizo la petición. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Contexto de la sesión para la empresa activa (resuelta por el header
     * X-Company-Id vía el middleware api.tenant): usuario, empresa, permisos,
     * features del plan y estado de suscripción. La app lo usa para adaptar el menú.
     */
    public function me(Request $request)
    {
        $user    = $request->user();
        $company = $request->attributes->get('tenant_company');

        $permissions = $company
            ? $company->getPermissionsForUser($user)
            : [];

        $plan = $company?->subscription?->plan;

        $companies = $user->is_super_admin
            ? \App\Models\Company::where('active', true)->orderBy('name')->get()
            : $user->activeCompanies()->get();

        return response()->json([
            'user'           => $this->userPayload($user),
            'is_super_admin' => (bool) $user->is_super_admin,
            'company'        => $company ? $this->companyPayload($company) : null,
            'companies'      => $companies->map(fn ($c) => $this->companyPayload($c))->values(),
            'permissions'    => array_values($permissions),
            'plan'           => $plan ? [
                'name'     => $plan->name,
                'features' => $plan->features ?? [],
            ] : null,
            'subscription'   => $company?->subscription ? [
                'status'      => $company->subscription->effectiveStatus(),
                'allows_write'=> $company->subscription->allowsWrite(),
            ] : null,
        ]);
    }

    // ── Serializadores ────────────────────────────────────────────

    private function userPayload(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }

    private function companyPayload($company): array
    {
        return [
            'id'            => $company->id,
            'name'          => $company->name,
            'currency'      => $company->currency ?: config('inventory.currency', 'Bs'),
            'logo_url'      => $company->logo_url,
            'theme_primary' => $company->theme_primary,
            'theme_accent'  => $company->theme_accent,
            'dashboard_order' => $company->dashboard_order ?: 'ventas,taller,compras',
        ];
    }
}
