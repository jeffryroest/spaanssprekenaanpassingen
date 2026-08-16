<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'user_id',
    'currency',
    'amount_delta',
    'balance_after',
    'reason_type',
    'reason_id',
    'idempotency_key',
    'metadata',
    'created_at',
])]
class GameLedgerEntry extends Model
{
    protected $table = 'game_ledger';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'amount_delta' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Het spellogboek is append-only.'));
        static::deleting(fn () => throw new LogicException('Spellogboekregels kunnen niet worden verwijderd.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
