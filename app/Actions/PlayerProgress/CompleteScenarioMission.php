<?php

namespace App\Actions\PlayerProgress;

use App\Models\GameLedgerEntry;
use App\Models\MissionAttempt;
use App\Models\User;
use App\Models\UserGameState;
use App\Models\UserMissionProgress;
use App\Models\UserReward;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;
use App\PlayerProgress\PlayerProgressSnapshot;
use App\PlayerProgress\ScenarioMissionDefinition;
use App\PlayerProgress\ScenarioMissionProfile;
use Illuminate\Support\Facades\DB;

final class CompleteScenarioMission
{
    public function __construct(private readonly PlayerProgressSnapshot $snapshot) {}

    /**
     * @param  list<array{step_id: string, source: string, assisted: bool}>  $turns
     * @return array<string, mixed>
     */
    public function handle(
        User $user,
        ScenarioMissionDefinition $definition,
        ScenarioMissionProfile $profile,
        string $completionKey,
        string $level,
        array $turns,
        bool $usedRepairStrategy,
    ): array {
        [$attempt, $duplicate] = DB::transaction(function () use (
            $user,
            $definition,
            $profile,
            $completionKey,
            $level,
            $turns,
            $usedRepairStrategy,
        ): array {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $existing = MissionAttempt::query()
                ->where('user_id', $user->getKey())
                ->where('completion_key', $completionKey)
                ->first();
            if ($existing !== null) {
                if ($existing->mission_key !== $definition->missionKey()) {
                    throw new ProgressRecordingFailed(
                        'completion_key_conflict',
                        409,
                        'Deze voltooiingssleutel hoort al bij een andere missie.',
                    );
                }

                return [$existing, true];
            }

            $validated = $definition->validateCompletion($level, $turns);
            $gameState = UserGameState::query()->firstOrCreate(
                ['user_id' => $user->getKey()],
                ['state_version' => 1],
            );
            $missionProgress = UserMissionProgress::query()->firstOrNew([
                'user_id' => $user->getKey(),
                'mission_key' => $definition->missionKey(),
            ]);
            $attemptNumber = ((int) MissionAttempt::query()
                ->where('user_id', $user->getKey())
                ->where('mission_key', $definition->missionKey())
                ->max('attempt_number')) + 1;
            $earnedXp = max(0, $validated->targetXp - (int) ($missionProgress->best_xp ?? 0));
            $earnedConfianza = max(0, $validated->targetConfianza - min(
                ScenarioMissionDefinition::SPOKEN_GOAL,
                (int) ($missionProgress->best_spoken_turns ?? 0),
            ));
            $earnedValentia = (int) ($missionProgress->completion_count ?? 0) === 0
                ? $validated->targetValentia
                : 0;
            $conversationStates = $usedRepairStrategy
                ? array_values(array_unique([...$validated->conversationStates, 'used_repair_strategy']))
                : $validated->conversationStates;
            $completedAt = now();

            $attempt = MissionAttempt::query()->create([
                'user_id' => $user->getKey(),
                'mission_key' => $definition->missionKey(),
                'source_content_node_id' => $definition->sourceContentNodeId,
                'source_content_version' => $definition->sourceContentVersion,
                'attempt_number' => $attemptNumber,
                'completion_key' => $completionKey,
                'status' => 'completed',
                'level' => $level,
                'completed_turns' => $validated->completedTurns,
                'spoken_turns' => $validated->spokenTurns,
                'assist_count' => $validated->assistCount,
                'used_repair_strategy' => $usedRepairStrategy,
                'earned_xp' => $earnedXp,
                'earned_confianza' => $earnedConfianza,
                'earned_valentia' => $earnedValentia,
                'evidence' => [
                    'turns' => $validated->turns,
                    'conversation_states' => $conversationStates,
                    'performance' => [
                        'target_xp' => $validated->targetXp,
                        'target_confianza' => $validated->targetConfianza,
                        'target_valentia' => $validated->targetValentia,
                    ],
                ],
                'completed_at' => $completedAt,
            ]);

            $this->awardCurrency($gameState, $attempt, 'xp', 'total_xp', $earnedXp);
            $this->awardCurrency($gameState, $attempt, 'confianza', 'confianza', $earnedConfianza);
            $this->awardCurrency($gameState, $attempt, 'valentia', 'valentia', $earnedValentia);
            $gameState->forceFill([
                'state_version' => $gameState->state_version + 1,
                'last_learning_date' => today(),
            ])->save();

            $missionProgress->fill([
                'source_content_node_id' => $definition->sourceContentNodeId,
                'source_content_version' => $definition->sourceContentVersion,
                'status' => 'completed',
                'completion_count' => ((int) ($missionProgress->completion_count ?? 0)) + 1,
                'best_xp' => max((int) ($missionProgress->best_xp ?? 0), $validated->targetXp),
                'best_spoken_turns' => max((int) ($missionProgress->best_spoken_turns ?? 0), $validated->spokenTurns),
                'spoken_goal_completed' => (bool) ($missionProgress->spoken_goal_completed ?? false)
                    || $validated->spokenTurns >= ScenarioMissionDefinition::SPOKEN_GOAL,
                'state_snapshot' => [
                    'world_states' => $profile->worldStates,
                    'conversation_states' => $conversationStates,
                ],
                'first_completed_at' => $missionProgress->first_completed_at ?? $completedAt,
                'last_completed_at' => $completedAt,
            ])->save();

            $this->grantReward($user, $attempt, $profile->stampKey, 'passport_stamp', $definition->reward('stamp'));
            $this->grantReward($user, $attempt, $profile->collectibleKey, 'collectible', $definition->reward('collectible'));
            foreach ($profile->extraRewards as $reward) {
                $this->grantReward($user, $attempt, $reward['key'], $reward['type'], $reward['title']);
            }
            if ($usedRepairStrategy) {
                $this->grantReward($user, $attempt, $profile->repairBadgeKey, 'badge', $definition->reward('repair_badge'));
            }

            return [$attempt, false];
        }, attempts: 3);

        return $this->snapshot->forUser(
            user: $user,
            attempt: $attempt,
            duplicate: $duplicate,
            missionKey: $definition->missionKey(),
            spokenGoalTarget: ScenarioMissionDefinition::SPOKEN_GOAL,
        );
    }

