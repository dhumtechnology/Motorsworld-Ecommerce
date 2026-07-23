<?php

namespace App\Http\Requests\Admin;

use App\Enums\Inventory\InventoryMovementReason;
use App\Enums\Inventory\InventoryMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInventoryMovementRequest extends FormRequest
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
            'type' => ['required', Rule::enum(InventoryMovementType::class)],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Selecciona el tipo de movimiento.',
            'product_id.required' => 'Selecciona un producto.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'reason.required' => 'Selecciona un motivo.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = InventoryMovementType::tryFrom((string) $this->input('type'));
            $reason = InventoryMovementReason::tryFrom((string) $this->input('reason'));

            if ($type === null || $reason === null) {
                $validator->errors()->add('reason', 'El motivo no es válido.');

                return;
            }

            $allowed = $type === InventoryMovementType::Entry
                ? InventoryMovementReason::forEntries()
                : InventoryMovementReason::forManualExits();

            $allowedValues = array_map(fn (InventoryMovementReason $item) => $item->value, $allowed);

            if (! in_array($reason->value, $allowedValues, true)) {
                $validator->errors()->add('reason', 'El motivo no corresponde al tipo de movimiento.');
            }
        });
    }

    /**
     * @return array{type: InventoryMovementType, product_id: int, quantity: int, reason: InventoryMovementReason, notes: string|null}
     */
    public function movementAttributes(): array
    {
        $notes = trim((string) $this->input('notes', ''));

        return [
            'type' => InventoryMovementType::from((string) $this->input('type')),
            'product_id' => (int) $this->input('product_id'),
            'quantity' => (int) $this->input('quantity'),
            'reason' => InventoryMovementReason::from((string) $this->input('reason')),
            'notes' => $notes !== '' ? $notes : null,
        ];
    }
}
