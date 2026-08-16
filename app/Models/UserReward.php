<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'user_id',
    'mission_attempt_id',
    'mission_key',
    'reward_key',
    'reward_type',
    'title_es',
    'title_nl',
    'metadata',
    'first_acquired_at',
])]
class UserReward extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'first_acquired_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Beloningstoekenningen zijn onveranderlijk.'));
        static::deleting(fn () => throw new LogicException('Beloningstoekenningen kunnen niet worden verwijderd.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function missionAttempt(): BelongsTo
    {
        return $this->belongsTo(MissionAttempt::class);
    }
}
