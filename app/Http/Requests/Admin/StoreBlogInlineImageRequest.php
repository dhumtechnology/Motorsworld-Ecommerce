<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreBlogInlineImageRequest extends FormRequest
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
            'image' => ['required', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Selecciona una imagen.',
            'image.image' => 'El archivo debe ser una imagen válida.',
            'image.max' => 'La imagen no puede superar 5 MB.',
        ];
    }

    public function imageFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('image');

        return $file;
    }
}
