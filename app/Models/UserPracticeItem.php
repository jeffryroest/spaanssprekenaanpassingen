<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'practice_key',
    'source_mission_key',
    'source_content_node_id',
    'source_content_version',
    'step_id',
    'interval_days',
    'successful_repetitions',
    'lapse_count',
    'last_rating',
    'due_at',
    'last_practiced_at',
])]
class UserPracticeItem extends Model
{
    protected function casts(): array
    {
        return [
            'source_content_version' => 'integer',
            'interval_days' => 'integer',
            'successful_repetitions' => 'integer',
            'lapse_count' => 'integer',
            'due_at' => 'immutable_datetime',
            'last_practiced_at' => 'immutable_datetime',
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
