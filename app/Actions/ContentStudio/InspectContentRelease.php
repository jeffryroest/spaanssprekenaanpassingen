<?php

namespace App\Actions\ContentStudio;

use App\ContentStudio\ReviewableContent;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentStatus;
use App\Models\ContentRelease;
use Illuminate\Support\Facades\Storage;

final class InspectContentRelease
{
    public function __construct(private readonly ReviewableContent $reviewableContent) {}

    /**
     * @return array{blockers: list<string>, warnings: list<string>}
     */
    public function handle(ContentRelease $release): array
    {
        $release->loadMissing(['items.contentNode', 'items.contentRevision.mediaAssets']);
        $blockers = [];
        $warnings = [
            'Tekstuele herkomst- en licentievelden zijn nog niet volledig gemodelleerd; controleer deze handmatig.',
        ];

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

            foreach ($this->reviewableContent->errors($contentNode, $revision) as $error) {
                $blockers[] = "{$label}: {$error}";
            }

            $mediaSnapshot = data_get($revision->snapshot, 'media', []);
            $expectedMediaCount = is_array($mediaSnapshot) ? count($mediaSnapshot) : 0;
            if ($revision->mediaAssets->count() !== $expectedMediaCount) {
                $blockers[] = "{$label}: een gekoppeld mediabestand ontbreekt of is gearchiveerd.";
            }

            foreach ($revision->mediaAssets as $asset) {
                $role = $asset->pivot->role;
                if (! Storage::disk($asset->disk)->exists($asset->object_key)) {
                    $blockers[] = "{$label}: het bestand voor {$role} ontbreekt in de opslag.";
                }
                if (! $asset->rights_status->isPublishable()) {
                    $blockers[] = "{$label}: {$role} heeft geen aantoonbaar publicatierecht.";
                }
                if ($asset->rights_expires_at?->isBefore(today())) {
                    $blockers[] = "{$label}: de rechten van {$role} zijn verlopen.";
                }
                if (! $asset->hasAccessibilityText()) {
                    $blockers[] = "{$label}: {$role} mist alt-tekst of transcript.";
                }
            }

            if (filled(data_get($revision->snapshot, 'domain_data.scene')) && $expectedMediaCount === 0) {
                $warnings[] = "{$label}: nog geen scène- of personagemedia gekoppeld.";
            }
        }

        return [
            'blockers' => array_values(array_unique($blockers)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }
}
