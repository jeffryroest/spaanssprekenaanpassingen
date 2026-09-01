<?php

namespace App\Http\Controllers\Game;

use App\ContentApi\PublishedContentRepository;
use App\ContentApi\RuntimeContentAccess;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EntitledConversationMediaController extends Controller
{
    public function __invoke(
        Request $request,
        PublishedContentRepository $repository,
        RuntimeContentAccess $access,
        int $version,
        string $role,
    ): Response {
        $scenarioSlug = (string) $request->route('scenarioSlug');
        $requiredEntitlement = (string) $request->route('requiredEntitlement');
        $contentNode = $repository->find(ContentType::ConversationScenario, $scenarioSlug);
        $releaseItem = $contentNode === null ? null : $repository->latestProductionItem($contentNode);

        if ($releaseItem === null
            || $releaseItem->version !== $version
            || $requiredEntitlement === ''
            || ! $access->allowsEntitlement($releaseItem, $requiredEntitlement)) {
            abort(404);
        }

        $asset = $releaseItem->contentRevision?->mediaAssets
            ?->first(fn ($asset): bool => $asset->pivot->role === $role);

        if ($asset === null
            || ! $asset->isPublishable()
            || ! Storage::disk($asset->disk)->exists($asset->object_key)) {
            abort(404);
        }

        return new StreamedResponse(function () use ($asset): void {
            $stream = Storage::disk($asset->disk)->readStream($asset->object_key);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Cache-Control' => 'private, no-store',
            'Content-Type' => $asset->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $asset->original_name,
                'media.'.pathinfo($asset->original_name, PATHINFO_EXTENSION),
            ),
            'Content-Length' => (string) $asset->byte_size,
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Vary' => 'Cookie',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
