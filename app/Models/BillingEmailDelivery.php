<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'subscription_id',
    'billing_invoice_id',
    'kind',
    'cycle_key',
    'due_at',
    'sent_at',
    'cancelled_at',
    'attempt_count',
    'last_error_code',
])]
class BillingEmailDelivery extends Model
{
    protected function casts(): array
    {
        return [
            'due_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }
}
