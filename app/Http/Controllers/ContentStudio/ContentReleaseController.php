<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\InspectContentRelease;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\StoreContentReleaseRequest;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContentReleaseController extends Controller
{
    public function index(): View
    {
        $releases = ContentRelease::query()
            ->with(['owner', 'publisher'])
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('content-studio.releases.index', compact('releases'));
    }

    public function create(): View
    {
        Gate::authorize('content-studio.publish');

        return view('content-studio.releases.create', [
            'channels' => ContentReleaseChannel::cases(),
        ]);
    }

    public function store(
        StoreContentReleaseRequest $request,
        CreateContentRelease $createContentRelease,
    ): RedirectResponse {
        $validated = $request->validated();
        $release = $createContentRelease->handle(
            actor: $request->user(),
            name: $validated['name'],
            targetChannel: ContentReleaseChannel::from($validated['target_channel']),
            description: $validated['description'] ?? null,
            desiredPublishAt: $validated['desired_publish_at'] ?? null,
        );

        return redirect()
            ->route('content-studio.releases.show', $release)
            ->with('success', 'De conceptrelease is aangemaakt.');
    }

    public function show(ContentRelease $contentRelease, InspectContentRelease $inspectContentRelease): View
    {
        $contentRelease->load([
            'items.contentNode.localizations',
            'items.contentRevision',
            'items.creator',
            'owner',
            'publisher',
            'canceller',
        ]);

        $approvedContent = collect();

        if ($contentRelease->isEditable() && Gate::allows('content-studio.publish')) {
            $approvedContent = ContentNode::query()
                ->with('localizations')
                ->where('status', ContentStatus::Approved->value)
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get();
        }

        return view('content-studio.releases.show', [
            'contentRelease' => $contentRelease,
            'approvedContent' => $approvedContent,
            'preflight' => $contentRelease->isEditable()
                ? $inspectContentRelease->handle($contentRelease)
                : ['blockers' => [], 'warnings' => []],
        ]);
    }
}
