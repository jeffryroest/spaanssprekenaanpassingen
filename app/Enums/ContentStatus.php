<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Concept',
            self::InReview => 'In review',
            self::ChangesRequested => 'Wijzigingen gevraagd',
            self::Approved => 'Goedgekeurd',
            self::Scheduled => 'Gepland',
            self::Published => 'Gepubliceerd',
            self::Withdrawn => 'Ingetrokken',
            self::Archived => 'Gearchiveerd',
        };
    }
}
