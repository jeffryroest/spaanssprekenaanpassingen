<?php

namespace App\ContentStudio;

use App\Enums\ContentType;
use App\Enums\MediaKind;

final class ContentMediaRoles
{
    /** @return array<string, array{label: string, kind: MediaKind, description: string}> */
    public function for(ContentType $contentType): array
    {
        return match ($contentType) {
            ContentType::Region => [
                'map_background' => [
                    'label' => 'Wereldillustratie',
                    'kind' => MediaKind::Image,
                    'description' => 'Achtergrond van de interactieve kaart.',
                ],
                'ambient_audio' => [
                    'label' => 'Omgevingsgeluid',
                    'kind' => MediaKind::Audio,
                    'description' => 'Optioneel, standaard uit en altijd pauzeerbaar.',
                ],
            ],
            ContentType::ConversationScenario => [
                'scene_background' => [
                    'label' => 'Scèneachtergrond',
                    'kind' => MediaKind::Image,
                    'description' => 'De omgeving waarin het gesprek plaatsvindt.',
                ],
                'npc_portrait' => [
                    'label' => 'NPC-portret',
                    'kind' => MediaKind::Image,
                    'description' => 'Het neutrale portret van de gesprekspartner.',
                ],
                'ambient_audio' => [
                    'label' => 'Omgevingsgeluid',
                    'kind' => MediaKind::Audio,
                    'description' => 'Optioneel, standaard uit en altijd pauzeerbaar.',
                ],
            ],
            ContentType::Npc => [
                'npc_portrait' => [
                    'label' => 'NPC-portret',
                    'kind' => MediaKind::Image,
                    'description' => 'Het neutrale portret van het personage.',
                ],
            ],
            default => [],
        };
    }
}
