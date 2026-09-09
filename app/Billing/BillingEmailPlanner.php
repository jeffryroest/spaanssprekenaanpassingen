<?php

namespace App\Billing;

use App\Models\BillingEmailDelivery;
use App\Models\BillingInvoice;
use App\Models\Subscription;
use Carbon\CarbonImmutable;

final class BillingEmailPlanner
{
    public function paymentConfirmed(Subscription $subscription, BillingInvoice $invoice): void
    {
        $this->plan(
            subscription: $subscription,
            kind: 'payment_confirmed',
            cycleKey: $invoice->public_id,
            dueAt: now()->toImmutable(),
            invoice: $invoice,
        );
    }

    public function paymentFailed(Subscription $subscription): void
    {
        $startedAt = $subscription->past_due_since_at;
        $graceEndsAt = $subscription->grace_ends_at;

        if ($startedAt === null || $graceEndsAt === null) {
            return;
        }

        $cycleKey = $graceEndsAt->format('YmdHis');

        foreach (config('subscriptions.recovery_reminder_days', [0, 7, 13]) as $day) {
            $day = max(0, (int) $day);
            $dueAt = $startedAt->addDays($day);

            if ($dueAt->greaterThanOrEqualTo($graceEndsAt)) {
                continue;
            }

            $this->plan(
                subscription: $subscription,
                kind: 'payment_recovery_day_'.$day,
                cycleKey: $cycleKey,
                dueAt: $dueAt,
            );
        }
    }

    public function cancellationConfirmed(Subscription $subscription): void
    {
        $cycleKey = $subscription->cancelled_at?->format('YmdHis') ?? (string) $subscription->getKey();

        $this->plan(
            subscription: $subscription,
            kind: 'subscription_cancelled',
            cycleKey: $cycleKey,
            dueAt: now()->toImmutable(),
        );
    }

    public function cancelRecoveryMessages(Subscription $subscription): void
    {
        BillingEmailDelivery::query()
            ->where('subscription_id', $subscription->getKey())
            ->where('kind', 'like', 'payment_recovery_day_%')
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }

    private function plan(
        Subscription $subscription,
        string $kind,
        string $cycleKey,
        CarbonImmutable $dueAt,
        ?BillingInvoice $invoice = null,
    ): void {
        BillingEmailDelivery::query()->firstOrCreate(
            [
                'subscription_id' => $subscription->getKey(),
                'kind' => $kind,
                'cycle_key' => $cycleKey,
            ],
            [
                'billing_invoice_id' => $invoice?->getKey(),
                'due_at' => $dueAt,
            ],
        );
    }
}
