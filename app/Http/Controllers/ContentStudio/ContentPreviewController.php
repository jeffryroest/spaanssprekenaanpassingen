<?php

namespace App\Http\Controllers\ContentStudio;

use App\ContentStudio\PlayableContentInspector;
use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ContentPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        ContentNode $contentNode,
        PlayableContentInspector $inspector,
    ): Response {
        Gate::authorize('view', $contentNode);
        $contentNode->load(['localizations', 'revisions.mediaAssets']);
        $version = $request->integer('version');
        abort_if($version < 1, 404);
        $revision = $contentNode->revisions->firstWhere('version', $version);
        abort_if($revision === null, 404);

        $domainData = data_get($revision->snapshot, 'domain_data', []);
        abort_unless(is_array($domainData) && filled($domainData['scene'] ?? null), 422, 'Deze content heeft geen speelbare preview.');

        $localizations = data_get($revision->snapshot, 'localizations', []);
        $localization = collect(is_array($localizations) ? $localizations : [])
            ->firstWhere('locale', $contentNode->default_locale)
            ?? collect(is_array($localizations) ? $localizations : [])->first();

        return response()->view('content-studio.previews.show', [
            'contentNode' => $contentNode,
            'revision' => $revision,
            'localization' => is_array($localization) ? $localization : [],
            'domainData' => $domainData,
            'inspection' => $inspector->inspect($contentNode->content_type, $domainData),
            'mediaByRole' => $revision->mediaAssets->keyBy(fn ($asset) => $asset->pivot->role),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }
}
