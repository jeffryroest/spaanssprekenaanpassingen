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

final class WithdrawContentReview
{
    public function handle(
        User $actor,
        ContentNode $contentNode,
        int $expectedVersion,
        string $reason,
    ): ContentNode {
        Gate::forUser($actor)->authorize('update', $contentNode);

        $validated = Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentNode, $expectedVersion, $validated): ContentNode {
            $lockedNode = ContentNode::query()->lockForUpdate()->findOrFail($contentNode->getKey());

            if ($lockedNode->status !== ContentStatus::InReview) {
                throw ValidationException::withMessages([
                    'status' => 'Alleen een lopende review kan worden ingetrokken.',
                ]);
            }

            if ($lockedNode->current_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Deze content is intussen gewijzigd. Vernieuw de pagina en probeer opnieuw.',
                ]);
            }

            $revision = ContentRevision::query()
                ->whereBelongsTo($lockedNode)
                ->where('version', $lockedNode->current_version)
                ->first();

            if ($revision === null || (int) $revision->created_by !== (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'reviewer' => 'Alleen de maker van de actuele revisie kan de review intrekken.',
                ]);
            }

            $hasSubmission = $lockedNode->reviews()
                ->where('content_revision_id', $revision->getKey())
                ->where('action', ContentReviewAction::Submitted->value)
                ->exists();

            if (! $hasSubmission) {
                throw ValidationException::withMessages([
                    'status' => 'Voor deze revisie ontbreekt een geldige reviewaanvraag.',
                ]);
            }

            $lockedNode->update([
                'status' => ContentStatus::Draft,
                'updated_by' => $actor->getKey(),
            ]);

            $lockedNode->reviews()->create([
                'content_revision_id' => $revision->getKey(),
                'version' => $lockedNode->current_version,
                'action' => ContentReviewAction::Withdrawn,
                'from_status' => ContentStatus::InReview,
                'to_status' => ContentStatus::Draft,
                'note' => $validated['reason'],
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->content_role?->value,
                'created_at' => now(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.review_withdrawn',
                contentNode: $lockedNode,
                before: $this->state($lockedNode, ContentStatus::InReview),
                after: $this->state($lockedNode, ContentStatus::Draft) + ['reason' => $validated['reason']],
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
