<?php

namespace App\Http\Requests\Admin;

use App\Models\Products\VehicleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleModelRequest extends FormRequest
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
        /** @var VehicleModel $vehicleModel */
        $vehicleModel = $this->route('vehicleModel');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('models', 'name')
                    ->where(fn ($query) => $query->where('brand_id', (int) $this->input('brand_id')))
                    ->ignore($vehicleModel->id),
            ],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe ese modelo para la marca seleccionada.',
            'brand_id.required' => 'Selecciona una marca.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function modelAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'brand_id' => (int) $this->input('brand_id'),
        ];
    }
}
