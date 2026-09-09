<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subscription_id',
    'provider',
    'provider_event_ref',
    'event_type',
    'event_payload',
    'occurred_at',
    'received_at',
    'processed_at',
    'processing_status',
    'processing_error',
])]
class SubscriptionEvent extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'event_payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function attentionKind(): ?string
    {
        $payload = $this->event_payload;

        if ($this->positiveAmount($payload['amount_charged_back'] ?? null)) {
            return 'charged_back';
        }

        if ($this->positiveAmount($payload['amount_refunded'] ?? null)) {
            return 'refunded';
        }

        $status = $payload['status'] ?? null;

        if (in_array($status, ['failed', 'canceled', 'expired'], true)) {
            return $status;
        }

        if ($this->processing_status === 'received') {
            return 'unprocessed';
        }

        if ($this->processing_status === 'ignored'
            && ! in_array($this->processing_error, ['unknown_payment', 'unknown_subscription'], true)) {
            return 'manual_check';
        }

        return null;
    }

    public function attentionLabel(): ?string
    {
        return match ($this->attentionKind()) {
            'charged_back' => 'Betaling teruggeboekt',
            'refunded' => 'Betaling terugbetaald',
            'failed' => 'Betaling mislukt',
            'canceled' => 'Betaling geannuleerd',
            'expired' => 'Betaling verlopen',
            'unprocessed' => 'Nog niet verwerkt',
            'manual_check' => 'Handmatige controle nodig',
            default => null,
        };
    }

    private function positiveAmount(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^(0|[1-9][0-9]{0,9})\.[0-9]{2}$/', $value) === 1
            && $value !== '0.00';
    }
}
