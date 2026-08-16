<?php

namespace App\Http\Controllers\Api\V1;

use App\ContentApi\PublicApiResponder;
use App\ContentApi\PublicContentTransformer;
use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PublishedContentController extends Controller
{
    public function __construct(
        private readonly PublishedContentRepository $repository,
        private readonly PublicContentTransformer $transformer,
        private readonly PublicApiResponder $responder,
    ) {}

    public function worlds(Request $request): JsonResponse|Response
    {
        return $this->collection($request, ContentType::Region, 'worlds');
    }

    public function world(Request $request, string $slug): JsonResponse|Response
    {
        return $this->item($request, ContentType::Region, 'world', $slug);
    }

    public function locations(Request $request): JsonResponse|Response
    {
        return $this->collection($request, ContentType::Location, 'locations');
    }

    public function location(Request $request, string $slug): JsonResponse|Response
    {
        return $this->item($request, ContentType::Location, 'location', $slug);
    }

    public function missions(Request $request): JsonResponse|Response
    {
        return $this->collection($request, ContentType::Mission, 'missions');
    }

    public function mission(Request $request, string $slug): JsonResponse|Response
    {
        return $this->item($request, ContentType::Mission, 'mission', $slug);
    }

    public function conversations(Request $request): JsonResponse|Response
    {
        return $this->collection($request, ContentType::ConversationScenario, 'conversations');
    }

    public function conversation(Request $request, string $slug): JsonResponse|Response
    {
        return $this->item($request, ContentType::ConversationScenario, 'conversation', $slug);
    }

    private function collection(
        Request $request,
        ContentType $contentType,
        string $resourceName,
    ): JsonResponse|Response {
        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
        ]);
        $requestedLocale = $validated['locale'] ?? null;
        $paginator = $this->repository
            ->paginatePublic($contentType, (int) ($validated['per_page'] ?? 20))
            ->appends($request->query());
        $data = collect($paginator->items())
            ->map(function ($contentNode) use ($requestedLocale): ?array {
                $releaseItem = $this->repository->latestProductionItem($contentNode);

                if ($releaseItem === null) {
                    return null;
                }

                return $this->transformer->summary($contentNode, $releaseItem, $requestedLocale);
            })
            ->filter()
            ->values()
            ->all();
        $lastModified = collect($paginator->items())
            ->map(fn ($contentNode) => $contentNode->published_at)
            ->filter()
            ->sortDesc()
            ->first();

        return $this->responder->respond($request, [
            'schema_version' => PublicApiResponder::API_VERSION,
            'data' => $data,
            'meta' => [
                'resource' => $resourceName,
                'requested_locale' => $requestedLocale,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'previous' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], lastModified: $lastModified);
    }

    private function item(
        Request $request,
        ContentType $contentType,
        string $resourceName,
        string $slug,
    ): JsonResponse|Response {
        $validated = $request->validate([
            'locale' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
        ]);
        $contentNode = $this->repository->findPublic($contentType, $slug);

        if ($contentNode === null) {
            return $this->responder->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => 'published_content_not_found',
                    'message' => "De gepubliceerde {$resourceName} is niet gevonden.",
                ],
            ], status: 404);
        }

        $releaseItem = $this->repository->latestProductionItem($contentNode);

        if ($releaseItem === null) {
            return $this->responder->respond($request, [
                'schema_version' => PublicApiResponder::API_VERSION,
                'error' => [
                    'code' => 'publication_evidence_missing',
                    'message' => 'De productiepublicatie kan niet veilig worden vastgesteld.',
                ],
            ], status: 404);
        }

        return $this->responder->respond($request, [
            'schema_version' => PublicApiResponder::API_VERSION,
            'data' => $this->transformer->detail(
                $contentNode,
                $releaseItem,
                $validated['locale'] ?? null,
            ),
        ], lastModified: $contentNode->published_at);
    }
}
