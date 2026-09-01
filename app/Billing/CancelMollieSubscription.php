<?php

namespace App\Billing;

use App\Billing\Exceptions\CheckoutUnavailable;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CancelMollieSubscription
{
    public function __construct(private readonly MollieApiClient $mollie) {}

    public function handle(User $user): Subscription
    {
        $subscription = Subscription::query()
            ->where('user_id', $user->getKey())
            ->where('provider', 'mollie')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Cancelled])
            ->where(function ($query): void {
                $query->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($subscription === null
            || $subscription->provider_customer_ref === null
            || $subscription->provider_subscription_ref === null) {
            throw new CheckoutUnavailable('Er is geen actief Mollie-abonnement om op te zeggen.');
        }

        if ($subscription->cancel_at_period_end) {
            return $subscription;
        }

        $this->mollie->cancelSubscription(
            $subscription->provider_customer_ref,
            $subscription->provider_subscription_ref,
        );

        return DB::transaction(function () use ($subscription): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $locked->forceFill([
                'status' => SubscriptionStatus::Cancelled,
                'cancel_at_period_end' => true,
                'cancelled_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }
}
