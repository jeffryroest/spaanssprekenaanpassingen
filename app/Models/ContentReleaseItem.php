<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'content_release_id',
    'content_node_id',
    'content_revision_id',
    'version',
    'created_by',
    'created_at',
])]
class ContentReleaseItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Release-items zijn versiegebonden en onveranderlijk.'));
        static::deleting(function (ContentReleaseItem $item): void {
            if (! $item->release()->where('status', 'draft')->exists()) {
                throw new LogicException('Items van een uitgevoerde of geannuleerde release kunnen niet worden verwijderd.');
            }
        });
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(ContentRelease::class, 'content_release_id');
    }

    public function contentNode(): BelongsTo
    {
        return $this->belongsTo(ContentNode::class);
    }

    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
