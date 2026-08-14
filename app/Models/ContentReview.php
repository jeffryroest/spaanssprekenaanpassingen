<?php

namespace App\Models;

use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'content_node_id',
    'content_revision_id',
    'version',
    'action',
    'from_status',
    'to_status',
    'note',
    'actor_user_id',
    'actor_role',
    'created_at',
])]
class ContentReview extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'action' => ContentReviewAction::class,
            'from_status' => ContentStatus::class,
            'to_status' => ContentStatus::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Reviewgebeurtenissen zijn onveranderlijk.'));
        static::deleting(fn () => throw new LogicException('Reviewgebeurtenissen kunnen niet worden verwijderd.'));
    }

    public function contentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class);
    }

    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorRoleLabel(): string
    {
        return $this->actor_role === null
            ? 'Onbekende rol'
            : (ContentRole::tryFrom($this->actor_role)?->label() ?? 'Onbekende rol');
    }
}
