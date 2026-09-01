<?php

namespace App\Billing;

use App\Enums\BillingInterval;
use App\Models\SubscriptionPlan;

final class MollieMonthlyOffer
{
    /** @return array{provider: string, code: string, name: string, billing_interval: string, currency: string, amount_minor: int, trial_days: int, entitlements: array<int, string>} */
    public function configuration(): array
    {
        /** @var array{provider: string, code: string, name: string, billing_interval: string, currency: string, amount_minor: int, trial_days: int, entitlements: array<int, string>} $offer */
        $offer = config('subscriptions.offers.mollie_monthly');

        return $offer;
    }

    public function activePlan(): ?SubscriptionPlan
    {
        $offer = $this->configuration();
        $plan = SubscriptionPlan::query()
            ->where('code', $offer['code'])
            ->where('active', true)
            ->first();

        return $plan !== null && $this->matches($plan) ? $plan : null;
    }

    public function matches(SubscriptionPlan $plan): bool
    {
        $offer = $this->configuration();
        $entitlements = $plan->entitlements ?? [];
        sort($entitlements);
        $expectedEntitlements = $offer['entitlements'];
        sort($expectedEntitlements);

        return $plan->code === $offer['code']
            && $plan->name === $offer['name']
            && $plan->billing_interval === BillingInterval::from($offer['billing_interval'])
            && $plan->currency === $offer['currency']
            && $plan->amount_minor === $offer['amount_minor']
            && $plan->trial_days === $offer['trial_days']
            && $entitlements === $expectedEntitlements
            && $plan->provider_price_ref === null
            && $plan->active;
    }

    /** @return array{provider: string, name: string, price_label: string, interval_label: string, trial_days: int, trial_activation_available: bool} */
    public function presentation(): array
    {
        $offer = $this->configuration();

        return [
            'provider' => ucfirst($offer['provider']),
            'name' => $offer['name'],
            'price_label' => '€ '.number_format($offer['amount_minor'] / 100, 2, ',', '.'),
            'interval_label' => 'per maand',
            'trial_days' => $offer['trial_days'],
            'trial_activation_available' => (bool) config('subscriptions.trial_activation_enabled')
                && $this->activePlan() !== null,
        ];
    }
}
