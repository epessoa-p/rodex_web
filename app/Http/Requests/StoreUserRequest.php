<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();
        // Para update (ruta tiene {user}) se acepta users.edit; para store, users.create.
        $isEdit  = (bool) $this->route('user');
        return $user->is_super_admin
            || $user->hasPermissionInCompany('users.create', $company)
            || ($isEdit && $user->hasPermissionInCompany('users.edit', $company));
    }

    protected function prepareForValidation(): void
    {
        // Cuando se edita y se dejan vacíos los campos de contraseña,
        // normalizamos ambos a null para que `confirmed` no falle (null === null).
        if ($this->route('user') && empty($this->input('password'))) {
            $this->merge(['password' => null, 'password_confirmation' => null]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^\S+$/',
                Rule::unique('users', 'name')->ignore($userId),
            ],
            // En edición el email viene como readonly (o puede estar ausente): se valida solo si está presente.
            'email' => [$userId ? 'sometimes' : 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'phone' => 'nullable|string|max:20',
            'is_super_admin' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es requerido',
            'name.regex' => 'El nombre de usuario no puede contener espacios',
            'email.required' => 'El email es requerido',
            'email.unique' => 'Este email ya está registrado',
            'name.unique' => 'Este nombre de usuario ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ];
    }
}
