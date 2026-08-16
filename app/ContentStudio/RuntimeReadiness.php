<?php

namespace App\ContentStudio;

use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\Models\ContentNode;

final class RuntimeReadiness
{
    public function __construct(
        private readonly PublishedContentRepository $publishedContent,
    ) {}

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return [
            $this->item('Madrid-wereld', ContentType::Region, 'madrid', 'madrid_hub', 'Openbare startwereld', true),
            $this->item('La Espiga met Lucía', ContentType::ConversationScenario, 'la-espiga-lucia', 'panaderia_text_dialogue', 'Openbare eerste missie', true),
            $this->item('Taxi met Diego', ContentType::ConversationScenario, 'taxi-diego', 'taxi_text_dialogue', 'Proefweek · recht vereist', false),
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
    ): array
    {
        $publishedNode = $public
            ? $this->publishedContent->findPublic($type, $slug)
            : $this->publishedContent->find($type, $slug);
        $node = $publishedNode ?? ContentNode::query()
            ->where('content_type', $type->value)
            ->where('slug', $slug)
            ->first();
        $releaseItem = $publishedNode === null ? null : $this->publishedContent->latestProductionItem($publishedNode);
        $publishedScene = data_get($releaseItem?->contentRevision?->snapshot, 'domain_data.scene');
        $ready = $publishedNode !== null && $publishedScene === $expectedScene;

        return [
            'label' => $label,
            'slug' => $slug,
            'scope' => $scope,
            'ready' => $ready,
            'status' => $ready
                ? 'Speelbaar'
                : ($publishedNode !== null ? 'Contract ongeldig' : ($node?->status?->label() ?? 'Ontbreekt')),
            'content_node' => $node,
            'template' => match ($slug) {
                'madrid' => 'madrid-hub',
                'la-espiga-lucia' => 'panaderia',
                'taxi-diego' => 'taxi',
            },
        ];
    }
}
