<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseStatus;
use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\ContentReleaseItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RemoveContentFromRelease
{
    public function handle(
        User $actor,
        ContentRelease $release,
        ContentReleaseItem $item,
    ): ContentRelease {
        Gate::forUser($actor)->authorize('content-studio.publish');

        return DB::transaction(function () use ($actor, $release, $item): ContentRelease {
            $lockedRelease = ContentRelease::query()->lockForUpdate()->findOrFail($release->getKey());
            $lockedItem = ContentReleaseItem::query()
                ->whereBelongsTo($lockedRelease, 'release')
                ->lockForUpdate()
                ->findOrFail($item->getKey());
            $lockedNode = ContentNode::query()->lockForUpdate()->findOrFail($lockedItem->content_node_id);

            if ($lockedRelease->status !== ContentReleaseStatus::Draft) {
                throw ValidationException::withMessages([
                    'release' => 'Een uitgevoerde of geannuleerde release is onveranderlijk.',
                ]);
            }

            if ($lockedNode->status !== ContentStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'content' => 'De content heeft niet langer de verwachte geplande status.',
                ]);
            }

            $beforeRelease = $this->releaseState($lockedRelease);
            $lockedItem->delete();
            $lockedNode->update([
                'status' => ContentStatus::Approved,
                'updated_by' => $actor->getKey(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.release_unscheduled',
                contentNode: $lockedNode,
                before: $this->contentState($lockedNode, ContentStatus::Scheduled),
                after: $this->contentState($lockedNode, ContentStatus::Approved) + [
                    'release_id' => $lockedRelease->getKey(),
                ],
            );
            AuditLog::recordReleaseChange(
                actor: $actor,
                action: 'content.release_item_removed',
                release: $lockedRelease,
                before: $beforeRelease,
                after: $this->releaseState($lockedRelease) + [
                    'content_node_id' => $lockedNode->getKey(),
                    'version' => $lockedItem->version,
                ],
            );

            return $lockedRelease->refresh()->load(['items.contentNode.localizations', 'owner']);
        });
    }

    /** @return array<string, mixed> */
    private function releaseState(ContentRelease $release): array
    {
        return [
            'status' => $release->status->value,
            'target_channel' => $release->target_channel->value,
            'item_count' => $release->items()->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function contentState(ContentNode $contentNode, ContentStatus $status): array
    {
        return [
            'content_type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'status' => $status->value,
            'version' => $contentNode->current_version,
        ];
    }
}
