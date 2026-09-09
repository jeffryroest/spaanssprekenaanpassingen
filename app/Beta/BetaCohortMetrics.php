<?php

namespace App\Beta;

use App\Enums\CheckoutPaymentStatus;
use App\Models\MissionAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Carbon\CarbonImmutable;

final class BetaCohortMetrics
{
    /** @var list<int> */
    public const ALLOWED_PERIODS = [7, 30, 90];

    /**
     * Aggregated beta funnel. No player identifiers or free-form learning data leave this service.
     *
     * @return array{days: int, from: CarbonImmutable, until: CarbonImmutable, stages: list<array{key: string, label: string, count: int, percentage: float}>}
     */
    public function forDays(int $days): array
    {
        $days = in_array($days, self::ALLOWED_PERIODS, true) ? $days : 30;
        $until = CarbonImmutable::now();
        $from = $until->subDays($days);
        $cohortUserIds = fn () => User::query()
            ->select('id')
            ->whereNull('content_role')
            ->whereBetween('created_at', [$from, $until]);

        $accounts = User::query()
            ->whereNull('content_role')
            ->whereBetween('created_at', [$from, $until])
            ->count();
        $trials = Subscription::query()
            ->whereIn('user_id', $cohortUserIds())
            ->whereNotNull('trial_starts_at')
            ->distinct()
            ->count('user_id');
        $learners = MissionAttempt::query()
            ->whereIn('user_id', $cohortUserIds())
            ->distinct()
            ->count('user_id');
        $speakers = MissionAttempt::query()
            ->whereIn('user_id', $cohortUserIds())
            ->where('spoken_turns', '>', 0)
            ->distinct()
            ->count('user_id');
        $finalists = MissionAttempt::query()
            ->whereIn('user_id', $cohortUserIds())
            ->where('mission_key', 'mission.madrid.week.final')
            ->distinct()
            ->count('user_id');
        $customers = SubscriptionOrder::query()
            ->whereIn('user_id', $cohortUserIds())
            ->where('payment_status', CheckoutPaymentStatus::Paid)
            ->distinct()
            ->count('user_id');

        return [
            'days' => $days,
            'from' => $from,
            'until' => $until,
            'stages' => [
                $this->stage('accounts', 'Nieuwe spelers', $accounts, $accounts),
                $this->stage('trials', 'Proefweek gestart', $trials, $accounts),
                $this->stage('learners', 'Minimaal één missie', $learners, $accounts),
                $this->stage('speakers', 'Minimaal één spreekbeurt', $speakers, $accounts),
                $this->stage('finalists', 'Finale voltooid', $finalists, $accounts),
                $this->stage('customers', 'Betalende spelers', $customers, $accounts),
            ],
        ];
    }

    /** @return array{key: string, label: string, count: int, percentage: float} */
    private function stage(string $key, string $label, int $count, int $cohortSize): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'percentage' => $cohortSize === 0 ? 0.0 : round(($count / $cohortSize) * 100, 1),
        ];
    }
}
