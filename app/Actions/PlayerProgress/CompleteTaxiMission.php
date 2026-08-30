<?php

namespace App\Actions\PlayerProgress;

use App\Models\User;
use App\PlayerProgress\PublishedScenarioMission;
use App\PlayerProgress\ScenarioMissionProfile;

final class CompleteTaxiMission
{
    public const SCENARIO_SLUG = 'taxi-diego';

    public const MISSION_KEY = 'mission.madrid.taxi.ride';

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
        $definition = $this->publishedMission->definition(self::SCENARIO_SLUG, self::MISSION_KEY, 'taxi_text_dialogue');
        $profile = new ScenarioMissionProfile(
            stampKey: 'stamp.first_taxi_ride',
            collectibleKey: 'item.madrid_taxi_receipt',
            repairBadgeKey: 'badge.buen_viajero',
            worldStates: [
                'madrid.taxi.mission_completed',
                'madrid.restaurant.preview_unlocked',
            ],
            extraRewards: [[
                'key' => 'madrid.restaurant.preview',
                'type' => 'unlock',
                'title' => ['es' => 'Próxima parada: el restaurante', 'nl' => 'Vooruitblik: het restaurant'],
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
