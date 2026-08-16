<?php

namespace App\Actions\PlayerProgress;

use App\Models\User;
use App\PlayerProgress\PublishedScenarioMission;
use App\PlayerProgress\ScenarioMissionProfile;

final class CompleteRestaurantMission
{
    public const SCENARIO_SLUG = 'restaurant-el-reloj';

    public const MISSION_KEY = 'mission.madrid.restaurant.order';

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
        $definition = $this->publishedMission->definition(self::SCENARIO_SLUG, self::MISSION_KEY);
        $profile = new ScenarioMissionProfile(
            stampKey: 'stamp.first_madrid_dinner',
            collectibleKey: 'item.el_reloj_coaster',
            repairBadgeKey: 'badge.con_soltura',
            worldStates: [
                'madrid.restaurant.mission_completed',
                'madrid.health.preview_unlocked',
            ],
            extraRewards: [[
                'key' => 'madrid.health.preview',
                'type' => 'unlock',
                'title' => ['es' => 'Próxima misión: la consulta', 'nl' => 'Vooruitblik: bij de dokter'],
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
