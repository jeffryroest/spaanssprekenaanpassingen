<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseStatus;
use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CancelContentRelease
{
    public function handle(User $actor, ContentRelease $release, string $reason): ContentRelease
    {
        Gate::forUser($actor)->authorize('content-studio.publish');

        $validated = Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $release, $validated): ContentRelease {
            $lockedRelease = ContentRelease::query()->lockForUpdate()->findOrFail($release->getKey());
            $lockedRelease->load('items');

            if ($lockedRelease->status !== ContentReleaseStatus::Draft) {
                throw ValidationException::withMessages([
                    'release' => 'Alleen een conceptrelease kan worden geannuleerd.',
                ]);
            }

            $beforeRelease = $this->releaseState($lockedRelease);

            foreach ($lockedRelease->items as $item) {
                $contentNode = ContentNode::query()->lockForUpdate()->findOrFail($item->content_node_id);

                if ($contentNode->status !== ContentStatus::Scheduled) {
                    throw ValidationException::withMessages([
                        'release' => "Content {$contentNode->slug} heeft niet langer de verwachte geplande status.",
                    ]);
                }

                $contentNode->update([
                    'status' => ContentStatus::Approved,
                    'updated_by' => $actor->getKey(),
                ]);

                AuditLog::recordContentChange(
                    actor: $actor,
                    action: 'content.release_unscheduled',
                    contentNode: $contentNode,
                    before: $this->contentState($contentNode, ContentStatus::Scheduled),
                    after: $this->contentState($contentNode, ContentStatus::Approved) + [
                        'release_id' => $lockedRelease->getKey(),
                        'reason' => $validated['reason'],
                    ],
                );
            }

            $lockedRelease->update([
                'status' => ContentReleaseStatus::Cancelled,
                'cancelled_by' => $actor->getKey(),
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ]);

            AuditLog::recordReleaseChange(
                actor: $actor,
                action: 'content.release_cancelled',
                release: $lockedRelease,
                before: $beforeRelease,
                after: $this->releaseState($lockedRelease) + ['reason' => $validated['reason']],
            );

            return $lockedRelease->refresh()->load(['items.contentNode.localizations', 'owner', 'canceller']);
        });
    }

    /** @return array<string, mixed> */
    private function releaseState(ContentRelease $release): array
    {
        return [
            'name' => $release->name,
            'status' => $release->status->value,
            'target_channel' => $release->target_channel->value,
            'item_count' => $release->items()->count(),
            'cancelled_at' => $release->cancelled_at?->toAtomString(),
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
