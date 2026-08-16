<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'user_id',
    'mission_key',
    'source_content_node_id',
    'source_content_version',
    'attempt_number',
    'completion_key',
    'status',
    'level',
    'completed_turns',
    'spoken_turns',
    'assist_count',
    'used_repair_strategy',
    'earned_xp',
    'earned_confianza',
    'earned_valentia',
    'evidence',
    'completed_at',
])]
class MissionAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'source_content_version' => 'integer',
            'attempt_number' => 'integer',
            'completed_turns' => 'integer',
            'spoken_turns' => 'integer',
            'assist_count' => 'integer',
            'used_repair_strategy' => 'boolean',
            'earned_xp' => 'integer',
            'earned_confianza' => 'integer',
            'earned_valentia' => 'integer',
            'evidence' => 'array',
            'completed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Missiepogingen zijn append-only.'));
        static::deleting(fn () => throw new LogicException('Missiepogingen kunnen niet worden verwijderd.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceContentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class, 'source_content_node_id');
    }
}
