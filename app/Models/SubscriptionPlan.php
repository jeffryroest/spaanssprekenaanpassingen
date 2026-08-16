<?php

namespace App\Models;

use App\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

#[Fillable([
    'code',
    'name',
    'billing_interval',
    'currency',
    'amount_minor',
    'trial_days',
    'provider_price_ref',
    'entitlements',
    'active',
])]
class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (SubscriptionPlan $plan): void {
            if ((int) $plan->amount_minor <= 0) {
                throw new InvalidArgumentException('Een abonnementsbedrag moet groter dan nul zijn.');
            }

            if ((int) $plan->trial_days < 0 || (int) $plan->trial_days > 90) {
                throw new InvalidArgumentException('Een proefperiode moet tussen 0 en 90 dagen liggen.');
            }

            if (preg_match('/^[A-Z]{3}$/', (string) $plan->currency) !== 1) {
                throw new InvalidArgumentException('Valuta moet een ISO-code van drie hoofdletters zijn.');
            }

            $entitlements = $plan->entitlements;
            $validEntitlements = is_array($entitlements) && (array_is_list($entitlements)
                ? collect($entitlements)->every(fn (mixed $value): bool => is_string($value) && $value !== '')
                : collect($entitlements)->every(
                    fn (mixed $enabled, mixed $key): bool => is_string($key) && $key !== '' && is_bool($enabled),
                ));

            if (! $validEntitlements) {
                throw new InvalidArgumentException('Rechten moeten unieke namen of naam/boolean-paren bevatten.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'billing_interval' => BillingInterval::class,
            'amount_minor' => 'integer',
            'trial_days' => 'integer',
            'entitlements' => 'array',
            'active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
