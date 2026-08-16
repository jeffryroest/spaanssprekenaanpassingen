<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

#[Fillable([
    'user_id',
    'subscription_plan_id',
    'provider',
    'provider_customer_ref',
    'provider_subscription_ref',
    'status',
    'trial_starts_at',
    'trial_ends_at',
    'current_period_starts_at',
    'current_period_ends_at',
    'cancel_at_period_end',
    'cancelled_at',
    'ended_at',
])]
class Subscription extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Subscription $subscription): void {
            if ($subscription->status !== SubscriptionStatus::Trialing) {
                return;
            }

            if ($subscription->trial_starts_at === null || $subscription->trial_ends_at === null) {
                throw new InvalidArgumentException('Een proefabonnement vereist een begin- en eindmoment.');
            }

            if ($subscription->trial_ends_at->lessThanOrEqualTo($subscription->trial_starts_at)) {
                throw new InvalidArgumentException('Het einde van een proefperiode moet na het begin liggen.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_starts_at' => 'immutable_datetime',
            'trial_ends_at' => 'immutable_datetime',
            'current_period_starts_at' => 'immutable_datetime',
            'current_period_ends_at' => 'immutable_datetime',
            'cancel_at_period_end' => 'boolean',
            'cancelled_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
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
}
