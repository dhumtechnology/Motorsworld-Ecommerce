<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlogPostIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'in:0,1'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function isPublishedFilter(): ?bool
    {
        $value = $this->input('is_published');

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value === '1';
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null || $this->isPublishedFilter() !== null;
    }
}
