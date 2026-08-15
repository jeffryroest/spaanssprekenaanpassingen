<?php

namespace App\Models;

use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'name',
    'description',
    'target_channel',
    'desired_publish_at',
    'status',
    'owner_user_id',
    'published_by',
    'published_at',
    'cancelled_by',
    'cancelled_at',
    'cancellation_reason',
])]
class ContentRelease extends Model
{
    protected function casts(): array
    {
        return [
            'target_channel' => ContentReleaseChannel::class,
            'status' => ContentReleaseStatus::class,
            'desired_publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ContentRelease $release): void {
            if ($release->getRawOriginal('status') !== ContentReleaseStatus::Draft->value) {
                throw new LogicException('Een uitgevoerde of geannuleerde release is onveranderlijk.');
            }
        });
        static::deleting(fn () => throw new LogicException('Releasehistorie kan niet worden verwijderd.'));
    }

    public function isEditable(): bool
    {
        return $this->status === ContentReleaseStatus::Draft;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContentReleaseItem::class)->orderBy('created_at');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
