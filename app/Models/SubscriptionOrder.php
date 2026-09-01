<?php

namespace App\Models;

use App\Enums\CheckoutPaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id',
    'user_id',
    'subscription_plan_id',
    'subscription_id',
    'first_name',
    'last_name',
    'email',
    'provider',
    'provider_customer_ref',
    'provider_payment_ref',
    'payment_status',
    'currency',
    'amount_minor',
    'consent_version',
    'consented_at',
    'checkout_started_at',
    'paid_at',
    'last_provider_sync_at',
    'completed_at',
    'failure_code',
])]
class SubscriptionOrder extends Model
{
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'payment_status' => CheckoutPaymentStatus::class,
            'amount_minor' => 'integer',
            'consented_at' => 'immutable_datetime',
            'checkout_started_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'last_provider_sync_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id')->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
