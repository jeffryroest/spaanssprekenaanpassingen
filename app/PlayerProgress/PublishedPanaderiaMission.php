<?php

namespace App\PlayerProgress;

use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;

final class PublishedPanaderiaMission
{
    private const SCENARIO_SLUG = 'la-espiga-lucia';

    private const MISSION_KEY = 'mission.madrid.panaderia.breakfast';

    public function __construct(private readonly PublishedContentRepository $repository) {}

    public function definition(): PanaderiaMissionDefinition
    {
        $node = $this->repository->find(ContentType::ConversationScenario, self::SCENARIO_SLUG);
        $releaseItem = $node === null ? null : $this->repository->latestProductionItem($node);
        $snapshot = $releaseItem?->contentRevision?->snapshot;
        $domainData = is_array($snapshot) ? ($snapshot['domain_data'] ?? null) : null;

        if (! is_array($domainData)
            || ($domainData['schema_version'] ?? null) !== '1.0.0'
            || ($domainData['mission']['id'] ?? null) !== self::MISSION_KEY
            || ($domainData['mission']['required_text_turns'] ?? null) !== 5
            || ! is_array($domainData['steps'] ?? null)
            || ! is_array($domainData['level_branches'] ?? null)
            || ! is_array($domainData['completion']['rewards'] ?? null)
            || ($domainData['completion']['rewards']['xp'] ?? null) !== PanaderiaMissionDefinition::BASE_XP
            || ($domainData['completion']['rewards']['valentia'] ?? null) !== 1
        ) {
            throw new ProgressRecordingFailed(
                'mission_definition_unavailable',
                503,
                'De gepubliceerde missie is tijdelijk niet beschikbaar voor accountopslag.',
            );
        }

        return new PanaderiaMissionDefinition(
            sourceContentNodeId: (int) $node->getKey(),
            sourceContentVersion: (int) $releaseItem->version,
            domainData: $domainData,
        );
    }
}
