<?php

namespace App\Access;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EntitlementService
{
    public function snapshotFor(User $user, ?CarbonImmutable $at = null): EntitlementSnapshot
    {
        $at ??= now()->toImmutable();
        $subscriptions = $user->subscriptions()
            ->with('plan')
            ->latest('created_at')
            ->latest('id')
            ->get();
        $effective = $subscriptions->first(
            fn (Subscription $subscription): bool => $this->isEffective($subscription, $at),
        );
        $display = $effective ?? $subscriptions->first();

        if ($display === null) {
            return new EntitlementSnapshot('none', false, [], null, null, null, null, null);
        }

        $accessActive = $effective !== null;
        $validUntil = $this->validUntil($display);
        $state = $accessActive ? $display->status->value : $this->inactiveState($display, $at);
        $trialDay = $accessActive && $display->status === SubscriptionStatus::Trialing
            ? $this->trialDay($display, $at)
            : null;

        return new EntitlementSnapshot(
            state: $state,
            accessActive: $accessActive,
            entitlements: $accessActive ? $this->entitlements($display) : [],
            planCode: $display->plan?->code,
            planName: $display->plan?->name,
            validUntil: $validUntil,
            trialDay: $trialDay,
            trialDays: $display->plan?->trial_days,
        );
    }

    private function isEffective(Subscription $subscription, CarbonImmutable $at): bool
    {
        if ($subscription->ended_at !== null && $subscription->ended_at->lessThanOrEqualTo($at)) {
            return false;
        }

        return match ($subscription->status) {
            SubscriptionStatus::Trialing => $subscription->trial_starts_at !== null
                && $subscription->trial_ends_at !== null
                && $subscription->trial_starts_at->lessThanOrEqualTo($at)
                && $subscription->trial_ends_at->greaterThan($at),
            SubscriptionStatus::Active => ($subscription->current_period_starts_at === null
                    || $subscription->current_period_starts_at->lessThanOrEqualTo($at))
                && ($subscription->current_period_ends_at === null
                    || $subscription->current_period_ends_at->greaterThan($at)),
            SubscriptionStatus::PastDue => $this->pastDueAccessEndsAt($subscription)?->greaterThan($at) ?? false,
            SubscriptionStatus::Cancelled => $subscription->current_period_ends_at?->greaterThan($at) ?? false,
            SubscriptionStatus::Paused, SubscriptionStatus::Expired => false,
        };
    }

    private function validUntil(Subscription $subscription): ?CarbonImmutable
    {
        return match ($subscription->status) {
            SubscriptionStatus::Trialing => $subscription->trial_ends_at,
            SubscriptionStatus::PastDue => $this->pastDueAccessEndsAt($subscription),
            SubscriptionStatus::Active, SubscriptionStatus::Cancelled => $subscription->current_period_ends_at,
            SubscriptionStatus::Paused, SubscriptionStatus::Expired => $subscription->ended_at
                ?? $subscription->current_period_ends_at
                ?? $subscription->trial_ends_at,
        };
    }

    private function pastDueAccessEndsAt(Subscription $subscription): ?CarbonImmutable
    {
        $periodEnd = $subscription->current_period_ends_at;
        if ($periodEnd === null) {
            return null;
        }

        return $periodEnd->addDays(max(0, (int) config('subscriptions.past_due_grace_days', 0)));
    }

    private function inactiveState(Subscription $subscription, CarbonImmutable $at): string
    {
        if (in_array($subscription->status, [SubscriptionStatus::Paused, SubscriptionStatus::Expired], true)) {
            return $subscription->status->value;
        }

        $validUntil = $this->validUntil($subscription);

        return $validUntil !== null && $validUntil->lessThanOrEqualTo($at)
            ? SubscriptionStatus::Expired->value
            : $subscription->status->value;
    }

    private function trialDay(Subscription $subscription, CarbonImmutable $at): int
    {
        $trialDays = max(1, (int) ($subscription->plan?->trial_days ?? 1));
        $elapsedDays = (int) floor($subscription->trial_starts_at->diffInDays($at));

        return min($trialDays, max(1, $elapsedDays + 1));
    }

    /** @return list<string> */
    private function entitlements(Subscription $subscription): array
    {
        $raw = $subscription->plan?->entitlements ?? [];
        $values = array_is_list($raw)
            ? $raw
            : Collection::make($raw)->filter()->keys()->all();

        return Collection::make($values)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
