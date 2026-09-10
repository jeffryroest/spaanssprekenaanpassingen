<?php

namespace App\Enums;

enum AccountDeletionStatus: string
{
    case Requested = 'requested';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'In behandeling',
            self::Blocked => 'Wacht op afhandeling abonnement',
            self::Completed => 'Accountgegevens gewist',
            self::Cancelled => 'Ingetrokken',
        };
    }
}
