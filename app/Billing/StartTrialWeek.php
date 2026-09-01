<?php

namespace App\Billing;

use App\Billing\Exceptions\TrialActivationUnavailable;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class StartTrialWeek
{
    public function __construct(private readonly MollieMonthlyOffer $offer) {}

    public function handle(User $user): Subscription
    {
        if (! config('subscriptions.trial_activation_enabled')) {
            throw new TrialActivationUnavailable('Proefactivatie staat nog niet aan.');
        }

        $plan = $this->offer->activePlan();

        if ($plan === null) {
            throw new TrialActivationUnavailable('Het maandplan is nog niet veilig geïnstalleerd.');
        }

        return DB::transaction(function () use ($user, $plan): Subscription {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $existing = Subscription::query()
                ->where('user_id', $user->getKey())
                ->latest('id')
                ->first();

            if ($existing !== null) {
                throw new TrialActivationUnavailable(
                    $existing->status === SubscriptionStatus::Trialing
                        ? 'Je proefweek is al gestart.'
                        : 'Dit account heeft al een eerdere toegangsperiode.',
                );
            }

            $startsAt = now();

            return Subscription::query()->create([
                'user_id' => $user->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'provider' => 'internal',
                'status' => SubscriptionStatus::Trialing,
                'trial_starts_at' => $startsAt,
                'trial_ends_at' => $startsAt->copy()->addDays($plan->trial_days),
            ]);
        }, 3);
    }
}
