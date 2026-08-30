<?php

namespace App\Enums;

enum MediaRightsStatus: string
{
    case Owned = 'owned';
    case Licensed = 'licensed';
    case PublicDomain = 'public_domain';
    case Unknown = 'unknown';
    case InspirationOnly = 'inspiration_only';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Eigen werk',
            self::Licensed => 'Gelicentieerd',
            self::PublicDomain => 'Publiek domein',
            self::Unknown => 'Rechten onbekend',
            self::InspirationOnly => 'Alleen ter inspiratie',
            self::Expired => 'Rechten verlopen',
        };
    }

    public function isPublishable(): bool
    {
        return in_array($this, [self::Owned, self::Licensed, self::PublicDomain], true);
    }
}
