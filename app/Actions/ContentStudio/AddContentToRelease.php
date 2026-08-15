<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AddContentToRelease
{
    public function handle(
        User $actor,
        ContentRelease $release,
        ContentNode $contentNode,
        int $expectedVersion,
    ): ContentRelease {
        Gate::forUser($actor)->authorize('content-studio.publish');

        return DB::transaction(function () use ($actor, $release, $contentNode, $expectedVersion): ContentRelease {
            $lockedRelease = ContentRelease::query()->lockForUpdate()->findOrFail($release->getKey());
            $lockedNode = ContentNode::query()->lockForUpdate()->findOrFail($contentNode->getKey());

            if ($lockedRelease->status !== ContentReleaseStatus::Draft) {
                throw ValidationException::withMessages([
                    'release' => 'Alleen een conceptrelease kan worden samengesteld.',
                ]);
            }

            if ($lockedNode->status !== ContentStatus::Approved) {
                throw ValidationException::withMessages([
                    'content_node_id' => 'Alleen goedgekeurde content kan aan een release worden toegevoegd.',
                ]);
            }

            if ($lockedNode->current_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Deze content is intussen gewijzigd. Vernieuw de pagina en probeer opnieuw.',
                ]);
            }

            $revision = ContentRevision::query()
                ->whereBelongsTo($lockedNode)
                ->where('version', $lockedNode->current_version)
                ->first();

            if ($revision === null || ! $lockedNode->reviews()
                ->where('content_revision_id', $revision->getKey())
                ->where('action', ContentReviewAction::Approved->value)
                ->exists()) {
                throw ValidationException::withMessages([
                    'content_node_id' => 'Voor deze exacte revisie ontbreekt een geldige goedkeuring.',
                ]);
            }

            if ($lockedRelease->items()->where('content_node_id', $lockedNode->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'content_node_id' => 'Deze content staat al in de release.',
                ]);
            }

            $beforeRelease = $this->releaseState($lockedRelease);
            $lockedRelease->items()->create([
                'content_node_id' => $lockedNode->getKey(),
                'content_revision_id' => $revision->getKey(),
                'version' => $lockedNode->current_version,
                'created_by' => $actor->getKey(),
                'created_at' => now(),
            ]);

            $lockedNode->update([
                'status' => ContentStatus::Scheduled,
                'updated_by' => $actor->getKey(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.release_scheduled',
                contentNode: $lockedNode,
                before: $this->contentState($lockedNode, ContentStatus::Approved),
                after: $this->contentState($lockedNode, ContentStatus::Scheduled) + [
                    'release_id' => $lockedRelease->getKey(),
                ],
            );
            AuditLog::recordReleaseChange(
                actor: $actor,
                action: 'content.release_item_added',
                release: $lockedRelease,
                before: $beforeRelease,
                after: $this->releaseState($lockedRelease) + [
                    'content_node_id' => $lockedNode->getKey(),
                    'version' => $lockedNode->current_version,
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
