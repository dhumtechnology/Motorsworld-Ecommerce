<?php

namespace App\Http\Requests\Admin;

use App\Models\Content\HomeBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreHomeBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'link_url' => HomeBanner::normalizeLinkUrl($this->input('link_url')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
            'link_url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! HomeBanner::isValidLinkUrl(is_string($value) ? $value : null)) {
                    $fail('Ingresa una URL válida (https://…) o una ruta interna (/catalogo).');
                }
            }],
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
            'link_url' => HomeBanner::normalizeLinkUrl($this->input('link_url')),
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
