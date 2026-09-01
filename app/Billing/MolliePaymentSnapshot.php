<?php

namespace App\Billing;

final readonly class MolliePaymentSnapshot
{
    public function __construct(
        public string $id,
        public string $status,
        public string $currency,
        public string $amount,
        public string $amountRefunded,
        public string $amountChargedBack,
        public ?string $subscriptionId,
        public ?string $paidAt,
        public ?string $failedAt,
        public ?string $canceledAt,
        public ?string $expiredAt,
    ) {}

    public function eventKey(): string
    {
        return hash('sha256', implode('|', [
            'mollie',
            'payment',
            $this->id,
            $this->status,
            $this->currency,
            $this->amount,
            $this->amountRefunded,
            $this->amountChargedBack,
            $this->subscriptionId ?? '',
            $this->paidAt ?? '',
            $this->failedAt ?? '',
            $this->canceledAt ?? '',
            $this->expiredAt ?? '',
        ]));
    }

    public function occurredAt(): ?string
    {
        return match ($this->status) {
            'paid' => $this->paidAt,
            'failed' => $this->failedAt,
            'canceled' => $this->canceledAt,
            'expired' => $this->expiredAt,
            default => null,
        };
    }

    /** @return array<string, string|null> */
    public function safePayload(): array
    {
        return [
            'payment_id' => $this->id,
            'status' => $this->status,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'amount_refunded' => $this->amountRefunded,
            'amount_charged_back' => $this->amountChargedBack,
            'subscription_id' => $this->subscriptionId,
            'paid_at' => $this->paidAt,
            'failed_at' => $this->failedAt,
            'canceled_at' => $this->canceledAt,
            'expired_at' => $this->expiredAt,
        ];
    }
}
