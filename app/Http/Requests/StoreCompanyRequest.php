<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_super_admin;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20|unique:companies,ruc',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la empresa es requerido',
            'ruc.unique' => 'Este RUC ya está registrado',
        ];
    }
}
