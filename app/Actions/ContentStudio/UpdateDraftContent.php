<?php

namespace App\Actions\ContentStudio;

use App\ContentStudio\ContentMediaSelection;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\User;
use App\Rules\PlayableDomainData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class UpdateDraftContent
{
    public function __construct(private readonly ContentMediaSelection $mediaSelection) {}

    /** @param array<string, mixed> $domainData @param array<string, int|string|null> $media */
    public function handle(
        User $actor,
        ContentNode $contentNode,
        int $expectedVersion,
        string $slug,
        string $locale,
        string $title,
        ?string $summary = null,
        ?string $body = null,
        array $domainData = [],
        array $media = [],
    ): ContentNode {
        Gate::forUser($actor)->authorize('update', $contentNode);
        $selectedMedia = $this->mediaSelection->resolve($contentNode->content_type, $media);

        $validated = Validator::make([
            'slug' => $slug,
            'locale' => $locale,
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'domain_data' => $domainData,
        ], [
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'domain_data' => ['array', new PlayableDomainData($contentNode->content_type)],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentNode, $expectedVersion, $validated, $selectedMedia): ContentNode {
            $lockedNode = ContentNode::query()
                ->with(['localizations', 'revisions'])
                ->lockForUpdate()
                ->findOrFail($contentNode->getKey());

            if (! $lockedNode->isEditableDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Alleen concepten en content met gevraagde wijzigingen kunnen worden bewerkt.',
                ]);
            }

            if ($lockedNode->current_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Deze content is intussen gewijzigd. Vernieuw de pagina en probeer opnieuw.',
                ]);
            }

            $localization = $lockedNode->defaultLocalization();

            if ($localization === null) {
                throw ValidationException::withMessages([
                    'locale' => 'De standaardlokalisatie ontbreekt en moet eerst worden hersteld.',
                ]);
            }

            $before = $this->state($lockedNode, $localization->title, $localization->summary, $localization->body);
            $newVersion = $lockedNode->current_version + 1;

            $lockedNode->update([
                'slug' => $validated['slug'],
                'status' => ContentStatus::Draft,
                'default_locale' => $validated['locale'],
                'current_version' => $newVersion,
                'updated_by' => $actor->getKey(),
            ]);

            $localization->update([
                'locale' => $validated['locale'],
                'title' => $validated['title'],
                'summary' => $validated['summary'],
                'body' => $validated['body'],
            ]);

            $mediaSnapshot = $selectedMedia->map(
                fn ($asset, string $role): array => [
                    'role' => $role,
                    'asset_id' => $asset->getKey(),
                    'asset_uuid' => $asset->uuid,
                ],
            )->values()->all();

            $snapshot = [
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
            ];

            $revision = $lockedNode->revisions()->create([
                'version' => $newVersion,
                'status' => RevisionStatus::Draft,
                'snapshot' => $snapshot,
                'change_summary' => 'Concept bijgewerkt',
                'created_by' => $actor->getKey(),
                'created_at' => now(),
            ]);

            $sortOrder = 0;
            foreach ($selectedMedia as $role => $asset) {
                $revision->mediaAssets()->attach($asset->getKey(), [
                    'content_node_id' => $lockedNode->getKey(),
                    'role' => (string) $role,
                    'sort_order' => $sortOrder++,
                ]);
            }

            $lockedNode->refresh()->load(['localizations', 'revisions.mediaAssets']);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.updated',
                contentNode: $lockedNode,
                before: $before,
                after: $this->state(
                    $lockedNode,
                    $localization->title,
                    $localization->summary,
                    $localization->body,
                ),
            );

            return $lockedNode;
        });
    }

    /** @return array<string, mixed> */
    private function state(ContentNode $contentNode, ?string $title, ?string $summary, ?string $body): array
    {
        return [
            'content_type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'status' => $contentNode->status->value,
            'locale' => $contentNode->default_locale,
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'version' => $contentNode->current_version,
        ];
    }
}
