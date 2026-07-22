<?php

namespace App\Http\Requests\Admin;

use App\Models\Products\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
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
        /** @var Brand $brand */
        $brand = $this->route('brand');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->ignore($brand->id),
            ],
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
            'name.unique' => 'Ya existe una marca con ese nombre.',
            'name.required' => 'El nombre es obligatorio.',
            'image.image' => 'La imagen debe ser un archivo de imagen válido.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function brandAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
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
}
