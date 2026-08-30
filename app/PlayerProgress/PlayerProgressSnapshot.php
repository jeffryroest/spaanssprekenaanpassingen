<?php

namespace App\PlayerProgress;

use App\Models\MissionAttempt;
use App\Models\User;
use App\Models\UserGameState;
use App\Models\UserMissionProgress;
use App\Models\UserReward;

final class PlayerProgressSnapshot
{
    public const MISSION_KEY = 'mission.madrid.panaderia.breakfast';

    public const TAXI_MISSION_KEY = 'mission.madrid.taxi.ride';

    public const RESTAURANT_MISSION_KEY = 'mission.madrid.restaurant.order';

    public const HEALTH_MISSION_KEY = 'mission.madrid.health.appointment';

    /**
     * @return array<string, mixed>
     */
    public function forUser(
        User $user,
        ?MissionAttempt $attempt = null,
        bool $duplicate = false,
        string $missionKey = self::MISSION_KEY,
        int $spokenGoalTarget = PanaderiaMissionDefinition::SPOKEN_GOAL,
    ): array {
        $state = UserGameState::query()->find($user->getKey());
        $mission = UserMissionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('mission_key', $missionKey)
            ->first();
        $rewards = UserReward::query()
            ->where('user_id', $user->getKey())
            ->where('mission_key', $missionKey)
            ->orderBy('id')
            ->get();
        $performance = $attempt?->evidence['performance'] ?? null;

        return [
            'balances' => [
                'xp' => (int) ($state?->total_xp ?? 0),
                'confianza' => (int) ($state?->confianza ?? 0),
                'valentia' => (int) ($state?->valentia ?? 0),
                'state_version' => (int) ($state?->state_version ?? 1),
            ],
            'mission' => [
                'key' => $missionKey,
                'status' => $mission?->status ?? 'not_started',
                'completion_count' => (int) ($mission?->completion_count ?? 0),
                'best_xp' => (int) ($mission?->best_xp ?? 0),
                'best_spoken_turns' => (int) ($mission?->best_spoken_turns ?? 0),
                'spoken_goal_target' => $spokenGoalTarget,
                'spoken_goal_completed' => (bool) ($mission?->spoken_goal_completed ?? false),
                'first_completed_at' => $mission?->first_completed_at?->toAtomString(),
                'last_completed_at' => $mission?->last_completed_at?->toAtomString(),
                'states' => $mission?->state_snapshot['world_states'] ?? [],
            ],
            'rewards' => $rewards->map(fn (UserReward $reward): array => [
                'key' => $reward->reward_key,
                'type' => $reward->reward_type,
                'title' => [
                    'es' => $reward->title_es,
                    'nl' => $reward->title_nl,
                ],
                'first_acquired_at' => $reward->first_acquired_at->toAtomString(),
            ])->values()->all(),
            'last_attempt' => $attempt === null ? null : [
                'id' => $attempt->getKey(),
                'duplicate' => $duplicate,
                'target_xp' => (int) ($performance['target_xp'] ?? 0),
                'target_confianza' => (int) ($performance['target_confianza'] ?? 0),
                'target_valentia' => (int) ($performance['target_valentia'] ?? 0),
                'spoken_turns' => $attempt->spoken_turns,
                'spoken_goal_completed' => $attempt->spoken_turns >= $spokenGoalTarget,
                'awarded_now' => [
                    'xp' => $duplicate ? 0 : $attempt->earned_xp,
                    'confianza' => $duplicate ? 0 : $attempt->earned_confianza,
                    'valentia' => $duplicate ? 0 : $attempt->earned_valentia,
                ],
            ],
        ];
    }
}
