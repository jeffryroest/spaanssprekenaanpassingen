<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseChannel;
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

final class PublishContentRelease
{
    public function __construct(private readonly InspectContentRelease $inspectContentRelease) {}

    public function handle(
        User $actor,
        ContentRelease $release,
        string $confirmation,
        string $reason,
        bool $acknowledgeWarnings = false,
    ): ContentRelease {
        Gate::forUser($actor)->authorize('content-studio.publish');

        $validated = Validator::make([
            'confirmation' => $confirmation,
            'reason' => $reason,
            'acknowledge_warnings' => $acknowledgeWarnings,
        ], [
            'confirmation' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'acknowledge_warnings' => ['boolean'],
        ])->validate();

        return DB::transaction(function () use ($actor, $release, $validated): ContentRelease {
            $lockedRelease = ContentRelease::query()->lockForUpdate()->findOrFail($release->getKey());
            $lockedRelease->load(['items.contentNode', 'items.contentRevision']);

            if ($lockedRelease->status !== ContentReleaseStatus::Draft) {
                throw ValidationException::withMessages([
                    'release' => 'Alleen een conceptrelease kan worden uitgevoerd.',
                ]);
            }

            if ($lockedRelease->desired_publish_at?->isFuture()) {
                throw ValidationException::withMessages([
                    'desired_publish_at' => 'Het gewenste publicatiemoment is nog niet bereikt.',
                ]);
            }

            $requiredConfirmation = $lockedRelease->target_channel === ContentReleaseChannel::Production
                ? 'PUBLICEREN'
                : 'UITVOEREN';

            if ($validated['confirmation'] !== $requiredConfirmation) {
                throw ValidationException::withMessages([
                    'confirmation' => "Typ exact {$requiredConfirmation} om deze release uit te voeren.",
                ]);
            }

            if ($lockedRelease->target_channel === ContentReleaseChannel::Production
                && ! $validated['acknowledge_warnings']) {
                throw ValidationException::withMessages([
                    'acknowledge_warnings' => 'Bevestig dat de zichtbare preflightwaarschuwingen handmatig zijn gecontroleerd.',
                ]);
            }

            foreach ($lockedRelease->items as $item) {
                ContentNode::query()->lockForUpdate()->findOrFail($item->content_node_id);
            }

            $preflight = $this->inspectContentRelease->handle($lockedRelease);

            if ($preflight['blockers'] !== []) {
                throw ValidationException::withMessages([
                    'preflight' => implode(' ', $preflight['blockers']),
                ]);
            }

            $publishedAt = now();
            $beforeRelease = $this->releaseState($lockedRelease);

            foreach ($lockedRelease->items as $item) {
                $contentNode = ContentNode::query()->findOrFail($item->content_node_id);
                $targetStatus = $lockedRelease->target_channel === ContentReleaseChannel::Production
                    ? ContentStatus::Published
                    : ContentStatus::Approved;
                $beforeContent = $this->contentState($contentNode, ContentStatus::Scheduled);

                $contentNode->update([
                    'status' => $targetStatus,
                    'published_at' => $targetStatus === ContentStatus::Published ? $publishedAt : null,
                    'updated_by' => $actor->getKey(),
                ]);

                AuditLog::recordContentChange(
                    actor: $actor,
                    action: $targetStatus === ContentStatus::Published
                        ? 'content.published'
                        : 'content.release_validated',
                    contentNode: $contentNode,
                    before: $beforeContent,
                    after: $this->contentState($contentNode, $targetStatus) + [
                        'release_id' => $lockedRelease->getKey(),
                        'target_channel' => $lockedRelease->target_channel->value,
                        'reason' => $validated['reason'],
                    ],
                );
            }

            $lockedRelease->update([
                'status' => ContentReleaseStatus::Published,
                'published_by' => $actor->getKey(),
                'published_at' => $publishedAt,
            ]);

            AuditLog::recordReleaseChange(
                actor: $actor,
                action: 'content.release_published',
                release: $lockedRelease,
                before: $beforeRelease,
                after: $this->releaseState($lockedRelease) + [
                    'reason' => $validated['reason'],
                    'warnings_acknowledged' => $validated['acknowledge_warnings'],
                ],
            );

            return $lockedRelease->refresh()->load([
                'items.contentNode.localizations',
                'items.contentRevision',
                'owner',
                'publisher',
            ]);
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
            'published_at' => $release->published_at?->toAtomString(),
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
            'published_at' => $contentNode->published_at?->toAtomString(),
        ];
    }
}
