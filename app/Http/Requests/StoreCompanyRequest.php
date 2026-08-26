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
        // Al editar, el RUC de la propia empresa no debe chocar consigo mismo.
        $companyId = $this->route('company')?->id;

        return [
            'name' => 'required|string|max:255',
            'ruc' => ['nullable', 'string', 'max:20', Rule::unique('companies', 'ruc')->ignore($companyId)],
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la empresa es requerido',
            'ruc.unique' => 'Este RUC ya está registrado',
            'logo.image' => 'El logo debe ser una imagen',
            'logo.max' => 'El logo no puede pesar más de 2 MB',
            'theme_primary.regex' => 'El color principal debe ser un valor hexadecimal (#RRGGBB).',
            'theme_accent.regex' => 'El color de acento debe ser un valor hexadecimal (#RRGGBB).',
        ];
    }
}
