<?php

namespace App\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Enums\CheckoutPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessMolliePayment
{
    public function __construct(
        private readonly MollieApiClient $mollie,
        private readonly ProviderWebhookInbox $inbox,
    ) {}

    public function handle(MolliePaymentSnapshot $snapshot): SubscriptionEvent
    {
        $event = $this->inbox->record($snapshot);

        if (in_array($event->processing_status, ['processed', 'ignored'], true)) {
            return $event;
        }

        $order = SubscriptionOrder::query()
            ->where('provider', 'mollie')
            ->where(function ($query) use ($snapshot): void {
                $query->where('provider_payment_ref', $snapshot->id);

                if ($snapshot->checkoutReference !== null) {
                    $query->orWhere('public_id', $snapshot->checkoutReference);
                }
            })
            ->first();

        if ($order !== null) {
            return $this->processInitialPayment($event, $order, $snapshot);
        }

        if ($snapshot->subscriptionId !== null) {
            return $this->processRecurringPayment($event, $snapshot);
        }

        return $this->finish($event, 'ignored', 'unknown_payment');
    }

    private function processInitialPayment(
        SubscriptionEvent $event,
        SubscriptionOrder $order,
        MolliePaymentSnapshot $snapshot,
    ): SubscriptionEvent {
        if ($order->provider_payment_ref !== null && $order->provider_payment_ref !== $snapshot->id) {
            return $this->finish($event, 'ignored', 'payment_reference_mismatch');
        }

        if ($snapshot->checkoutReference !== null && $snapshot->checkoutReference !== $order->public_id) {
            return $this->finish($event, 'ignored', 'checkout_reference_mismatch');
        }

        if ($snapshot->customerId === null
            || $snapshot->customerId !== $order->provider_customer_ref
            || $snapshot->sequenceType !== 'first'
            || $snapshot->currency !== $order->currency
            || $this->amountMinor($snapshot->amount) !== $order->amount_minor) {
            return $this->finish($event, 'ignored', 'payment_contract_mismatch');
        }

        $status = $this->orderStatus($snapshot);
        $order->forceFill([
            'provider_payment_ref' => $snapshot->id,
            'payment_status' => $status,
            'last_provider_sync_at' => now(),
            'failure_code' => null,
        ])->save();

        if ($status !== CheckoutPaymentStatus::Paid) {
            return $this->finish($event, 'processed');
        }

        if ($snapshot->paidAt === null) {
            throw new BillingProviderUnavailable('Mollie gaf geen betaaldatum terug.');
        }

        try {
            $paidAt = CarbonImmutable::parse($snapshot->paidAt);
        } catch (Throwable) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige betaaldatum terug.');
        }

        $providerSubscriptionId = $order->subscription?->provider_subscription_ref;

        if ($providerSubscriptionId === null) {
            if (! $this->mollie->hasUsableMandate($snapshot->customerId)) {
                throw new BillingProviderUnavailable('Mollie heeft nog geen bruikbaar betaalmandaat bevestigd.');
            }

            $providerSubscriptionId = $this->mollie->findSubscriptionForOrder(
                $snapshot->customerId,
                $order->public_id,
            ) ?? $this->mollie->createSubscription(
                customerId: $snapshot->customerId,
                amountMinor: $order->amount_minor,
                currency: $order->currency,
                startDate: $paidAt->addMonthNoOverflow()->toDateString(),
                description: 'Spaansspreken '.substr($order->public_id, -8),
                webhookUrl: route('billing.mollie.webhook'),
                checkoutReference: $order->public_id,
                idempotencyKey: 'subscription-'.$order->public_id,
            );
        }

        $existingSubscription = Subscription::query()
            ->where('provider', 'mollie')
            ->where('provider_subscription_ref', $providerSubscriptionId)
            ->first();

        if ($existingSubscription !== null
            && ($existingSubscription->user_id !== $order->user_id
                || $existingSubscription->subscription_plan_id !== $order->subscription_plan_id
                || $existingSubscription->provider_customer_ref !== $snapshot->customerId)) {
            return $this->finish($event, 'ignored', 'provider_subscription_conflict');
        }

