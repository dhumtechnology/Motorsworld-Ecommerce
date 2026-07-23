<?php

namespace App\Http\Requests\Admin;

use App\Enums\Appointments\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentIndexRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::enum(AppointmentStatus::class)],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'mode' => ['nullable', 'string', Rule::in(['list', 'calendar'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search === '' ? null : $search;
    }

    public function status(): ?AppointmentStatus
    {
        $status = $this->input('status');

        if ($status === null || $status === '') {
            return null;
        }

        return AppointmentStatus::tryFrom((string) $status);
    }

    public function serviceTypeId(): ?int
    {
        $value = $this->input('service_type_id');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function mode(): string
    {
        $mode = (string) $this->input('mode', 'list');

        return in_array($mode, ['list', 'calendar'], true) ? $mode : 'list';
    }

    public function month(): Carbon
    {
        $month = $this->input('month');

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        if ($this->selectedDate() !== null) {
            return $this->selectedDate()->copy()->startOfMonth();
        }

        return now()->startOfMonth();
    }

    public function selectedDate(): ?Carbon
    {
        $date = $this->input('date');

        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }

    public function hasActiveFilters(): bool
    {
        return $this->searchTerm() !== null
            || $this->status() !== null
            || $this->serviceTypeId() !== null;
    }
}
