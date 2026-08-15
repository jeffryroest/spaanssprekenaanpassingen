<?php

namespace App\Enums;

enum ContentReleaseChannel: string
{
    case Preview = 'preview';
    case Staging = 'staging';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Preview => 'Preview',
            self::Staging => 'Staging',
            self::Production => 'Productie',
        };
    }
}
