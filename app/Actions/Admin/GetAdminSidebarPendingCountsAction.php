<?php

namespace App\Actions\Admin;

use App\Enums\Appointments\AppointmentStatus;
use App\Enums\Claims\ClaimBookStatus;
use App\Enums\Claims\ClaimBookType;
use App\Enums\Contacts\ContactMessageStatus;
use App\Models\Appointments\Appointment;
use App\Models\Claims\ClaimBookEntry;
use App\Models\Contacts\ContactMessage;

class GetAdminSidebarPendingCountsAction
{
    /**
     * @return array{
     *     appointments: int,
     *     complaints: int,
     *     claims: int,
     *     contacts: int
     * }
     */
    public function execute(): array
    {
        return [
            'appointments' => Appointment::query()
                ->where('status', AppointmentStatus::Pending)
                ->count(),
            'complaints' => ClaimBookEntry::query()
                ->ofType(ClaimBookType::Complaint)
                ->where('status', ClaimBookStatus::Pending)
                ->count(),
            'claims' => ClaimBookEntry::query()
                ->ofType(ClaimBookType::Claim)
                ->where('status', ClaimBookStatus::Pending)
                ->count(),
            'contacts' => ContactMessage::query()
                ->where('status', ContactMessageStatus::Pending)
                ->count(),
        ];
    }
}
