<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UpdatePageBannerRequest extends FormRequest
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
            'image' => ['nullable', 'image', 'max:5120'],
            'restore_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.image' => 'La imagen debe ser un archivo de imagen válido.',
            'image.max' => 'La imagen no debe superar los 5 MB.',
        ];
    }

    public function imageFile(): ?UploadedFile
    {
        /** @var UploadedFile|null $file */
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function shouldRestoreDefault(): bool
    {
        return $this->boolean('restore_default');
    }
}
