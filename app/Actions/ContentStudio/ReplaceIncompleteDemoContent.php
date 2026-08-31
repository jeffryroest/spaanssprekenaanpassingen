<?php

namespace App\Actions\ContentStudio;

use App\ContentStudio\ContentMediaSelection;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\ContentLocalization;
use App\Models\ContentNode;
use App\Models\ContentRevision;
use App\Models\User;
use App\Rules\PlayableDomainData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ReplaceIncompleteDemoContent
{
    public function __construct(private readonly ContentMediaSelection $mediaSelection) {}

    /**
     * @param  array<string, mixed>  $template
     * @param  array<string, int|string|null>  $media
     */
    public function handle(
        User $actor,
        ContentNode $contentNode,
        array $template,
        array $media,
        string $packageVersion,
    ): ContentNode {
        if ($actor->content_role !== ContentRole::Administrator) {
            throw new AuthorizationException('Alleen een Content Studio-beheerder mag oude demoplaceholders vervangen.');
        }

        $selectedMedia = $this->mediaSelection->resolve($template['content_type'], $media);
        $validated = Validator::make([
            'slug' => $template['slug'],
            'locale' => $template['locale'],
            'title' => $template['title'],
            'summary' => $template['summary'],
            'body' => $template['body'],
            'domain_data' => $template['domain_data'],
        ], [
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'domain_data' => ['array', new PlayableDomainData($template['content_type'])],
        ])->validate();

        return DB::transaction(function () use (
            $actor,
            $contentNode,
            $media,
            $packageVersion,
            $selectedMedia,
            $template,
            $validated,
        ): ContentNode {
            $lockedNode = ContentNode::query()
                ->with(['localizations', 'revisions.mediaAssets', 'releaseItems'])
                ->lockForUpdate()
                ->findOrFail($contentNode->getKey());
            $currentRevision = $lockedNode->revisions->firstWhere('version', $lockedNode->current_version);
            $localization = $lockedNode->defaultLocalization();

            if ($lockedNode->content_type !== $template['content_type']
                || $lockedNode->slug !== $template['slug']
                || ! $this->mayReplace($lockedNode, $currentRevision, $localization)) {
                throw ValidationException::withMessages([
                    'content' => 'Alleen een ongepubliceerde, onvolledige demoplaceholder zonder media of releasekoppeling kan worden vervangen.',
                ]);
            }

            $before = $this->state($lockedNode, $currentRevision);
            $newVersion = $lockedNode->current_version + 1;
            $metadata = array_replace_recursive($localization->metadata ?? [], [
                'demo_content_package' => [
                    'key' => $template['key'],
                    'version' => $packageVersion,
                ],
            ]);

            if ($lockedNode->status === ContentStatus::InReview) {
                $lockedNode->reviews()->create([
                    'content_revision_id' => $currentRevision->getKey(),
                    'version' => $lockedNode->current_version,
                    'action' => ContentReviewAction::Withdrawn,
                    'from_status' => ContentStatus::InReview,
                    'to_status' => ContentStatus::Draft,
                    'note' => "Beheerder verving een onvolledige demoplaceholder door pakket {$packageVersion}.",
                    'actor_user_id' => $actor->getKey(),
                    'actor_role' => $actor->content_role?->value,
                    'created_at' => now(),
                ]);
            }

            $lockedNode->update([
                'slug' => $validated['slug'],
                'status' => ContentStatus::Draft,
                'default_locale' => $validated['locale'],
                'current_version' => $newVersion,
                'published_at' => null,
                'updated_by' => $actor->getKey(),
            ]);

            $localization->update([
                'locale' => $validated['locale'],
                'title' => $validated['title'],
                'summary' => $validated['summary'],
                'body' => $validated['body'],
                'metadata' => $metadata,
            ]);

            $mediaSnapshot = $selectedMedia->map(
                fn ($asset, string $role): array => [
                    'role' => $role,
                    'asset_id' => $asset->getKey(),
                    'asset_uuid' => $asset->uuid,
                ],
            )->values()->all();

            $revision = $lockedNode->revisions()->create([
                'version' => $newVersion,
                'status' => RevisionStatus::Draft,
                'snapshot' => [
                    'schema_version' => $lockedNode->schema_version,
                    'content_type' => $lockedNode->content_type->value,
                    'slug' => $lockedNode->slug,
                    'localizations' => [[
                        'locale' => $localization->locale,
                        'title' => $localization->title,
                        'summary' => $localization->summary,
                        'body' => $localization->body,
                        'metadata' => $localization->metadata,
                    ]],
                    'domain_data' => $validated['domain_data'],
                    'media' => $mediaSnapshot,
                ],
                'change_summary' => "Demopakket {$packageVersion} bewust toegepast",
                'created_by' => $actor->getKey(),
                'created_at' => now(),
            ]);

            $sortOrder = 0;
            foreach ($selectedMedia as $role => $asset) {
                $revision->mediaAssets()->attach($asset->getKey(), [
                    'content_node_id' => $lockedNode->getKey(),
                    'role' => (string) $role,
                    'sort_order' => $sortOrder,
                ]);
                $sortOrder += 1;
            }

            $lockedNode->refresh()->load(['localizations', 'revisions.mediaAssets', 'releaseItems']);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.demo_placeholder_replaced',
                contentNode: $lockedNode,
                before: $before,
                after: $this->state($lockedNode, $revision) + [
                    'package_key' => $template['key'],
                    'package_version' => $packageVersion,
                    'media_roles' => array_keys($media),
                ],
            );

            return $lockedNode;
        });
    }

    private function mayReplace(
        ContentNode $contentNode,
        ?ContentRevision $revision,
        ?ContentLocalization $localization,
    ): bool
    {
        return ! $contentNode->trashed()
            && $contentNode->published_at === null
            && in_array($contentNode->status, [
                ContentStatus::Draft,
                ContentStatus::InReview,
                ContentStatus::ChangesRequested,
            ], true)
            && $contentNode->releaseItems->isEmpty()
            && $contentNode->localizations->count() === 1
            && $localization !== null
            && $revision !== null
            && blank(data_get($revision->snapshot, 'domain_data.scene'))
            && $revision->mediaAssets->isEmpty();
    }

    /** @return array<string, mixed> */
    private function state(ContentNode $contentNode, ?ContentRevision $revision): array
    {
        return [
            'content_type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'status' => $contentNode->status->value,
            'version' => $contentNode->current_version,
            'scene' => data_get($revision?->snapshot, 'domain_data.scene'),
        ];
    }
}
