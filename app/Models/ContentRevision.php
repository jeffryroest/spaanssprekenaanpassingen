<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'content_node_id',
    'version',
    'status',
    'snapshot',
    'change_summary',
    'created_by',
    'reviewed_by',
    'reviewed_at',
    'created_at',
])]
class ContentRevision extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => RevisionStatus::class,
            'snapshot' => 'array',
            'reviewed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Contentrevisies zijn onveranderlijk.'));
        static::deleting(fn () => throw new LogicException('Contentrevisies kunnen niet afzonderlijk worden verwijderd.'));
    }

    public function contentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
