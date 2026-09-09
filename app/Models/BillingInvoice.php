<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id',
    'subscription_id',
    'subscription_order_id',
    'subscription_event_id',
    'invoice_year',
    'sequence_number',
    'invoice_number',
    'seller_snapshot',
    'buyer_snapshot',
    'line_description',
    'currency',
    'amount_minor',
    'tax_treatment',
    'tax_rate_basis_points',
    'tax_minor',
    'issued_at',
])]
class BillingInvoice extends Model
{
    public function amountLabel(): string
    {
        return '€ '.number_format($this->amount_minor / 100, 2, ',', '.');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'seller_snapshot' => 'array',
            'buyer_snapshot' => 'array',
            'invoice_year' => 'integer',
            'sequence_number' => 'integer',
            'amount_minor' => 'integer',
            'tax_rate_basis_points' => 'integer',
            'tax_minor' => 'integer',
            'issued_at' => 'immutable_datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SubscriptionOrder::class, 'subscription_order_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(SubscriptionEvent::class, 'subscription_event_id');
    }
}
