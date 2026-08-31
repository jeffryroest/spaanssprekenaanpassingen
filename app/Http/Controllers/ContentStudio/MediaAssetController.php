<?php

namespace App\Http\Controllers\ContentStudio;

use App\Actions\ContentStudio\CreateMediaAsset;
use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentStudio\StoreMediaAssetRequest;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaAssetController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('content-studio.view');

        return view('content-studio.media.index', [
            'mediaAssets' => MediaAsset::query()->with('creator')->latest()->paginate(24),
            'mediaKinds' => MediaKind::cases(),
            'rightsStatuses' => MediaRightsStatus::cases(),
        ]);
    }

    public function store(StoreMediaAssetRequest $request, CreateMediaAsset $createMediaAsset): RedirectResponse
    {
        $validated = $request->validated();
        $createMediaAsset->handle(
            actor: $request->user(),
            file: $request->file('file'),
            kind: MediaKind::from($validated['kind']),
            title: $validated['title'],
            description: $validated['description'] ?? null,
            altText: $validated['alt_text'] ?? null,
            transcript: $validated['transcript'] ?? null,
            rightsStatus: MediaRightsStatus::from($validated['rights_status']),
            sourceName: $validated['source_name'] ?? null,
            creatorName: $validated['creator_name'] ?? null,
            licenseName: $validated['license_name'] ?? null,
            rightsExpiresAt: $validated['rights_expires_at'] ?? null,
        );

        return redirect()->route('content-studio.media.index')->with('success', 'Medium is veilig opgeslagen.');
    }

    public function stream(MediaAsset $mediaAsset): StreamedResponse
    {
        Gate::authorize('content-studio.view');
        abort_unless(Storage::disk($mediaAsset->disk)->exists($mediaAsset->object_key), 404);

        return Storage::disk($mediaAsset->disk)->response(
            $mediaAsset->object_key,
            $mediaAsset->original_name,
            [
                'Content-Type' => $mediaAsset->mime_type,
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
                'X-Robots-Tag' => 'noindex, nofollow',
            ],
        );
    }
}
