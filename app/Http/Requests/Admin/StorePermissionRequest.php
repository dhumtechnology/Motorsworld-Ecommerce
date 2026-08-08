<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:permissions,slug', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
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
            'slug.unique' => 'Ya existe un permiso con ese slug.',
            'slug.regex' => 'El slug solo puede contener minúsculas, números, puntos, guiones o guiones bajos.',
        ];
    }

    /**
     * @return array{name: string, slug: string|null, description: string|null}
     */
    public function permissionAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'slug' => $this->filled('slug') ? $this->string('slug')->trim()->toString() : null,
            'description' => $this->filled('description')
                ? $this->string('description')->trim()->toString()
                : null,
        ];
    }
}
