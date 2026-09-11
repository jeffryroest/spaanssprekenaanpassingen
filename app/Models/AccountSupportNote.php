<?php

namespace App\Models;

use App\Enums\AccountSupportCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'actor_id',
    'resolved_by_id',
    'category',
    'summary',
    'follow_up_at',
    'resolved_at',
])]
class AccountSupportNote extends Model
{
    protected function casts(): array
    {
        return [
            'category' => AccountSupportCategory::class,
            'follow_up_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }
}
