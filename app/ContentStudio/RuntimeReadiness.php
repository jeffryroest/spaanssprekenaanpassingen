<?php

namespace App\ContentStudio;

use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\Models\ContentNode;
use Illuminate\Support\Facades\Storage;

final class RuntimeReadiness
{
    private readonly PublishedContentRepository $publishedContent;

    public function __construct(PublishedContentRepository $publishedContent)
    {
        $this->publishedContent = $publishedContent;
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return [
            $this->item(
                'Madrid-wereld met Consulta La Luz',
                ContentType::Region,
                'madrid',
                'madrid_hub',
                'Openbare startwereld',
                true,
                'madrid.consulta.luz',
                ['map_background'],
            ),
            $this->item(
                'La Espiga met Lucía',
                ContentType::ConversationScenario,
                'la-espiga-lucia',
                'panaderia_text_dialogue',
                'Openbare eerste missie',
                true,
                requiredMediaRoles: ['scene_background', 'npc_expression_sheet'],
            ),
            $this->item('Taxi met Diego', ContentType::ConversationScenario, 'taxi-diego', 'taxi_text_dialogue', 'Proefweek · recht vereist', false),
            $this->item('Café El Reloj met Carmen', ContentType::ConversationScenario, 'restaurant-el-reloj', 'restaurant_text_dialogue', 'Proefweek · recht vereist', false),
            $this->item('Consulta La Luz met Elena', ContentType::ConversationScenario, 'consulta-elena', 'health_text_dialogue', 'Proefweek · fictief rollenspel · recht vereist', false),
        ];
    }

    /** @return array<string, mixed> */
    private function item(
        string $label,
        ContentType $type,
        string $slug,
        string $expectedScene,
        string $scope,
        bool $public,
        ?string $requiredHotspotId = null,
        array $requiredMediaRoles = [],
    ): array {
        $publishedNode = $public
            ? $this->publishedContent->findPublic($type, $slug)
            : $this->publishedContent->find($type, $slug);
        $node = $publishedNode ?? ContentNode::query()
            ->where('content_type', $type->value)
            ->where('slug', $slug)
            ->first();
        $releaseItem = $publishedNode === null ? null : $this->publishedContent->latestProductionItem($publishedNode);
        $publishedScene = data_get($releaseItem?->contentRevision?->snapshot, 'domain_data.scene');
        $hotspots = data_get($releaseItem?->contentRevision?->snapshot, 'domain_data.hotspots', []);
        $hasRequiredHotspot = $requiredHotspotId === null || collect(is_array($hotspots) ? $hotspots : [])
            ->contains(fn (mixed $hotspot): bool => is_array($hotspot) && ($hotspot['id'] ?? null) === $requiredHotspotId);
        $publishedMediaRoles = $releaseItem?->contentRevision?->mediaAssets
            ?->filter(fn ($asset): bool => $asset->isPublishable()
                && Storage::disk($asset->disk)->exists($asset->object_key)
            )
            ->pluck('pivot.role')
            ->all() ?? [];
        $missingMediaRoles = array_values(array_diff($requiredMediaRoles, $publishedMediaRoles));
        $contractReady = $publishedNode !== null && $publishedScene === $expectedScene && $hasRequiredHotspot;
        $ready = $contractReady && $missingMediaRoles === [];

        return [
            'label' => $label,
            'slug' => $slug,
            'scope' => $scope,
            'ready' => $ready,
            'status' => $ready
                ? 'Speelbaar'
                : ($contractReady ? 'Media ontbreekt' : ($publishedNode !== null ? 'Contract ongeldig' : ($node?->status?->label() ?? 'Ontbreekt'))),
            'missing_media_roles' => $missingMediaRoles,
            'content_node' => $node,
            'template' => match ($slug) {
                'madrid' => 'madrid-hub',
                'la-espiga-lucia' => 'panaderia',
                'taxi-diego' => 'taxi',
                'restaurant-el-reloj' => 'restaurant',
                'consulta-elena' => 'health',
            },
        ];
    }
}
