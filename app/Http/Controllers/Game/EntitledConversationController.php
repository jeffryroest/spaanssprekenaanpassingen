<?php

namespace App\Http\Controllers\Game;

use App\ContentApi\PublicApiResponder;
use App\ContentApi\PublicContentTransformer;
use App\ContentApi\PublishedContentRepository;
use App\ContentApi\RuntimeContentAccess;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class EntitledConversationController extends Controller
{
    public function __invoke(
        Request $request,
        PublishedContentRepository $repository,
        PublicContentTransformer $transformer,
        PublicApiResponder $responder,
        RuntimeContentAccess $access,
    ): JsonResponse|Response {
        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
        ]);
        $scenarioSlug = (string) $request->route('scenarioSlug');
        $contentNode = $repository->find(ContentType::ConversationScenario, $scenarioSlug);
        $releaseItem = $contentNode === null ? null : $repository->latestProductionItem($contentNode);

        if ($contentNode === null || $releaseItem === null) {
            return $responder->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => 'published_content_not_found',
                    'message' => 'De gepubliceerde conversatie is niet gevonden.',
                ],
            ], status: 404, additionalHeaders: $this->privateHeaders());
        }

        $requiredEntitlement = (string) $request->route('requiredEntitlement');

        if ($requiredEntitlement === '' || ! $access->allowsEntitlement($releaseItem, $requiredEntitlement)) {
            return $responder->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => 'content_access_contract_invalid',
                    'message' => 'De toegangsinstelling van deze conversatie is niet geldig.',
                ],
            ], status: 503, additionalHeaders: $this->privateHeaders());
        }

        $detail = $transformer->detail(
            $contentNode,
            $releaseItem,
            $validated['locale'] ?? null,
        );
        $mediaRouteName = (string) $request->route('mediaRouteName');
        if ($mediaRouteName !== '') {
            $detail['content']['media'] = $releaseItem->contentRevision->mediaAssets
                ->filter(fn ($asset): bool => $asset->isPublishable()
                    && Storage::disk($asset->disk)->exists($asset->object_key)
                )
                ->mapWithKeys(fn ($asset): array => [
                    $asset->pivot->role => [
                        'kind' => $asset->kind->value,
                        'url' => route($mediaRouteName, [
                            'version' => $releaseItem->version,
                            'role' => $asset->pivot->role,
                        ]),
                        'mime_type' => $asset->mime_type,
                        'width' => $asset->width,
                        'height' => $asset->height,
                        'alt_text' => $asset->alt_text,
                        'transcript' => $asset->transcript,
                    ],
                ])
                ->all();
        }
        $detail['links']['self'] = $request->url();

        return $responder->respond($request, [
            'schema_version' => PublicApiResponder::API_VERSION,
            'data' => $detail,
        ], lastModified: $contentNode->published_at, additionalHeaders: $this->privateHeaders());
    }

    /** @return array<string, string> */
    private function privateHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store',
            'Vary' => 'Cookie, Accept, Accept-Encoding',
        ];
    }
}
