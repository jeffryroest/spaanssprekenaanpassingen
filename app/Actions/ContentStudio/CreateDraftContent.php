<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\RevisionStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateDraftContent
{
    /**
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $domainData
     */
    public function handle(
        User $actor,
        ContentType $contentType,
        string $slug,
        string $locale,
        string $title,
        ?string $summary = null,
        ?string $body = null,
        array $metadata = [],
        array $domainData = [],
    ): ContentNode {
        Gate::forUser($actor)->authorize('create', ContentNode::class);

        $validated = Validator::make([
            'slug' => $slug,
            'locale' => $locale,
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'metadata' => $metadata,
            'domain_data' => $domainData,
        ], [
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'metadata' => ['array'],
            'domain_data' => ['array'],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentType, $validated): ContentNode {
            $contentNode = ContentNode::query()->create([
                'content_type' => $contentType,
                'slug' => $validated['slug'],
                'status' => ContentStatus::Draft,
                'default_locale' => $validated['locale'],
                'schema_version' => 1,
                'current_version' => 1,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);

            $localization = $contentNode->localizations()->create([
                'locale' => $validated['locale'],
                'title' => $validated['title'],
                'summary' => $validated['summary'],
                'body' => $validated['body'],
                'metadata' => $validated['metadata'],
            ]);

            $contentNode->revisions()->create([
                'version' => 1,
                'status' => RevisionStatus::Draft,
                'snapshot' => [
                    'schema_version' => 1,
                    'content_type' => $contentType->value,
                    'slug' => $contentNode->slug,
                    'localizations' => [[
                        'locale' => $localization->locale,
                        'title' => $localization->title,
                        'summary' => $localization->summary,
                        'body' => $localization->body,
                        'metadata' => $localization->metadata,
                    ]],
                    'domain_data' => $validated['domain_data'],
                ],
                'change_summary' => 'Eerste conceptversie',
                'created_by' => $actor->getKey(),
                'created_at' => now(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.created',
                contentNode: $contentNode,
                before: null,
                after: [
                    'content_type' => $contentType->value,
                    'slug' => $contentNode->slug,
                    'status' => ContentStatus::Draft->value,
                    'locale' => $localization->locale,
                    'title' => $localization->title,
                    'summary' => $localization->summary,
                    'body' => $localization->body,
                    'version' => 1,
                ],
            );

            return $contentNode->load(['localizations', 'revisions']);
        });
    }
}
