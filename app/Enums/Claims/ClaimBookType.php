<?php

namespace App\Enums\Claims;

enum ClaimBookType: string
{
    case Claim = 'claim';
    case Complaint = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::Claim => 'Reclamo',
            self::Complaint => 'Queja',
        };
    }

    public function labelPlural(): string
    {
        return match ($this) {
            self::Claim => 'Reclamos',
            self::Complaint => 'Quejas',
        };
    }
}
