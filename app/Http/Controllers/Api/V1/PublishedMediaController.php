<?php

namespace App\Http\Controllers\Api\V1;

use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublishedMediaController extends Controller
{
    public function __invoke(
        Request $request,
        PublishedContentRepository $repository,
        string $contentType,
        string $slug,
        int $version,
        string $role,
    ): Response {
        $type = ContentType::tryFrom($contentType);

        if (! in_array($type, [
            ContentType::Region,
            ContentType::Location,
            ContentType::Mission,
            ContentType::ConversationScenario,
        ], true)) {
            abort(404);
        }

        $contentNode = $repository->findPublic($type, $slug);
        $releaseItem = $contentNode === null ? null : $repository->latestProductionItem($contentNode);

        if ($releaseItem === null || $releaseItem->version !== $version) {
            abort(404);
        }

        $asset = $releaseItem->contentRevision?->mediaAssets
            ?->first(fn ($asset): bool => $asset->pivot->role === $role);

        if ($asset === null
            || ! $asset->isPublishable()
            || ! Storage::disk($asset->disk)->exists($asset->object_key)) {
            abort(404);
        }

        $etag = '"'.$asset->checksum_sha256.'"';
        $headers = [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Type' => $asset->mime_type,
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $asset->original_name,
                'media.'.pathinfo($asset->original_name, PATHINFO_EXTENSION),
            ),
            'Content-Length' => (string) $asset->byte_size,
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->headers->get('If-None-Match') === $etag) {
            unset($headers['Content-Length'], $headers['Content-Type'], $headers['Content-Disposition']);

            return response('', 304, $headers);
        }

        return new StreamedResponse(function () use ($asset): void {
            $stream = Storage::disk($asset->disk)->readStream($asset->object_key);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, $headers);
    }
}
