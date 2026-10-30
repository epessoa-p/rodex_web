<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_super_admin;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name' => 'required|string|max:255',
            'ruc' => ['nullable', 'string', 'max:20', $this->uniqueRuc($companyId)],
            'currency' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            // Logo de la empresa (white-label): aparece en el menú, recibos e impresiones.
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // Colores base (white-label): menú de navegación y cabecera. Formato #RRGGBB.
            'theme_primary' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'theme_accent'  => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            // Punto de venta: paso del botón "Redondear" y ventas a crédito.
            'pos_rounding_step'  => ['sometimes', 'required', Rule::in(['0.50', '0.5', '1', '1.00'])],
            'allow_credit_sales' => ['sometimes', 'boolean'],
            // Formas de cobro aceptadas y si lo no-efectivo paga gastos.
            'payment_methods'            => ['sometimes', 'array'],
            'payment_methods.*'          => [Rule::in(\App\Models\CashMovement::SALE_METHODS)],
            'expenses_use_other_methods' => ['sometimes', 'boolean'],
            'pos_card_fields'            => ['sometimes', 'array'],
            'pos_card_fields.*'          => ['nullable', Rule::in(array_keys(\App\Models\Company::POS_CARD_FIELDS))],
            // Rubro (motorepuestos / autorepuestos / lubricentro / neumáticos): en el alta decide qué catálogos precargar.
            'business_type' => ['nullable', Rule::in(array_keys(\App\Support\BusinessTypeDefaults::OPTIONS))],
        ] + ($this->isMethod('post') ? $this->onboardingRules() : []);
    }

    /**
     * RUC único contra TODAS las empresas, también las eliminadas: el índice
     * `companies_ruc_unique` las incluye y, si no, el alta revienta con un 1062.
     * Al editar, la propia empresa no choca consigo misma.
     */
    private function uniqueRuc(?int $companyId): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
            $owner = \App\Models\Company::withTrashed()
                ->where('ruc', $value)
                ->when($companyId, fn ($q) => $q->whereKeyNot($companyId))
                ->first(['id', 'name', 'deleted_at']);

            if (! $owner) {
                return;
            }

            $fail($owner->trashed()
                ? "Este RUC pertenece a la empresa eliminada «{$owner->name}» (#{$owner->id}). Usa otro RUC o déjalo vacío."
                : "Este RUC ya está registrado en la empresa «{$owner->name}».");
        };
    }

    /**
     * Alta "lista para usar" (solo al crear): plan, primera sucursal (con su
     * almacén), cargo administrador y primer personal con usuario y caja.
     * Todo opcional; si se indica personal, hace falta sucursal, cargo y acceso.
     */
    private function onboardingRules(): array
    {
        return [
            'plan_id'             => ['nullable', 'exists:plans,id'],
            'subscription_status' => ['nullable', Rule::in(['trial', 'active'])],
            'branch_name'         => ['nullable', 'string', 'max:255', 'required_with:personal_name'],
            'branch_address'      => ['nullable', 'string', 'max:255'],
            'branch_phone'        => ['nullable', 'string', 'max:20'],
            'cargo_name'          => ['nullable', 'string', 'max:150', 'required_with:personal_name'],
            'personal_name'       => ['nullable', 'string', 'max:255', 'required_with:user_email'],
            'personal_phone'      => ['nullable', 'string', 'max:30'],
            'user_email'          => ['nullable', 'email', 'max:255', 'unique:users,email', 'required_with:personal_name'],
            'user_password'       => ['nullable', 'string', 'min:8', 'confirmed', 'required_with:personal_name'],
            'create_register'     => ['nullable', 'boolean'],
            'register_name'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la empresa es requerido',
            'logo.image' => 'El logo debe ser una imagen',
            'logo.max' => 'El logo no puede pesar más de 2 MB',
            'theme_primary.regex' => 'El color principal debe ser un valor hexadecimal (#RRGGBB).',
            'theme_accent.regex' => 'El color de acento debe ser un valor hexadecimal (#RRGGBB).',
            'branch_name.required_with'   => 'Para crear el personal indica la sucursal.',
            'cargo_name.required_with'    => 'Para crear el personal indica el cargo.',
            'personal_name.required_with' => 'Indica el nombre del personal para crear su acceso.',
            'user_email.required_with'    => 'El email es requerido para crear el acceso del personal.',
            'user_email.unique'           => 'Ese email ya está registrado.',
            'user_password.required_with' => 'La contraseña es requerida para crear el acceso.',
            'user_password.min'           => 'La contraseña debe tener al menos 8 caracteres.',
            'user_password.confirmed'     => 'Las contraseñas no coinciden.',
        ];
    }
}
