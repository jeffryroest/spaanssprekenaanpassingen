<?php

namespace App\Actions\ContentStudio;

use App\Enums\ContentStatus;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ArchiveDraftContent
{
    public function handle(
        User $actor,
        ContentNode $contentNode,
        int $expectedVersion,
        string $reason,
    ): ContentNode {
        Gate::forUser($actor)->authorize('delete', $contentNode);

        $validated = Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', 'min:3', 'max:480'],
        ])->validate();

        return DB::transaction(function () use ($actor, $contentNode, $expectedVersion, $validated): ContentNode {
            $lockedNode = ContentNode::query()
                ->with('localizations')
                ->lockForUpdate()
                ->findOrFail($contentNode->getKey());

            if (! $lockedNode->isEditableDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Alleen concepten en content met gevraagde wijzigingen kunnen worden gearchiveerd.',
                ]);
            }

            if ($lockedNode->current_version !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'expected_version' => 'Deze content is intussen gewijzigd. Vernieuw de pagina en probeer opnieuw.',
                ]);
            }

            $localization = $lockedNode->defaultLocalization();
            $before = $this->state($lockedNode, $localization?->title);

            $lockedNode->update([
                'status' => ContentStatus::Archived,
                'updated_by' => $actor->getKey(),
            ]);

            $lockedNode->refresh()->load(['localizations', 'revisions']);

            AuditLog::recordContentChange(
                actor: $actor,
                action: 'content.archived',
                contentNode: $lockedNode,
                before: $before,
                after: $this->state($lockedNode, $localization?->title) + ['reason' => $validated['reason']],
            );

            return $lockedNode;
        });
    }

    /** @return array<string, mixed> */
    private function state(ContentNode $contentNode, ?string $title): array
    {
        return [
            'content_type' => $contentNode->content_type->value,
            'slug' => $contentNode->slug,
            'status' => $contentNode->status->value,
            'title' => $title,
            'version' => $contentNode->current_version,
        ];
    }
}
