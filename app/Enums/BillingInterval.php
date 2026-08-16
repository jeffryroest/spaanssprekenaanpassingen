<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Month => 'Maandelijks',
            self::Year => 'Jaarlijks',
        };
    }
}
