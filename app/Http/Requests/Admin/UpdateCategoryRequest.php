<?php

namespace App\Http\Requests\Admin;

use App\Models\Products\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'name.required' => 'El nombre es obligatorio.',
            'image.image' => 'La imagen debe ser un archivo de imagen válido.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'description' => $this->nullableString('description'),
        ];
    }

    public function imageFile(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function shouldRemoveImage(): bool
    {
        return $this->boolean('remove_image');
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
