<?php

namespace App\Http\Requests\Admin;

use App\Models\Products\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderBrandsRequest extends FormRequest
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
            'ids.*' => ['integer', 'distinct', 'exists:brands,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = $this->ids();
            $expected = Brand::query()->count();

            if (count($ids) !== $expected) {
                $validator->errors()->add(
                    'ids',
                    'Debes incluir todas las marcas para definir un orden único.',
                );
            }
        });
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', $this->input('ids', []))));
    }
}
