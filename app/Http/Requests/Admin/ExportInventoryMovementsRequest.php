<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportInventoryMovementsRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:inventory_movements,id'],
            'format' => ['required', Rule::in(['xlsx', 'pdf'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Selecciona al menos una fila para exportar.',
            'ids.min' => 'Selecciona al menos una fila para exportar.',
            'format.required' => 'Selecciona el formato de exportación.',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('ids', []))));
    }

    public function format(): string
    {
        return (string) $this->input('format');
    }
}
