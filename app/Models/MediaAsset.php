<?php

namespace App\Models;

use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable([
    'uuid',
    'kind',
    'disk',
    'object_key',
    'original_name',
    'mime_type',
    'byte_size',
    'width',
    'height',
    'checksum_sha256',
    'title',
    'description',
    'alt_text',
    'transcript',
    'source_name',
    'creator_name',
    'license_name',
    'rights_status',
    'rights_expires_at',
    'created_by',
])]
class MediaAsset extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'kind' => MediaKind::class,
            'rights_status' => MediaRightsStatus::class,
            'rights_expires_at' => 'immutable_date',
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Mediametadata is onveranderlijk; maak een gecorrigeerd asset.'));
        static::deleting(fn () => throw new LogicException('Media wordt alleen via een gecontroleerde archiefactie verwijderd.'));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revisions(): BelongsToMany
    {
        return $this->belongsToMany(ContentRevision::class, 'content_media')
            ->withPivot(['content_node_id', 'role', 'sort_order'])
            ->withTimestamps();
    }

    public function hasAccessibilityText(): bool
    {
        return $this->kind === MediaKind::Image
            ? filled($this->alt_text)
            : filled($this->transcript);
    }

    public function isPublishable(): bool
    {
        return $this->rights_status->isPublishable()
            && ($this->rights_expires_at === null || ! $this->rights_expires_at->isBefore(today()))
            && $this->hasAccessibilityText();
    }
}
