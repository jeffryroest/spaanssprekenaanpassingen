<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentReviewAction;
use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\ContentRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SubmitContentForReview
{
    public function handle(
        User $actor,
        ContentNode $contentNode,
        int $expectedVersion,
        ?string $note = null,
    ): ContentNode {
        Gate::forUser($actor)->authorize('update', $contentNode);

        $validated = Validator::make(['note' => $note], [
            'note' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentNode, $expectedVersion, $validated): ContentNode {
            $lockedNode = ContentNode::query()
                ->with('localizations')
                ->lockForUpdate()
                ->findOrFail($contentNode->getKey());

            if ($lockedNode->status !== ContentStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Alleen een actuele conceptrevisie kan worden ingediend. Verwerk gevraagde wijzigingen eerst in een nieuwe revisie.',
                ]);
            }

            if ($lockedNode->current_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Deze content is intussen gewijzigd. Vernieuw de pagina en probeer opnieuw.',
                ]);
            }

            if (blank($lockedNode->defaultLocalization()?->title)) {
                throw ValidationException::withMessages([
                    'title' => 'Een titel is verplicht voordat review kan worden aangevraagd.',
                ]);
            }

            $revision = ContentRevision::query()
                ->whereBelongsTo($lockedNode)
                ->where('version', $lockedNode->current_version)
                ->first();

            if ($revision === null) {
                throw ValidationException::withMessages([
                    'expected_version' => 'De actuele revisie ontbreekt en moet eerst worden hersteld.',
                ]);
            }

            $fromStatus = $lockedNode->status;
            $lockedNode->update([
                'status' => ContentStatus::InReview,
                'updated_by' => $actor->getKey(),
            ]);

            $lockedNode->reviews()->create([
                'content_revision_id' => $revision->getKey(),
                'version' => $lockedNode->current_version,
                'action' => ContentReviewAction::Submitted,
                'from_status' => $fromStatus,
                'to_status' => ContentStatus::InReview,
                'note' => $validated['note'],
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->content_role?->value,
                'created_at' => now(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.review_submitted',
                contentNode: $lockedNode,
                before: $this->state($lockedNode, $fromStatus),
                after: $this->state($lockedNode, ContentStatus::InReview) + ['note' => $validated['note']],
            );

            return $lockedNode->refresh()->load(['localizations', 'revisions', 'reviews.actor']);
        });
    }

    /** @return array<string, mixed> */
    private function state(ContentNode $contentNode, ContentStatus $status): array
    {
        return [
            'content_type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'status' => $status->value,
            'version' => $contentNode->current_version,
        ];
    }
}