    private function awardCurrency(
        UserGameState $state,
        MissionAttempt $attempt,
        string $currency,
        string $balanceField,
        int $amount,
    ): void {
        if ($amount <= 0) {
            return;
        }

        $balanceAfter = (int) $state->{$balanceField} + $amount;
        GameLedgerEntry::query()->create([
            'user_id' => $state->user_id,
            'currency' => $currency,
            'amount_delta' => $amount,
            'balance_after' => $balanceAfter,
            'reason_type' => 'mission_completion',
            'reason_id' => $attempt->getKey(),
            'idempotency_key' => "{$attempt->mission_key}:{$attempt->getKey()}:{$currency}",
            'metadata' => [
                'mission_key' => $attempt->mission_key,
                'source_content_version' => $attempt->source_content_version,
            ],
            'created_at' => now(),
        ]);
        $state->{$balanceField} = $balanceAfter;
    }

    /** @param array{es: string, nl: string} $title */
    private function grantReward(
        User $user,
        MissionAttempt $attempt,
        string $rewardKey,
        string $rewardType,
        array $title,
    ): void {
        UserReward::query()->firstOrCreate([
            'user_id' => $user->getKey(),
            'reward_key' => $rewardKey,
        ], [
            'mission_attempt_id' => $attempt->getKey(),
            'mission_key' => $attempt->mission_key,
            'reward_type' => $rewardType,
            'title_es' => $title['es'],
            'title_nl' => $title['nl'],
            'metadata' => ['source_content_version' => $attempt->source_content_version],
            'first_acquired_at' => now(),
        ]);
    }
}
