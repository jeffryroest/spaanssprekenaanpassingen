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
}
