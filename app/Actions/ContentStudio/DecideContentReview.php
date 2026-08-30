<?php

namespace App\Actions\ContentStudio;

use App\ContentStudio\ContentReviewPolicy;
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
use InvalidArgumentException;

final class DecideContentReview
{
    public function __construct(private readonly ContentReviewPolicy $reviewPolicy) {}

    public function handle(
        User $actor,
        ContentNode $contentNode,
        int $expectedVersion,
        ContentReviewAction $action,
        string $note,
    ): ContentNode {
        if (! in_array($action, [ContentReviewAction::Approved, ContentReviewAction::ChangesRequested], true)) {
            throw new InvalidArgumentException('Deze actie is geen reviewbeslissing.');
        }

        Gate::forUser($actor)->authorize(
            $action === ContentReviewAction::Approved ? 'content-studio.approve' : 'content-studio.review',
        );

        $validated = Validator::make(['note' => $note], [
            'note' => ['required', 'string', 'min:3', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentNode, $expectedVersion, $action, $validated): ContentNode {
            $lockedNode = ContentNode::query()
                ->lockForUpdate()
                ->findOrFail($contentNode->getKey());

            if ($lockedNode->status !== ContentStatus::InReview) {
                throw ValidationException::withMessages([
                    'status' => 'Alleen content die in review staat kan worden beoordeeld.',
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

            if ($revision === null) {
                throw ValidationException::withMessages([
                    'expected_version' => 'De actuele revisie ontbreekt en moet eerst worden hersteld.',
                ]);
            }

            $isOwnRevision = (int) $revision->created_by === (int) $actor->getKey();

            if ($isOwnRevision && $action === ContentReviewAction::ChangesRequested) {
                throw ValidationException::withMessages([
                    'reviewer' => 'Trek je eigen reviewaanvraag in om de revisie opnieuw te bewerken.',
                ]);
            }

            if ($isOwnRevision && ! $this->reviewPolicy->allowsSelfApproval($actor, $revision)) {
                throw ValidationException::withMessages([
                    'reviewer' => $this->reviewPolicy->requiresIndependentReviewer($revision)
                        ? 'Deze gevoelige revisie vereist altijd een onafhankelijke tweede beoordelaar.'
                        : 'De huidige reviewinstelling vereist een onafhankelijke tweede beoordelaar.',
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

            $targetStatus = $action === ContentReviewAction::Approved
                ? ContentStatus::Approved
                : ContentStatus::ChangesRequested;

            $lockedNode->update([
                'status' => $targetStatus,
                'updated_by' => $actor->getKey(),
            ]);

            $lockedNode->reviews()->create([
                'content_revision_id' => $revision->getKey(),
                'version' => $lockedNode->current_version,
                'action' => $action,
                'from_status' => ContentStatus::InReview,
                'to_status' => $targetStatus,
                'note' => $validated['note'],
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->content_role?->value,
                'created_at' => now(),
            ]);

            AuditLog::recordContentChange(
                actor: $actor,
                action: $action === ContentReviewAction::Approved
                    ? ($isOwnRevision ? 'content.review_self_approved' : 'content.review_approved')
                    : 'content.review_changes_requested',
                contentNode: $lockedNode,
                before: $this->state($lockedNode, ContentStatus::InReview),
                after: $this->state($lockedNode, $targetStatus) + [
                    'note' => $validated['note'],
                    'self_approved' => $isOwnRevision,
                ],
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
