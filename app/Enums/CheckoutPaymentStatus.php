<?php

namespace App\Enums;

enum CheckoutPaymentStatus: string
{
    case Created = 'created';
    case Open = 'open';
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Expired = 'expired';
    case Refunded = 'refunded';
    case ChargedBack = 'charged_back';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Betaallink wordt gemaakt',
            self::Open => 'Wacht op betaling',
            self::Pending => 'Betaling wordt verwerkt',
            self::Authorized => 'Betaling is geautoriseerd',
            self::Paid => 'Betaald',
            self::Failed => 'Betaling mislukt',
            self::Canceled => 'Betaling geannuleerd',
            self::Expired => 'Betaallink verlopen',
            self::Refunded => 'Betaling terugbetaald',
            self::ChargedBack => 'Betaling teruggeboekt',
        };
    }

    public function canRetry(): bool
    {
        return in_array($this, [self::Failed, self::Canceled, self::Expired], true);
    }
}
