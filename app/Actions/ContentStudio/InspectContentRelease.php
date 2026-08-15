<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentStatus;
use App\Models\ContentRelease;

final class InspectContentRelease
{
    /**
     * @return array{blockers: list<string>, warnings: list<string>}
     */
    public function handle(ContentRelease $release): array
    {
        $release->loadMissing(['items.contentNode', 'items.contentRevision']);
        $blockers = [];

        if ($release->status !== ContentReleaseStatus::Draft) {
            $blockers[] = 'Alleen een conceptrelease kan een preflight doorlopen.';
        }

        if ($release->items->isEmpty()) {
            $blockers[] = 'Voeg minimaal één goedgekeurde contentversie toe.';
        }

        if ($release->desired_publish_at?->isFuture()) {
            $blockers[] = 'Het gewenste publicatiemoment is nog niet bereikt.';
        }

        foreach ($release->items as $item) {
            $contentNode = $item->contentNode;
            $revision = $item->contentRevision;
            $label = $contentNode?->slug ?? "item #{$item->getKey()}";

            if ($contentNode === null || $revision === null) {
                $blockers[] = "{$label}: content of revisie ontbreekt.";

                continue;
            }

            if ($contentNode->status !== ContentStatus::Scheduled) {
                $blockers[] = "{$label}: status is niet langer Gepland.";
            }

            if ($contentNode->current_version !== $item->version
                || (int) $revision->content_node_id !== (int) $contentNode->getKey()
                || $revision->version !== $item->version) {
                $blockers[] = "{$label}: de versiegebonden revisie is niet meer actueel.";
            }

            if (! $contentNode->reviews()
                ->where('content_revision_id', $revision->getKey())
                ->where('action', ContentReviewAction::Approved->value)
                ->exists()) {
                $blockers[] = "{$label}: de goedkeuring voor revisie {$item->version} ontbreekt.";
            }
        }

        return [
            'blockers' => array_values(array_unique($blockers)),
            'warnings' => [
                'Relatie-, media- en licentievelden worden toegevoegd zodra de typespecifieke editors beschikbaar zijn.',
            ],
        ];
    }
}
