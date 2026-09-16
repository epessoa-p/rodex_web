<?php

namespace App\Services\Admin;

use App\Models\Cargo;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Personal;
use App\Models\Plan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Alta de empresa "lista para usar" en un solo paso (super_admin): además de
 * la empresa, opcionalmente su plan, la primera sucursal (con su almacén), el
 * cargo administrador (rol con todos los permisos), el primer personal con su
 * usuario y su caja. Devuelve un resumen con las credenciales para entregar
 * al cliente. Replica exactamente lo que hacen las pantallas individuales
 * (Suscripciones, Sucursales, Cargos, Personal, Cajas).
 */
class CompanyOnboardingService
{
    public function __construct(private BranchWarehouseService $branches) {}

    /**
     * $data: plan_id, subscription_status, branch_name, branch_address,
     * branch_phone, cargo_name, personal_name, personal_phone, user_email,
     * user_password, create_register, register_name (todo opcional).
     */
    public function run(Company $company, array $data, int $actorId): array
    {
        return DB::transaction(function () use ($company, $data, $actorId) {
            $summary = [];

            // ── Plan / suscripción ──
            if (! empty($data['plan_id'])) {
                $plan   = Plan::findOrFail($data['plan_id']);
                $status = $data['subscription_status'] ?? 'trial';
                $company->subscription()->create([
                    'plan_id'            => $plan->id,
                    'status'             => $status,
                    'trial_ends_at'      => $status === 'trial' ? now()->addDays($plan->trial_days ?: 15) : null,
                    'current_period_end' => $status === 'active' ? now()->addMonths($plan->periodMonths()) : null,
                    'grace_days'         => 5,
                    'created_by'         => $actorId,
                ]);
                $summary['plan'] = $plan->name . ' (' . ($status === 'trial' ? 'prueba' : 'activa') . ')';
            }

            // ── Sucursal + almacén ──
            $branch = null;
            if (! empty($data['branch_name'])) {
                $branch = $this->branches->create($company->id, [
                    'name'    => trim($data['branch_name']),
                    'address' => $data['branch_address'] ?? null,
                    'phone'   => $data['branch_phone'] ?? null,
                    'active'  => true,
                ]);
                $summary['branch']    = $branch->name;
                $summary['warehouse'] = $branch->warehouse?->name . ' (' . $branch->warehouse?->code . ')';
            }

            // ── Cargo administrador (rol nuevo con todos los permisos de EMPRESA —
            //    sin Usuarios ni Plantillas, que son de plataforma; los
            //    módulos siguen gateados por el plan) ──
            $cargo = null;
            if (! empty($data['cargo_name'])) {
                $name = trim($data['cargo_name']);
                $role = Role::create(['name' => $name, 'slug' => $this->uniqueRoleSlug($name)]);
                $role->permissions()->sync(Permission::forCompanies()->pluck('id')->all());
                $cargo = Cargo::create([
                    'company_id' => $company->id,
                    'role_id'    => $role->id,
                    'name'       => $name,
                    'active'     => true,
                ]);
                $summary['cargo'] = $cargo->name;
            }

            // ── Personal + usuario (+ caja) ──
            if (! empty($data['personal_name'])) {
                $fullName = trim($data['personal_name']);
                $user = User::create([
                    'name'           => $this->makeUniqueUsername($fullName),
                    'email'          => $data['user_email'],
                    'password'       => Hash::make($data['user_password']),
                    'phone'          => $data['personal_phone'] ?? null,
                    'active'         => true,
                    'is_super_admin' => false,
                ]);
                $user->companies()->syncWithoutDetaching([
                    $company->id => ['role_id' => $cargo?->role_id, 'active' => true],
                ]);

                $personal = Personal::create([
                    'company_id' => $company->id,
                    'cargo_id'   => $cargo?->id,
                    'branch_id'  => $branch?->id,
                    'user_id'    => $user->id,
                    'full_name'  => $fullName,
                    'phone'      => $data['personal_phone'] ?? null,
                    'email'      => $data['user_email'],
                    'hire_date'  => now()->toDateString(),
                    'active'     => true,
                ]);

                $summary['personal'] = $fullName;
                $summary['username'] = $user->name;
                $summary['email']    = $user->email;
                $summary['password'] = $data['user_password'];

                if (! empty($data['create_register']) && $branch) {
                    $register = CashRegister::create([
                        'company_id'           => $company->id,
                        'branch_id'            => $branch->id,
                        'name'                 => trim($data['register_name'] ?? '') ?: 'CAJA ' . $branch->name,
                        'assigned_personal_id' => $personal->id,
                        'active'               => true,
                        'created_by'           => $actorId,
                    ]);
                    $summary['register'] = $register->name;
                }
            }

            return $summary;
        });
    }

    private function uniqueRoleSlug(string $name): string
    {
        $base = Str::of($name)->lower()->ascii()->replace(' ', '_')->toString() ?: 'role';
        $slug = $base;
        $i = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $base . '_' . $i++;
        }

        return $slug;
    }

    private function makeUniqueUsername(string $fullName): string
    {
        $base = Str::of($fullName)->lower()->ascii()
            ->replaceMatches('/[^a-z0-9\s]/', '')->trim()->replace(' ', '_')->toString() ?: 'usuario';
        $candidate = $base;
        $i = 1;
        while (User::where('name', $candidate)->exists()) {
            $candidate = $base . '_' . $i++;
        }

        return $candidate;
    }
}
