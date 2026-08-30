<?php

namespace App\PlayerProgress;

use App\ContentApi\PublishedContentRepository;
use App\ContentApi\RuntimeContentAccess;
use App\Enums\ContentType;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;

final class PublishedScenarioMission
{
    public function __construct(
        private readonly PublishedContentRepository $repository,
        private readonly RuntimeContentAccess $runtimeAccess,
    ) {}

    public function definition(string $scenarioSlug, string $missionKey, string $expectedScene): ScenarioMissionDefinition
    {
        $node = $this->repository->find(ContentType::ConversationScenario, $scenarioSlug);
        $releaseItem = $node === null ? null : $this->repository->latestProductionItem($node);
        $snapshot = $releaseItem?->contentRevision?->snapshot;
        $domainData = is_array($snapshot) ? ($snapshot['domain_data'] ?? null) : null;
        $rewards = is_array($domainData) ? ($domainData['completion']['rewards'] ?? null) : null;

        if ($releaseItem === null
            || ! $this->runtimeAccess->allowsEntitlement($releaseItem, 'trial_week')
            || ! is_array($domainData)
            || ($domainData['schema_version'] ?? null) !== '1.0.0'
            || ($domainData['scene'] ?? null) !== $expectedScene
            || ($domainData['mission']['id'] ?? null) !== $missionKey
            || ($domainData['mission']['required_text_turns'] ?? null) !== 5
            || ! is_array($domainData['steps'] ?? null)
            || ! is_array($domainData['level_branches'] ?? null)
            || ! is_array($rewards)
            || ! is_int($rewards['xp'] ?? null)
            || ($rewards['xp'] ?? 0) < 1
            || ($rewards['valentia'] ?? null) !== 1
        ) {
            throw new ProgressRecordingFailed(
                'mission_definition_unavailable',
                503,
                'De gepubliceerde missie is tijdelijk niet beschikbaar voor accountopslag.',
            );
        }

        return new ScenarioMissionDefinition(
            sourceContentNodeId: (int) $node->getKey(),
            sourceContentVersion: (int) $releaseItem->version,
            domainData: $domainData,
        );
    }
}
