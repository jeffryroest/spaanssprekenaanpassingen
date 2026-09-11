<?php

namespace App\ContentStudio;

use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;
use RuntimeException;

final class PreparePublishedDemoMediaUpgrade
{
    public function __construct(private readonly GoldenRouteMedia $goldenRouteMedia) {}

    /** @param array<string, mixed> $template */
    public function canHandle(ContentNode $contentNode, string $templateKey, array $template): bool
    {
        $contentNode->loadMissing(['localizations', 'revisions.mediaAssets', 'releaseItems.release']);
        $revision = $contentNode->revisions->firstWhere('version', $contentNode->current_version);
        $roles = $this->goldenRouteMedia->rolesForTemplate($templateKey);

        return $contentNode->status === ContentStatus::Published
            && $contentNode->published_at !== null
            && $roles !== []
            && $revision !== null
            && $revision->mediaAssets->isEmpty()
            && data_get($contentNode->defaultLocalization()?->metadata, 'demo_content_package.key') === $templateKey
            && $this->matchesTemplate($contentNode, $revision->snapshot, $template)
            && $contentNode->releaseItems->contains(fn ($item): bool => $item->version === $contentNode->current_version
                && $item->release?->target_channel === ContentReleaseChannel::Production
                && $item->release?->status === ContentReleaseStatus::Published
                && $item->release?->published_at !== null
                && ! $item->release->published_at->isFuture());
    }

    /** @param array<string, mixed> $template */
    public function handle(User $actor, ContentNode $contentNode, string $templateKey, array $template): ContentNode
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Een gepubliceerde media-upgrade mag alleen binnen de atomaire proefweekrelease plaatsvinden.');
        }

        Gate::forUser($actor)->authorize('update', $contentNode);
        $assets = $this->goldenRouteMedia->ensure(
            $actor,
            array_values($this->goldenRouteMedia->rolesForTemplate($templateKey)),
        );
        $lockedNode = ContentNode::query()
            ->with(['localizations', 'revisions.mediaAssets', 'releaseItems.release'])
            ->lockForUpdate()
            ->findOrFail($contentNode->getKey());

        if (! $this->canHandle($lockedNode, $templateKey, $template)) {
            throw new RuntimeException("{$lockedNode->slug} is tijdens de voorbereiding gewijzigd en blijft ongewijzigd.");
        }

        $roles = $this->goldenRouteMedia->rolesForTemplate($templateKey);
        $media = collect($roles)->map(function (string $assetKey, string $role) use ($assets): array {
            $asset = $assets->get($assetKey);

            if ($asset === null) {
                throw new RuntimeException("Het verplichte mediabestand voor {$role} kon niet worden voorbereid.");
            }

            return [
                'role' => $role,
                'asset_id' => $asset->getKey(),
                'asset_uuid' => $asset->uuid,
            ];
        })->values()->all();
        $oldVersion = $lockedNode->current_version;
        $newVersion = $oldVersion + 1;
        $localization = $lockedNode->defaultLocalization();

        if ($localization === null) {
            throw new RuntimeException("De standaardlokalisatie van {$lockedNode->slug} ontbreekt.");
        }

        $metadata = array_replace_recursive($localization->metadata ?? [], [
            'demo_content_package' => [
                'key' => $templateKey,
                'version' => DemoContentInstaller::PACKAGE_VERSION,
            ],
        ]);
        $localization->update(['metadata' => $metadata]);

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
                    'metadata' => $metadata,
                ]],
                'domain_data' => $template['domain_data'],
                'media' => $media,
            ],
            'change_summary' => 'Ontbrekende proefweekmedia toegevoegd',
            'created_by' => $actor->getKey(),
            'created_at' => now(),
        ]);

        foreach ($media as $sortOrder => $selection) {
            $revision->mediaAssets()->attach($selection['asset_id'], [
                'content_node_id' => $lockedNode->getKey(),
                'role' => $selection['role'],
                'sort_order' => $sortOrder,
            ]);
        }

        $lockedNode->update([
            'status' => ContentStatus::Draft,
            'current_version' => $newVersion,
            'updated_by' => $actor->getKey(),
        ]);

        AuditLog::recordContentChange(
            actor: $actor,
            action: 'content.published_demo_media_upgrade_prepared',
            contentNode: $lockedNode,
            before: [
                'content_type' => $lockedNode->content_type->value,
                'slug' => $lockedNode->slug,
                'status' => ContentStatus::Published->value,
                'version' => $oldVersion,
                'published_at' => $lockedNode->published_at?->toAtomString(),
            ],
            after: [
                'content_type' => $lockedNode->content_type->value,
                'slug' => $lockedNode->slug,
                'status' => ContentStatus::Draft->value,
                'version' => $newVersion,
                'package_version' => DemoContentInstaller::PACKAGE_VERSION,
                'media_roles' => array_keys($roles),
                'atomic_release_required' => true,
            ],
        );

        return $lockedNode->refresh()->load(['localizations', 'revisions.mediaAssets', 'releaseItems.release']);
    }

    /** @param array<string, mixed> $snapshot @param array<string, mixed> $template */
    private function matchesTemplate(ContentNode $contentNode, array $snapshot, array $template): bool
    {
        $localization = $contentNode->defaultLocalization();

        return $contentNode->default_locale === $template['locale']
            && $localization !== null
            && $localization->title === $template['title']
            && $localization->summary === $template['summary']
            && $localization->body === $template['body']
            && data_get($snapshot, 'domain_data', []) == $template['domain_data'];
    }
}
