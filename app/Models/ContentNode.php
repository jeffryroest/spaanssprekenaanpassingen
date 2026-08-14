<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable([
    'content_type',
    'slug',
    'status',
    'default_locale',
    'schema_version',
    'current_version',
    'published_at',
    'created_by',
    'updated_by',
])]
class ContentNode extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'content_type' => ContentType::class,
            'status' => ContentStatus::class,
            'schema_version' => 'integer',
            'current_version' => 'integer',
            'published_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContentNode $contentNode): void {
            if ($contentNode->status === ContentStatus::Published && $contentNode->published_at === null) {
                throw new LogicException('Gepubliceerde content vereist een publicatietijdstip.');
            }
        });
    }

    public function localizations(): HasMany
    {
        return $this->hasMany(ContentLocalization::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class)->orderBy('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
