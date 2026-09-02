<?php

namespace App\Billing;

use App\Access\EntitlementService;
use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\Exceptions\CheckoutUnavailable;
use App\Enums\CheckoutPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StartMollieCheckout
{
    public function __construct(
        private readonly MollieMonthlyOffer $offer,
        private readonly MollieApiClient $mollie,
        private readonly EntitlementService $entitlements,
    ) {}

    public function handle(User $user, string $firstName, string $lastName, string $email): MollieCheckout
    {
        if (! config('services.mollie.enabled') || ! config('services.mollie.checkout_enabled')) {
            throw new CheckoutUnavailable('Afrekenen is nog niet beschikbaar.');
        }

        $plan = $this->offer->activePlan();

        if ($plan === null) {
            throw new CheckoutUnavailable('Het maandaanbod is tijdelijk niet beschikbaar.');
        }

        if ($this->entitlements->snapshotFor($user)->accessActive) {
            throw new CheckoutUnavailable('Je hebt al toegang. Bekijk daar je abonnementsstatus.');
        }

        $order = DB::transaction(function () use ($user, $plan, $firstName, $lastName, $email): SubscriptionOrder {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($this->entitlements->snapshotFor($lockedUser)->accessActive) {
                throw new CheckoutUnavailable('Je hebt al toegang. Bekijk daar je abonnementsstatus.');
            }

            $hasUnresolvedMollieSubscription = $lockedUser->subscriptions()
                ->where('provider', 'mollie')
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
                ->exists();

            if ($hasUnresolvedMollieSubscription) {
                throw new CheckoutUnavailable('Er bestaat al een Mollie-abonnement. Controleer eerst de abonnementsstatus.');
            }

            $hasOpenOrder = SubscriptionOrder::query()
                ->where('user_id', $user->getKey())
                ->whereIn('payment_status', [
                    CheckoutPaymentStatus::Created,
                    CheckoutPaymentStatus::Open,
                    CheckoutPaymentStatus::Pending,
                    CheckoutPaymentStatus::Authorized,
                ])
                ->exists();

            if ($hasOpenOrder) {
                throw new CheckoutUnavailable('Je hebt al een openstaande betaling. Controleer eerst de betaalstatus.');
            }

            $configuration = $this->offer->configuration();

            return SubscriptionOrder::query()->create([
                'public_id' => (string) Str::ulid(),
                'user_id' => $user->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'provider' => 'mollie',
                'payment_status' => CheckoutPaymentStatus::Created,
                'currency' => $configuration['currency'],
                'amount_minor' => $configuration['amount_minor'],
                'consent_version' => (string) config('subscriptions.checkout_consent_version'),
                'consented_at' => now(),
            ]);
        });

        try {
            $customerId = $this->mollie->createCustomer(
                name: trim($firstName.' '.$lastName),
                email: $email,
                checkoutReference: $order->public_id,
                idempotencyKey: 'customer-'.$order->public_id,
            );

            $createdPayment = $this->mollie->createFirstPayment(
                customerId: $customerId,
                amountMinor: $order->amount_minor,
                currency: $order->currency,
                description: 'Spaansspreken eerste maand '.substr($order->public_id, -8),
                redirectUrl: route('billing.mollie.return', $order),
                cancelUrl: route('billing.mollie.return', $order),
                webhookUrl: route('billing.mollie.webhook'),
                checkoutReference: $order->public_id,
                idempotencyKey: 'payment-'.$order->public_id,
            );
        } catch (BillingProviderUnavailable $exception) {
            $order->forceFill([
                'payment_status' => CheckoutPaymentStatus::Failed,
                'failure_code' => 'provider_unavailable',
                'last_provider_sync_at' => now(),
            ])->save();

            throw $exception;
        }

        $order->forceFill([
            'provider_customer_ref' => $customerId,
            'provider_payment_ref' => $createdPayment->id,
            'payment_status' => CheckoutPaymentStatus::from($createdPayment->status),
            'checkout_started_at' => now(),
            'last_provider_sync_at' => now(),
            'failure_code' => null,
        ])->save();

        return new MollieCheckout($order, $createdPayment->checkoutUrl);
    }
}