        return DB::transaction(function () use ($event, $order, $snapshot, $paidAt, $providerSubscriptionId): SubscriptionEvent {
            $lockedOrder = SubscriptionOrder::query()->lockForUpdate()->findOrFail($order->getKey());
            $subscription = Subscription::query()->firstOrCreate(
                [
                    'provider' => 'mollie',
                    'provider_subscription_ref' => $providerSubscriptionId,
                ],
                [
                    'user_id' => $lockedOrder->user_id,
                    'subscription_plan_id' => $lockedOrder->subscription_plan_id,
                    'provider_customer_ref' => $snapshot->customerId,
                    'status' => SubscriptionStatus::Active,
                    'current_period_starts_at' => $paidAt,
                    'current_period_ends_at' => $paidAt->addMonthNoOverflow(),
                    'cancel_at_period_end' => false,
                ],
            );

            $lockedOrder->forceFill([
                'subscription_id' => $subscription->getKey(),
                'payment_status' => CheckoutPaymentStatus::Paid,
                'paid_at' => $paidAt,
                'last_provider_sync_at' => now(),
                'completed_at' => now(),
            ])->save();

            $event->forceFill([
                'subscription_id' => $subscription->getKey(),
                'processing_status' => 'processed',
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();

            return $event->refresh();
        });
    }

    private function processRecurringPayment(
        SubscriptionEvent $event,
        MolliePaymentSnapshot $snapshot,
    ): SubscriptionEvent {
        $subscription = Subscription::query()
            ->where('provider', 'mollie')
            ->where('provider_subscription_ref', $snapshot->subscriptionId)
            ->first();

        if ($subscription === null) {
            return $this->finish($event, 'ignored', 'unknown_subscription');
        }

        $plan = $subscription->plan;

        if ($snapshot->customerId === null
            || $snapshot->customerId !== $subscription->provider_customer_ref
            || $snapshot->sequenceType !== 'recurring'
            || $snapshot->currency !== $plan->currency
            || $this->amountMinor($snapshot->amount) !== $plan->amount_minor) {
            return $this->finish($event, 'ignored', 'subscription_payment_mismatch');
        }

        if ($snapshot->status !== 'paid') {
            return $this->finish($event, 'processed');
        }

        if ($snapshot->paidAt === null) {
            throw new BillingProviderUnavailable('Mollie gaf geen betaaldatum terug.');
        }

        try {
            $paidAt = CarbonImmutable::parse($snapshot->paidAt);
        } catch (Throwable) {
            throw new BillingProviderUnavailable('Mollie gaf een ongeldige betaaldatum terug.');
        }

        DB::transaction(function () use ($subscription, $event, $paidAt): void {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());
            $locked->forceFill([
                'status' => $locked->cancel_at_period_end ? SubscriptionStatus::Cancelled : SubscriptionStatus::Active,
                'current_period_starts_at' => $paidAt,
                'current_period_ends_at' => $paidAt->addMonthNoOverflow(),
                'ended_at' => null,
            ])->save();

            $event->forceFill([
                'subscription_id' => $locked->getKey(),
                'processing_status' => 'processed',
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();
        });

        return $event->refresh();
    }

    private function finish(SubscriptionEvent $event, string $status, ?string $error = null): SubscriptionEvent
    {
        $event->forceFill([
            'processing_status' => $status,
            'processed_at' => now(),
            'processing_error' => $error,
        ])->save();

        return $event->refresh();
    }

    private function orderStatus(MolliePaymentSnapshot $snapshot): CheckoutPaymentStatus
    {
        if ($this->amountMinor($snapshot->amountChargedBack) > 0) {
            return CheckoutPaymentStatus::ChargedBack;
        }

        if ($this->amountMinor($snapshot->amountRefunded) > 0) {
            return CheckoutPaymentStatus::Refunded;
        }

        return CheckoutPaymentStatus::from($snapshot->status);
    }

    private function amountMinor(string $amount): int
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ((int) $whole * 100) + (int) $fraction;
    }
}
