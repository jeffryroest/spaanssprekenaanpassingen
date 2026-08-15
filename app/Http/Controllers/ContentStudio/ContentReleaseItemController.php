<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\RemoveContentFromRelease;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\AddContentReleaseItemRequest;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\ContentReleaseItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContentReleaseItemController extends Controller
{
    public function store(
        AddContentReleaseItemRequest $request,
        ContentRelease $contentRelease,
        AddContentToRelease $addContentToRelease,
    ): RedirectResponse {
        $validated = $request->validated();
        $contentNode = ContentNode::query()->findOrFail($validated['content_node_id']);

        $addContentToRelease->handle(
            actor: $request->user(),
            release: $contentRelease,
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
        );

        return redirect()
            ->route('content-studio.releases.show', $contentRelease)
            ->with('success', 'De goedgekeurde revisie is versiegebonden ingepland.');
    }

    public function destroy(
        Request $request,
        ContentRelease $contentRelease,
        ContentReleaseItem $contentReleaseItem,
        RemoveContentFromRelease $removeContentFromRelease,
    ): RedirectResponse {
        Gate::authorize('content-studio.publish');

        $removeContentFromRelease->handle(
            actor: $request->user(),
            release: $contentRelease,
            item: $contentReleaseItem,
        );

        return redirect()
            ->route('content-studio.releases.show', $contentRelease)
            ->with('success', 'De content is uit de conceptrelease gehaald.');
    }
}
