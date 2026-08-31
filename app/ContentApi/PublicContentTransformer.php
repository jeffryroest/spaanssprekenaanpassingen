<?php

namespace App\ContentApi;

use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\ContentReleaseItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

final class PublicContentTransformer
{
    /** @return array<string, mixed> */
    public function detail(
        ContentNode $contentNode,
        ContentReleaseItem $releaseItem,
        ?string $requestedLocale,
    ): array {
        $snapshot = $releaseItem->contentRevision->snapshot;
        [$localization, $resolvedLocale, $availableLocales] = $this->resolveLocalization(
            localizations: Arr::get($snapshot, 'localizations', []),
            requestedLocale: $requestedLocale,
            defaultLocale: $contentNode->default_locale,
        );

        return $this->identity($contentNode, $releaseItem, $requestedLocale, $resolvedLocale, $availableLocales) + [
            'content' => [
                'title' => $localization['title'] ?? null,
                'summary' => $localization['summary'] ?? null,
                'body' => $localization['body'] ?? null,
                'metadata' => $localization['metadata'] ?? [],
                'domain_data' => Arr::get($snapshot, 'domain_data', []),
                'media' => $this->media($contentNode, $releaseItem),
            ],
        ];
    }

    /** @return array<string, array<string, int|string|null>> */
    private function media(ContentNode $contentNode, ContentReleaseItem $releaseItem): array
    {
        if (Arr::get($releaseItem->contentRevision->snapshot, 'domain_data.runtime_access.visibility', 'public') !== 'public') {
            return [];
        }

        return $releaseItem->contentRevision->mediaAssets
            ->filter(fn ($asset): bool => $asset->isPublishable()
                && Storage::disk($asset->disk)->exists($asset->object_key)
            )
            ->mapWithKeys(fn ($asset): array => [
                $asset->pivot->role => [
                    'kind' => $asset->kind->value,
                    'url' => route('api.v1.media.show', [
                        'contentType' => $contentNode->content_type->value,
                        'slug' => $contentNode->slug,
                        'version' => $releaseItem->version,
                        'role' => $asset->pivot->role,
                    ]),
                    'mime_type' => $asset->mime_type,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'alt_text' => $asset->alt_text,
                    'transcript' => $asset->transcript,
                ],
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    public function summary(
        ContentNode $contentNode,
        ContentReleaseItem $releaseItem,
        ?string $requestedLocale,
    ): array {
        $snapshot = $releaseItem->contentRevision->snapshot;
        [$localization, $resolvedLocale, $availableLocales] = $this->resolveLocalization(
            localizations: Arr::get($snapshot, 'localizations', []),
            requestedLocale: $requestedLocale,
            defaultLocale: $contentNode->default_locale,
        );

        return $this->identity($contentNode, $releaseItem, $requestedLocale, $resolvedLocale, $availableLocales) + [
            'title' => $localization['title'] ?? null,
            'summary' => $localization['summary'] ?? null,
        ];
    }

    /**
     * @param  array<int, mixed>  $localizations
     * @return array{0: array<string, mixed>, 1: string, 2: list<string>}
     */
    private function resolveLocalization(
        array $localizations,
        ?string $requestedLocale,
        string $defaultLocale,
    ): array {
        $validLocalizations = collect($localizations)
            ->filter(fn (mixed $localization): bool => is_array($localization)
                && is_string($localization['locale'] ?? null)
            )
            ->values();
        $availableLocales = $validLocalizations
            ->pluck('locale')
            ->unique()
            ->values()
            ->all();
        $localization = $validLocalizations->firstWhere('locale', $requestedLocale)
            ?? $validLocalizations->firstWhere('locale', $defaultLocale)
            ?? $validLocalizations->first()
            ?? ['locale' => $defaultLocale];

        return [$localization, $localization['locale'], $availableLocales];
    }

    /** @return array<string, mixed> */
    private function identity(
        ContentNode $contentNode,
        ContentReleaseItem $releaseItem,
        ?string $requestedLocale,
        string $resolvedLocale,
        array $availableLocales,
    ): array {
        return [
            'id' => $contentNode->getKey(),
            'type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'version' => $releaseItem->version,
            'content_schema_version' => $contentNode->schema_version,
            'requested_locale' => $requestedLocale,
            'locale' => $resolvedLocale,
            'available_locales' => $availableLocales,
            'published_at' => $contentNode->published_at?->toAtomString(),
            'publication' => [
                'release_id' => $releaseItem->content_release_id,
                'published_at' => $releaseItem->release?->published_at?->toAtomString(),
            ],
            'links' => [
                'self' => route($this->routeName($contentNode->content_type), $contentNode->slug),
            ],
        ];
    }

    private function routeName(ContentType $contentType): string
    {
        return match ($contentType) {
            ContentType::Region => 'api.v1.worlds.show',
            ContentType::Location => 'api.v1.locations.show',
            ContentType::Mission => 'api.v1.missions.show',
            ContentType::ConversationScenario => 'api.v1.conversations.show',
            default => throw new \LogicException('Dit contenttype heeft geen publieke runtime-route.'),
        };
    }
}
