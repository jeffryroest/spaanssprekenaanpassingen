<?php

namespace App\Actions\PlayerProgress;

use App\Models\User;
use App\PlayerProgress\PublishedScenarioMission;
use App\PlayerProgress\ScenarioMissionProfile;

final class CompleteFinalMission
{
    public const SCENARIO_SLUG = 'madrid-final-lucia';

    public const MISSION_KEY = 'mission.madrid.week.final';

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
        $definition = $this->publishedMission->definition(self::SCENARIO_SLUG, self::MISSION_KEY, 'final_text_dialogue');
        $profile = new ScenarioMissionProfile(
            stampKey: 'stamp.madrid_week_complete',
            collectibleKey: 'item.madrid_memory_postcard',
            repairBadgeKey: 'badge.sigo_hablando',
            worldStates: [
                'madrid.week.completed',
                'madrid.free_practice.unlocked',
                'spain.next_city.preview_unlocked',
            ],
            extraRewards: [[
                'key' => 'spain.next_city.preview',
                'type' => 'unlock',
                'title' => ['es' => 'Próximo destino', 'nl' => 'Volgende bestemming'],
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
