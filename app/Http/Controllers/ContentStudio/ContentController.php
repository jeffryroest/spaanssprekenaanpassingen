<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\ArchiveDraftContent;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\UpdateDraftContent;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\ArchiveContentRequest;
use App\Http\Requests\ContentStudio\StoreContentRequest;
use App\Http\Requests\ContentStudio\UpdateContentRequest;
use App\Models\ContentNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'content_type' => ['nullable', Rule::enum(ContentType::class)],
            'status' => ['nullable', Rule::enum(ContentStatus::class)],
        ]);

        $contentNodes = ContentNode::query()
            ->with('localizations')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('slug', 'like', "%{$search}%")
                        ->orWhere('id', ctype_digit($search) ? (int) $search : 0)
                        ->orWhereHas('localizations', function (Builder $query) use ($search): void {
                            $query
                                ->where('title', 'like', "%{$search}%")
                                ->orWhere('summary', 'like', "%{$search}%")
                                ->orWhere('body', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $filters['content_type'] ?? null,
                fn (Builder $query, string $type) => $query->where('content_type', $type),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where('status', $status),
            )
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('content-studio.content.index', [
            'contentNodes' => $contentNodes,
            'contentTypes' => ContentType::cases(),
            'contentStatuses' => ContentStatus::cases(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', ContentNode::class);

        return view('content-studio.content.create', [
            'contentTypes' => ContentType::cases(),
        ]);
    }

    public function store(StoreContentRequest $request, CreateDraftContent $createDraftContent): RedirectResponse
    {
        $validated = $request->validated();
        $contentNode = $createDraftContent->handle(
            actor: $request->user(),
            contentType: ContentType::from($validated['content_type']),
            slug: $validated['slug'],
            locale: $validated['locale'],
            title: $validated['title'],
            summary: $validated['summary'] ?? null,
            body: $validated['body'] ?? null,
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', 'Conceptcontent is veilig aangemaakt.');
    }

    public function show(ContentNode $contentNode): View
    {
        $contentNode->load(['localizations', 'revisions.creator', 'reviews.actor', 'creator', 'updater']);

        return view('content-studio.content.show', compact('contentNode'));
    }

    public function edit(ContentNode $contentNode): View
    {
        Gate::authorize('update', $contentNode);
        abort_unless($contentNode->isEditableDraft(), 409, 'Deze status kan niet worden bewerkt.');
        $contentNode->load('localizations');

        return view('content-studio.content.edit', compact('contentNode'));
    }

    public function update(
        UpdateContentRequest $request,
        ContentNode $contentNode,
        UpdateDraftContent $updateDraftContent,
    ): RedirectResponse {
        $validated = $request->validated();
        $contentNode = $updateDraftContent->handle(
            actor: $request->user(),
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
            slug: $validated['slug'],
            locale: $validated['locale'],
            title: $validated['title'],
            summary: $validated['summary'] ?? null,
            body: $validated['body'] ?? null,
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', 'Een nieuwe conceptrevisie is opgeslagen.');
    }

    public function destroy(
        ArchiveContentRequest $request,
        ContentNode $contentNode,
        ArchiveDraftContent $archiveDraftContent,
    ): RedirectResponse {
        $validated = $request->validated();
        $contentNode = $archiveDraftContent->handle(
            actor: $request->user(),
            contentNode: $contentNode,
            expectedVersion: (int) $validated['expected_version'],
            reason: $validated['reason'],
        );

        return redirect()
            ->route('content-studio.content.show', $contentNode)
            ->with('success', 'De content is gearchiveerd en blijft volledig traceerbaar.');
    }
}
