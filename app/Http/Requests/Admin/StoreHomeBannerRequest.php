<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreHomeBannerRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Debes subir una imagen para el banner.',
            'image.image' => 'La imagen debe ser un archivo de imagen válido.',
            'starts_at.required' => 'La fecha de inicio es obligatoria.',
            'ends_at.after' => 'La fecha de fin debe ser posterior al inicio.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bannerAttributes(): array
    {
        return [
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : null,
            'image' => '',
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $this->input('starts_at'),
            'ends_at' => $this->filled('ends_at') ? $this->input('ends_at') : null,
        ];
    }

    public function imageFile(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }
}
