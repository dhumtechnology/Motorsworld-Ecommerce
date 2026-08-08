<?php

namespace App\Http\Requests\Admin;

use App\Models\Auth\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
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
        /** @var Permission $permission */
        $permission = $this->route('permission');

        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('permissions', 'name')->ignore($permission->id),
            ],
            'slug' => [
                'required',
                'string',
                'max:150',
                Rule::unique('permissions', 'slug')->ignore($permission->id),
                'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/',
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del permiso es obligatorio.',
            'name.unique' => 'Ya existe un permiso con ese nombre.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.unique' => 'Ya existe un permiso con ese slug.',
            'slug.regex' => 'El slug solo puede contener minúsculas, números, puntos, guiones o guiones bajos.',
        ];
    }

    /**
     * @return array{name: string, slug: string, description: string|null}
     */
    public function permissionAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'slug' => $this->string('slug')->trim()->toString(),
            'description' => $this->filled('description')
                ? $this->string('description')->trim()->toString()
                : null,
        ];
    }
}
