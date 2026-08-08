<?php

namespace App\Http\Requests\Admin;

use App\Models\Auth\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }

    /**
     * @return array{name: string, description: string|null, permission_ids: list<int>}
     */
    public function roleAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'description' => $this->filled('description')
                ? $this->string('description')->trim()->toString()
                : null,
            'permission_ids' => array_map('intval', $this->input('permission_ids', [])),
        ];
    }
}
