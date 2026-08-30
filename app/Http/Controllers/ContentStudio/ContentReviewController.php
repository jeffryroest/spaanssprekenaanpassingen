<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\Actions\ContentStudio\WithdrawContentReview;
use App\Enums\ContentReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\DecideContentReviewRequest;
use App\Http\Requests\ContentStudio\SubmitContentReviewRequest;
use App\Http\Requests\ContentStudio\WithdrawContentReviewRequest;
use App\Models\ContentNode;
use Illuminate\Http\RedirectResponse;

class ContentReviewController extends Controller
{
    public function submit(
        SubmitContentReviewRequest $request,
        ContentNode $contentNode,
        SubmitContentForReview $submitContentForReview,
    ): RedirectResponse {
        $validated = $request->validated();
        $contentNode = $submitContentForReview->handle(
            actor: $request->user(),
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
            note: $validated['note'] ?? null,
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', 'Versie '.$contentNode->current_version.' is veilig voor review ingediend.');
    }

    public function decide(
        DecideContentReviewRequest $request,
        ContentNode $contentNode,
        DecideContentReview $decideContentReview,
    ): RedirectResponse {
        $validated = $request->validated();
        $action = ContentReviewAction::from($validated['action']);
        $contentNode = $decideContentReview->handle(
            actor: $request->user(),
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
            action: $action,
            note: $validated['note'],
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', $action === ContentReviewAction::Approved
                ? 'De beoordeelde versie is goedgekeurd.'
                : 'De wijzigingen zijn met motivatie teruggestuurd naar de redactie.');
    }

    public function withdraw(
        WithdrawContentReviewRequest $request,
        ContentNode $contentNode,
        WithdrawContentReview $withdrawContentReview,
    ): RedirectResponse {
        $validated = $request->validated();
        $contentNode = $withdrawContentReview->handle(
            actor: $request->user(),
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
            reason: $validated['reason'],
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', 'De reviewaanvraag is ingetrokken. Je kunt het concept weer bewerken.');
    }
}
