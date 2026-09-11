<?php

namespace App\Enums;

enum AccountSupportCategory: string
{
    case Account = 'account';
    case Access = 'access';
    case Billing = 'billing';
    case Technical = 'technical';
    case Privacy = 'privacy';

    public function label(): string
    {
        return match ($this) {
            self::Account => 'Account',
            self::Access => 'Toegang',
            self::Billing => 'Betaling',
            self::Technical => 'Technisch',
            self::Privacy => 'Privacy',
        };
    }
}
