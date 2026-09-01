<?php

namespace App\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionEvent;

final class ProviderWebhookInbox
{
    public function record(MolliePaymentSnapshot $snapshot): SubscriptionEvent
    {
        $receivedAt = now();
        $subscriptionId = $snapshot->subscriptionId === null
            ? null
            : Subscription::query()
                ->where('provider', 'mollie')
                ->where('provider_subscription_ref', $snapshot->subscriptionId)
                ->value('id');

        return SubscriptionEvent::query()->firstOrCreate(
            [
                'provider' => 'mollie',
                'provider_event_ref' => $snapshot->eventKey(),
            ],
            [
                'subscription_id' => $subscriptionId,
                'event_type' => 'payment.'.$snapshot->status,
                'event_payload' => $snapshot->safePayload(),
                'occurred_at' => $snapshot->occurredAt() ?? $receivedAt,
                'received_at' => $receivedAt,
                'processing_status' => 'received',
            ],
        );
    }
}
