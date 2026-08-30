<?php

namespace App\Enums;

enum ContentReviewAction: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Review aangevraagd',
            self::Approved => 'Goedgekeurd',
            self::ChangesRequested => 'Wijzigingen gevraagd',
            self::Withdrawn => 'Review ingetrokken',
        };
    }
}
