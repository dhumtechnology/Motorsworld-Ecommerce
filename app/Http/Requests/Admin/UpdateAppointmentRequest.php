<?php

namespace App\Http\Requests\Admin;

use App\Enums\Appointments\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
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
            'appointment_at' => ['required', 'date'],
            'status' => ['required', 'string', Rule::enum(AppointmentStatus::class)],
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'vehicle_model_id' => ['nullable', 'integer', 'exists:models,id'],
            'plate' => ['nullable', 'string', 'max:20'],
            'km' => ['nullable', 'numeric', 'min:0'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_at.required' => 'La fecha y hora son obligatorias.',
            'status.required' => 'Selecciona un estado.',
            'service_type_id.required' => 'Selecciona un tipo de servicio.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function appointmentAttributes(): array
    {
        $plate = trim((string) $this->input('plate', ''));
        $comments = trim((string) $this->input('comments', ''));

        return [
            'appointment_at' => $this->input('appointment_at'),
            'status' => $this->input('status'),
            'service_type_id' => (int) $this->input('service_type_id'),
            'vehicle_model_id' => $this->filled('vehicle_model_id') ? (int) $this->input('vehicle_model_id') : null,
            'plate' => $plate === '' ? null : strtoupper($plate),
            'km' => $this->filled('km') ? $this->input('km') : null,
            'comments' => $comments === '' ? null : $comments,
        ];
    }
}
