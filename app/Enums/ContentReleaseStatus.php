<?php

namespace App\Enums;

enum ContentReleaseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Conceptrelease',
            self::Published => 'Uitgevoerd',
            self::Cancelled => 'Geannuleerd',
        };
    }
}
