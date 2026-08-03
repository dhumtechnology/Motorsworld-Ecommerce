<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UpdateBlogPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'body.required' => 'El contenido de la publicación es obligatorio.',
            'image.image' => 'La imagen debe ser un archivo de imagen válido.',
            'is_published.required' => 'Selecciona si la publicación es borrador o publicada.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function blogPostAttributes(): array
    {
        /** @var \App\Models\Content\BlogPost $blogPost */
        $blogPost = $this->route('blogPost');
        $published = $this->boolean('is_published');

        return [
            'title' => trim((string) $this->input('title')),
            'body' => (string) $this->input('body'),
            'is_published' => $published,
            'published_at' => $published
                ? ($blogPost->published_at ?? now())
                : null,
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
