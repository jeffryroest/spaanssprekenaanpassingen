<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'mission_key',
    'source_content_node_id',
    'source_content_version',
    'status',
    'completion_count',
    'best_xp',
    'best_spoken_turns',
    'spoken_goal_completed',
    'state_snapshot',
    'first_completed_at',
    'last_completed_at',
])]
class UserMissionProgress extends Model
{
    protected $table = 'user_mission_progress';

    protected function casts(): array
    {
        return [
            'source_content_version' => 'integer',
            'completion_count' => 'integer',
            'best_xp' => 'integer',
            'best_spoken_turns' => 'integer',
            'spoken_goal_completed' => 'boolean',
            'state_snapshot' => 'array',
            'first_completed_at' => 'immutable_datetime',
            'last_completed_at' => 'immutable_datetime',
        ];
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
