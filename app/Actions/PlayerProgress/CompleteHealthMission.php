<?php

namespace App\Actions\PlayerProgress;

use App\Models\User;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;
use App\PlayerProgress\PublishedScenarioMission;
use App\PlayerProgress\ScenarioMissionProfile;

final class CompleteHealthMission
{
    public const SCENARIO_SLUG = 'consulta-elena';

    public const MISSION_KEY = 'mission.madrid.health.appointment';

    public function __construct(
        private readonly PublishedScenarioMission $publishedMission,
        private readonly CompleteScenarioMission $completeMission,
    ) {}

    /**
     * @param  list<array{step_id: string, source: string, assisted: bool}>  $turns
     * @return array<string, mixed>
     */
    public function handle(
        User $user,
        string $completionKey,
        string $level,
        array $turns,
        bool $usedRepairStrategy,
    ): array {
        $definition = $this->publishedMission->definition(self::SCENARIO_SLUG, self::MISSION_KEY, 'health_text_dialogue');
        if (data_get($definition->domainData, 'roleplay.fictional') !== true) {
            throw new ProgressRecordingFailed(
                'mission_definition_unavailable',
                503,
                'De gepubliceerde fictieve rolkaart is tijdelijk niet beschikbaar voor accountopslag.',
            );
        }

        $profile = new ScenarioMissionProfile(
            stampKey: 'stamp.first_consulta_conversation',
            collectibleKey: 'item.consulta_phrase_card',
            repairBadgeKey: 'badge.pregunta_clara',
            worldStates: [
                'madrid.health.mission_completed',
                'madrid.station.preview_unlocked',
            ],
            extraRewards: [[
                'key' => 'madrid.station.preview',
                'type' => 'unlock',
                'title' => ['es' => 'Próxima misión: la estación', 'nl' => 'Vooruitblik: op het station'],
            ]],
        );

        return $this->completeMission->handle(
            user: $user,
            definition: $definition,
            profile: $profile,
            completionKey: $completionKey,
            level: $level,
            turns: $turns,
            usedRepairStrategy: $usedRepairStrategy,
        );
    }
}
