<?php

namespace App\Http\Requests\Admin;

use App\Models\Appointments\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceTypeRequest extends FormRequest
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
        /** @var ServiceType $serviceType */
        $serviceType = $this->route('serviceType');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_types', 'name')->ignore($serviceType->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe un servicio con ese nombre.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serviceTypeAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
        ];
    }
}
