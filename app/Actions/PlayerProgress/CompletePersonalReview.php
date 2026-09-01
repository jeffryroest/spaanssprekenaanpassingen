<?php

namespace App\Actions\PlayerProgress;

use App\Models\GameLedgerEntry;
use App\Models\MissionAttempt;
use App\Models\User;
use App\Models\UserGameState;
use App\Models\UserMissionProgress;
use App\Models\UserPracticeItem;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;
use App\PlayerProgress\PersonalReviewDeck;
use App\PlayerProgress\PlayerProgressSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class CompletePersonalReview
{
    public const MISSION_KEY = 'mission.madrid.review.personal';

    public const SPOKEN_GOAL = 3;

    public function __construct(
        private readonly PersonalReviewDeck $deck,
        private readonly PlayerProgressSnapshot $snapshot,
    ) {}

    /**
     * @param  list<array{practice_key: string, source: string, assisted: bool, rating: string}>  $cards
     * @return array<string, mixed>
     */
    public function handle(User $user, string $completionKey, array $cards): array
    {
        [$attempt, $duplicate, $schedule] = DB::transaction(function () use ($user, $completionKey, $cards): array {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $existing = MissionAttempt::query()
                ->where('user_id', $user->getKey())
                ->where('completion_key', $completionKey)
                ->first();
            if ($existing !== null) {
                if ($existing->mission_key !== self::MISSION_KEY) {
                    throw new ProgressRecordingFailed(
                        'completion_key_conflict',
                        409,
                        'Deze voltooiingssleutel hoort al bij een andere missie.',
                    );
                }

                return [$existing, true, $existing->evidence['cards'] ?? []];
            }

            $available = collect($this->deck->forUser($user)['cards'])->keyBy('practice_key');
            $submittedKeys = array_column($cards, 'practice_key');
            if ($cards === []
                || count($submittedKeys) !== count(array_unique($submittedKeys))
                || collect($submittedKeys)->contains(fn (string $key): bool => ! $available->has($key))) {
                throw new ProgressRecordingFailed(
                    'review_evidence_invalid',
                    422,
                    'Deze herhalingskaarten horen niet bij de actuele persoonlijke sessie.',
                );
            }

            $now = now();
            $schedule = [];
            foreach ($cards as $submitted) {
                $card = $available->get($submitted['practice_key']);
                $item = UserPracticeItem::query()
                    ->where('user_id', $user->getKey())
                    ->where('practice_key', $submitted['practice_key'])
                    ->lockForUpdate()
                    ->first() ?? new UserPracticeItem([
                        'user_id' => $user->getKey(),
                        'practice_key' => $submitted['practice_key'],
                    ]);
                $next = $this->schedule($item, $submitted['rating'], $now);
                $item->fill([
                    'source_mission_key' => $card['source_mission_key'],
                    'source_content_node_id' => $card['source_content_node_id'],
                    'source_content_version' => $card['source_content_version'],
                    'step_id' => $card['step_id'],
                    'interval_days' => $next['interval_days'],
                    'successful_repetitions' => $next['successful_repetitions'],
                    'lapse_count' => $next['lapse_count'],
                    'last_rating' => $submitted['rating'],
                    'due_at' => $next['due_at'],
                    'last_practiced_at' => $now,
                ])->save();
                $schedule[] = [
                    'practice_key' => $submitted['practice_key'],
                    'source_mission_key' => $card['source_mission_key'],
                    'source_content_version' => $card['source_content_version'],
                    'step_id' => $card['step_id'],
                    'source' => $submitted['source'],
                    'assisted' => $submitted['assisted'],
                    'rating' => $submitted['rating'],
                    'interval_days' => $next['interval_days'],
                    'due_at' => $next['due_at']->toAtomString(),
                ];
            }

            $spokenCount = count(array_filter($cards, fn (array $card): bool => $card['source'] === 'speech'));
            $assistCount = count(array_filter($cards, fn (array $card): bool => $card['assisted']));
            $targetXp = min(20, count($cards) * 4);
            $targetConfianza = $spokenCount >= self::SPOKEN_GOAL ? 1 : 0;
            $practiceDate = today()->toDateString();
            $earnedXp = min($targetXp, max(0, 20 - $this->awardedToday($user, $practiceDate, 'xp')));
            $earnedConfianza = min($targetConfianza, max(0, 1 - $this->awardedToday($user, $practiceDate, 'confianza')));
            $attemptNumber = ((int) MissionAttempt::query()
                ->where('user_id', $user->getKey())
                ->where('mission_key', self::MISSION_KEY)
                ->max('attempt_number')) + 1;
            $attempt = MissionAttempt::query()->create([
                'user_id' => $user->getKey(),
                'mission_key' => self::MISSION_KEY,
                'source_content_node_id' => null,
                'source_content_version' => 1,
                'attempt_number' => $attemptNumber,
                'completion_key' => $completionKey,
                'status' => 'completed',
                'level' => 'R',
                'completed_turns' => count($cards),
                'spoken_turns' => $spokenCount,
                'assist_count' => $assistCount,
                'used_repair_strategy' => false,
                'earned_xp' => $earnedXp,
                'earned_confianza' => $earnedConfianza,
                'earned_valentia' => 0,
                'evidence' => [
                    'cards' => $schedule,
                    'performance' => [
                        'target_xp' => $targetXp,
                        'target_confianza' => $targetConfianza,
                        'target_valentia' => 0,
                    ],
                ],
                'completed_at' => $now,
            ]);

            $gameState = UserGameState::query()->firstOrCreate(
                ['user_id' => $user->getKey()],
                ['state_version' => 1],
            );
            $this->award($user, $gameState, $attempt, $completionKey, $practiceDate, 'xp', 'total_xp', $earnedXp);
            $this->award($user, $gameState, $attempt, $completionKey, $practiceDate, 'confianza', 'confianza', $earnedConfianza);
            $gameState->forceFill([
                'state_version' => $gameState->state_version + 1,
                'last_learning_date' => today(),
            ])->save();

            $progress = UserMissionProgress::query()->firstOrNew([
                'user_id' => $user->getKey(),
                'mission_key' => self::MISSION_KEY,
            ]);
            $progress->fill([
                'source_content_node_id' => null,
                'source_content_version' => 1,
                'status' => 'completed',
                'completion_count' => ((int) ($progress->completion_count ?? 0)) + 1,
                'best_xp' => max((int) ($progress->best_xp ?? 0), $targetXp),
                'best_spoken_turns' => max((int) ($progress->best_spoken_turns ?? 0), $spokenCount),
                'spoken_goal_completed' => (bool) ($progress->spoken_goal_completed ?? false)
                    || $spokenCount >= self::SPOKEN_GOAL,
                'state_snapshot' => ['world_states' => ['madrid.review.personal.completed']],
                'first_completed_at' => $progress->first_completed_at ?? $now,
                'last_completed_at' => $now,
            ])->save();

            return [$attempt, false, $schedule];
        }, attempts: 3);

        return $this->snapshot->forUser(
            user: $user,
            attempt: $attempt,
            duplicate: $duplicate,
            missionKey: self::MISSION_KEY,
            spokenGoalTarget: self::SPOKEN_GOAL,
        ) + [
            'review' => [
                'cards' => $schedule,
                'personal_answers_persisted' => false,
                'daily_reward_already_claimed' => $attempt->earned_xp === 0
                    && $attempt->earned_confianza === 0
                    && (int) data_get($attempt->evidence, 'performance.target_xp', 0) > 0,
            ],
        ];
    }

    /** @return array{interval_days: int, successful_repetitions: int, lapse_count: int, due_at: Carbon} */
    private function schedule(UserPracticeItem $item, string $rating, Carbon $now): array
    {
        $currentInterval = (int) ($item->interval_days ?? 0);
        $successful = (int) ($item->successful_repetitions ?? 0);
        $lapses = (int) ($item->lapse_count ?? 0);

        if ($rating === 'again') {
            return [
                'interval_days' => 0,
                'successful_repetitions' => 0,
                'lapse_count' => $lapses + 1,
                'due_at' => $now->copy()->addMinutes(10),
            ];
        }

        $interval = match ($rating) {
            'hard' => $currentInterval === 0 ? 1 : max(1, (int) ceil($currentInterval * 1.5)),
            'good' => $currentInterval === 0 ? 3 : max(3, (int) ceil($currentInterval * 2.2)),
            'easy' => $currentInterval === 0 ? 7 : max(7, (int) ceil($currentInterval * 3)),
        };
        $interval = min($rating === 'easy' ? 90 : 60, $interval);

        return [
            'interval_days' => $interval,
            'successful_repetitions' => $successful + 1,
            'lapse_count' => $lapses,
            'due_at' => $now->copy()->addDays($interval),
        ];
    }

    private function award(
        User $user,
        UserGameState $state,
        MissionAttempt $attempt,
        string $completionKey,
        string $practiceDate,
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
            'reason_type' => 'personal_review',
            'reason_id' => $attempt->getKey(),
            'idempotency_key' => $this->ledgerKey($user, $completionKey, $currency),
            'metadata' => [
                'mission_key' => self::MISSION_KEY,
                'practice_date' => $practiceDate,
            ],
            'created_at' => now(),
        ]);
        $state->{$balanceField} = $balanceAfter;
    }

    private function awardedToday(User $user, string $practiceDate, string $currency): int
    {
        return (int) GameLedgerEntry::query()
            ->where('user_id', $user->getKey())
            ->where('currency', $currency)
            ->where('reason_type', 'personal_review')
            ->whereDate('created_at', $practiceDate)
            ->sum('amount_delta');
    }

    private function ledgerKey(User $user, string $completionKey, string $currency): string
    {
        return "review:{$user->getKey()}:{$completionKey}:{$currency}";
    }
}
