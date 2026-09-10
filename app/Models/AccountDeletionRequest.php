<?php

namespace App\Models;

use App\Enums\AccountDeletionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'public_id',
    'user_id',
    'requested_by_id',
    'processed_by_id',
    'status',
    'requested_at',
    'processed_at',
    'billing_retained_until',
])]
class AccountDeletionRequest extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AccountDeletionStatus::class,
            'requested_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'billing_retained_until' => 'immutable_date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }
}
