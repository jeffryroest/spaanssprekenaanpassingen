<?php

namespace App\Enums;

enum MediaKind: string
{
    case Image = 'image';
    case Audio = 'audio';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Afbeelding',
            self::Audio => 'Audio',
        };
    }
}
