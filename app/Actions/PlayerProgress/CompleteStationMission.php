<?php

namespace App\Actions\PlayerProgress;

use App\Models\User;
use App\PlayerProgress\PublishedScenarioMission;
use App\PlayerProgress\ScenarioMissionProfile;

final class CompleteStationMission
{
    public const SCENARIO_SLUG = 'station-nuria';

    public const MISSION_KEY = 'mission.madrid.station.ticket';

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
        $definition = $this->publishedMission->definition(self::SCENARIO_SLUG, self::MISSION_KEY, 'station_text_dialogue');
        $profile = new ScenarioMissionProfile(
            stampKey: 'stamp.first_train_ticket',
            collectibleKey: 'item.toledo_return_ticket',
            repairBadgeKey: 'badge.viajero_atento',
            worldStates: [
                'madrid.station.mission_completed',
                'madrid.final.preview_unlocked',
            ],
            extraRewards: [[
                'key' => 'madrid.final.preview',
                'type' => 'unlock',
                'title' => ['es' => 'Próxima misión: el reto final', 'nl' => 'Vooruitblik: de slotmissie'],
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
