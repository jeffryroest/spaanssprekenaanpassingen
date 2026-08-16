<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Proefperiode actief',
            self::Active => 'Toegang actief',
            self::PastDue => 'Betaling openstaand',
            self::Paused => 'Toegang gepauzeerd',
            self::Cancelled => 'Opgezegd',
            self::Expired => 'Toegang verlopen',
        };
    }
}
