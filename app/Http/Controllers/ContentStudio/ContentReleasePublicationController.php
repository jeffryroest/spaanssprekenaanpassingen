<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\CancelContentRelease;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\CancelContentReleaseRequest;
use App\Http\Requests\ContentStudio\PublishContentReleaseRequest;
use App\Models\ContentRelease;
use Illuminate\Http\RedirectResponse;

class ContentReleasePublicationController extends Controller
{
    public function publish(
        PublishContentReleaseRequest $request,
        ContentRelease $contentRelease,
        PublishContentRelease $publishContentRelease,
    ): RedirectResponse {
        $validated = $request->validated();
        $publishContentRelease->handle(
            actor: $request->user(),
            release: $contentRelease,
            confirmation: $validated['confirmation'],
            reason: $validated['reason'],
            acknowledgeWarnings: (bool) ($validated['acknowledge_warnings'] ?? false),
        );

        return redirect()
            ->route('content-studio.releases.show', $contentRelease)
            ->with('success', 'De release is gecontroleerd en uitgevoerd.');
    }

    public function cancel(
        CancelContentReleaseRequest $request,
        ContentRelease $contentRelease,
        CancelContentRelease $cancelContentRelease,
    ): RedirectResponse {
        $validated = $request->validated();
        $cancelContentRelease->handle(
            actor: $request->user(),
            release: $contentRelease,
            reason: $validated['cancel_reason'],
        );

        return redirect()
            ->route('content-studio.releases.show', $contentRelease)
            ->with('success', 'De conceptrelease is geannuleerd; de content is weer goedgekeurd.');
    }
}
