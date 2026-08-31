<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

#[Fillable([
    'actor_user_id',
    'action',
    'subject_type',
    'subject_id',
    'before_state',
    'after_state',
    'request_id',
    'ip_hash',
    'user_agent_family',
    'created_at',
])]
class AuditLog extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Auditregels zijn onveranderlijk.'));
        static::deleting(fn () => throw new LogicException('Auditregels kunnen niet worden verwijderd.'));
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    public static function recordContentChange(
        User $actor,
        string $action,
        ContentNode $contentNode,
        ?array $before,
        array $after,
    ): self {
        $context = ['actor_role' => $actor->content_role?->value];

        return self::query()->create([
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => ContentNode::class,
            'subject_id' => $contentNode->getKey(),
            'before_state' => $before === null ? null : $context + ['content' => $before],
            'after_state' => $context + ['content' => $after],
            'request_id' => Str::uuid()->toString(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    public static function recordReleaseChange(
        User $actor,
        string $action,
        ContentRelease $release,
        ?array $before,
        array $after,
    ): self {
        $context = ['actor_role' => $actor->content_role?->value];

        return self::query()->create([
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => ContentRelease::class,
            'subject_id' => $release->getKey(),
            'before_state' => $before === null ? null : $context + ['release' => $before],
            'after_state' => $context + ['release' => $after],
            'request_id' => Str::uuid()->toString(),
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $after */
    public static function recordMediaChange(User $actor, string $action, MediaAsset $asset, array $after): self
    {
        return self::query()->create([
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => MediaAsset::class,
            'subject_id' => $asset->getKey(),
            'before_state' => null,
            'after_state' => [
                'actor_role' => $actor->content_role?->value,
                'media' => $after,
            ],
            'request_id' => Str::uuid()->toString(),
            'created_at' => now(),
        ]);
    }
}
