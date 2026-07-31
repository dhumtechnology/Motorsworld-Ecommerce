<?php

namespace App\Http\Requests\Shop;

use App\Actions\Shop\GetAvailableAppointmentSlotsAction;
use App\Models\Appointments\ServicePackage;
use App\Models\Products\VehicleModel;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShopAppointmentRequest extends FormRequest
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
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'vehicle_model_id' => ['required', 'integer', 'exists:models,id'],
            'plate' => ['required', 'string', 'max:20'],
            'km' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_document' => ['required', 'string', 'max:20'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'service_package_id' => ['required', 'integer', 'exists:service_packages,id'],
            'customer_email' => ['required', 'email', 'max:255'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'marca',
            'vehicle_model_id' => 'modelo',
            'plate' => 'placa',
            'km' => 'kilometraje',
            'customer_name' => 'nombres y apellidos',
            'customer_document' => 'DNI',
            'customer_phone' => 'teléfono',
            'service_type_id' => 'tipo de servicio',
            'service_package_id' => 'paquete de servicio',
            'customer_email' => 'email',
            'appointment_date' => 'fecha',
            'appointment_time' => 'hora',
            'comments' => 'comentario',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $brandId = (int) $this->input('brand_id');
            $modelId = (int) $this->input('vehicle_model_id');
            $belongs = VehicleModel::query()
                ->whereKey($modelId)
                ->where('brand_id', $brandId)
                ->exists();

            if (! $belongs) {
                $validator->errors()->add('vehicle_model_id', 'El modelo no pertenece a la marca seleccionada.');
            }

            $typeId = (int) $this->input('service_type_id');
            $packageId = (int) $this->input('service_package_id');
            $packageOk = ServicePackage::query()
                ->whereKey($packageId)
                ->where('service_type_id', $typeId)
                ->where('is_active', true)
                ->exists();

            if (! $packageOk) {
                $validator->errors()->add('service_package_id', 'El paquete no es válido para el tipo de servicio.');
            }

            $date = (string) $this->input('appointment_date');
            $time = (string) $this->input('appointment_time');
            $day = Carbon::parse($date);

            if ($day->isWeekend()) {
                $validator->errors()->add('appointment_date', 'Las citas solo están disponibles de lunes a viernes.');

                return;
            }

            $slots = app(GetAvailableAppointmentSlotsAction::class)->execute($date);

            if (! in_array($time, $slots, true)) {
                $validator->errors()->add('appointment_time', 'El horario no está disponible.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function appointmentData(): array
    {
        return [
            'brand_id' => (int) $this->input('brand_id'),
            'vehicle_model_id' => (int) $this->input('vehicle_model_id'),
            'plate' => (string) $this->input('plate'),
            'km' => $this->input('km'),
            'customer_name' => (string) $this->input('customer_name'),
            'customer_document' => (string) $this->input('customer_document'),
            'customer_phone' => (string) $this->input('customer_phone'),
            'service_type_id' => (int) $this->input('service_type_id'),
            'service_package_id' => (int) $this->input('service_package_id'),
            'customer_email' => (string) $this->input('customer_email'),
            'appointment_date' => (string) $this->input('appointment_date'),
            'appointment_time' => (string) $this->input('appointment_time'),
            'comments' => $this->input('comments'),
        ];
    }
}
