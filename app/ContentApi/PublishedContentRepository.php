<?php

namespace App\ContentApi;

use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\ContentReleaseItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class PublishedContentRepository
{
    /** @return LengthAwarePaginator<ContentNode> */
    public function paginate(ContentType $contentType, int $perPage): LengthAwarePaginator
    {
        return $this->query($contentType)
            ->orderBy('slug')
            ->paginate($perPage);
    }

    public function find(ContentType $contentType, string $slug): ?ContentNode
    {
        return $this->query($contentType)
            ->where('slug', $slug)
            ->first();
    }

    public function latestProductionItem(ContentNode $contentNode): ?ContentReleaseItem
    {
        return $contentNode->releaseItems
            ->filter(fn (ContentReleaseItem $item): bool => $item->version === $contentNode->current_version
                && $item->contentRevision?->version === $contentNode->current_version
                && (int) $item->contentRevision?->content_node_id === (int) $contentNode->getKey()
                && $item->release?->target_channel === ContentReleaseChannel::Production
                && $item->release?->status === ContentReleaseStatus::Published
                && $item->release?->published_at !== null
                && ! $item->release->published_at->isFuture()
            )
            ->sortByDesc(fn (ContentReleaseItem $item): int => $item->release?->published_at?->getTimestamp() ?? 0)
            ->first();
    }

    /** @return Builder<ContentNode> */
    private function query(ContentType $contentType): Builder
    {
        return ContentNode::query()
            ->where('content_type', $contentType->value)
            ->where('status', ContentStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('releaseItems', function (Builder $query): void {
                $query
                    ->whereColumn('content_release_items.version', 'content_nodes.current_version')
                    ->whereHas('contentRevision', function (Builder $query): void {
                        $query
                            ->whereColumn('content_revisions.version', 'content_release_items.version')
                            ->whereColumn('content_revisions.content_node_id', 'content_release_items.content_node_id');
                    })
                    ->whereHas('release', $this->productionReleaseQuery(...));
            })
            ->with(['releaseItems' => function ($query): void {
                $query
                    ->whereHas('release', $this->productionReleaseQuery(...))
                    ->with(['release', 'contentRevision']);
            }]);
    }

    private function productionReleaseQuery(Builder $query): void
    {
        $query
            ->where('target_channel', ContentReleaseChannel::Production->value)
            ->where('status', ContentReleaseStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
