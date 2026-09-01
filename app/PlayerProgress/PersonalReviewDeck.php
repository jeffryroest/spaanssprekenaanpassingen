<?php

namespace App\PlayerProgress;

use App\ContentApi\PublishedContentRepository;
use App\ContentApi\RuntimeContentAccess;
use App\Enums\ContentType;
use App\Models\MissionAttempt;
use App\Models\User;
use App\Models\UserPracticeItem;
use Illuminate\Support\Collection;

final class PersonalReviewDeck
{
    public const MAX_CARDS = 5;

    /** @var array<string, array{slug: string, scene: string, setting: string}> */
    private const SOURCES = [
        'mission.madrid.panaderia.breakfast' => ['slug' => 'la-espiga-lucia', 'scene' => 'panaderia_text_dialogue', 'setting' => 'La Espiga · Lucía'],
        'mission.madrid.taxi.ride' => ['slug' => 'taxi-diego', 'scene' => 'taxi_text_dialogue', 'setting' => 'Madrid · Diego'],
        'mission.madrid.restaurant.order' => ['slug' => 'restaurant-el-reloj', 'scene' => 'restaurant_text_dialogue', 'setting' => 'Café El Reloj · Carmen'],
        'mission.madrid.health.appointment' => ['slug' => 'consulta-elena', 'scene' => 'health_text_dialogue', 'setting' => 'Consulta La Luz · Elena'],
        'mission.madrid.station.ticket' => ['slug' => 'estacion-mateo', 'scene' => 'station_text_dialogue', 'setting' => 'Estación del Centro · Mateo'],
        'mission.madrid.week.final' => ['slug' => 'madrid-final-lucia', 'scene' => 'final_text_dialogue', 'setting' => 'La Espiga · Lucía'],
    ];

    public function __construct(
        private readonly PublishedContentRepository $content,
        private readonly RuntimeContentAccess $runtimeAccess,
    ) {}

    /** @return array{cards: list<array<string, mixed>>, meta: array<string, int|bool|string|null>} */
    public function forUser(User $user): array
    {
        $latestAttempts = MissionAttempt::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'completed')
            ->whereIn('mission_key', array_keys(self::SOURCES))
            ->latest('completed_at')
            ->get()
            ->unique('mission_key');
        $scheduled = UserPracticeItem::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy('practice_key');
        $cards = $latestAttempts
            ->flatMap(fn (MissionAttempt $attempt): array => $this->cardsForAttempt($attempt, $scheduled))
            ->sortBy([
                ['priority', 'asc'],
                ['due_sort', 'asc'],
                ['source_order', 'asc'],
                ['turn', 'asc'],
            ])
            ->take(self::MAX_CARDS)
            ->values()
            ->map(fn (array $card): array => collect($card)->except(['priority', 'due_sort', 'source_order'])->all())
            ->all();

        return [
            'cards' => $cards,
            'meta' => [
                'maximum_cards' => self::MAX_CARDS,
                'completed_sources' => $latestAttempts->count(),
                'card_count' => count($cards),
                'has_practice_history' => $scheduled->isNotEmpty(),
                'next_due_at' => $scheduled
                    ->filter(fn (UserPracticeItem $item): bool => $item->due_at->isFuture())
                    ->sortBy('due_at')
                    ->first()?->due_at?->toAtomString(),
                'generated_at' => now()->toAtomString(),
            ],
        ];
    }

    /**
     * @param  Collection<string, UserPracticeItem>  $scheduled
     * @return list<array<string, mixed>>
     */
    private function cardsForAttempt(MissionAttempt $attempt, Collection $scheduled): array
    {
        $source = self::SOURCES[$attempt->mission_key] ?? null;
        if ($source === null) {
            return [];
        }

        $node = $this->content->find(ContentType::ConversationScenario, $source['slug']);
        $releaseItem = $node === null ? null : $this->content->latestProductionItem($node);
        $domainData = $releaseItem?->contentRevision?->snapshot['domain_data'] ?? null;
        if ($releaseItem === null
            || ! is_array($domainData)
            || ($domainData['scene'] ?? null) !== $source['scene']
            || ($source['scene'] !== 'panaderia_text_dialogue'
                && ! $this->runtimeAccess->allowsEntitlement($releaseItem, 'trial_week'))) {
            return [];
        }

        $steps = collect($domainData['steps'] ?? [])
            ->filter(fn (mixed $step): bool => is_array($step))
            ->keyBy('id');
        $turns = collect($attempt->evidence['turns'] ?? [])
            ->filter(fn (mixed $turn): bool => is_array($turn));

        return $turns->map(function (array $turn) use ($attempt, $domainData, $releaseItem, $source, $steps, $scheduled): ?array {
            $stepId = is_string($turn['step_id'] ?? null) ? $turn['step_id'] : null;
            $step = $stepId === null ? null : $steps->get($stepId);
            if (! is_array($step)) {
                return null;
            }

            $practiceKey = hash('sha256', implode('|', [
                $attempt->mission_key,
                $releaseItem->version,
                $stepId,
            ]));
            $item = $scheduled->get($practiceKey);
            $due = $item?->due_at;
            $isDue = $due === null || ! $due->isFuture();
            if (! $isDue) {
                return null;
            }
            $assisted = (bool) ($turn['assisted'] ?? false);
            $sourceType = (string) ($turn['source'] ?? 'typed_assist');
            $priority = $item !== null
                ? 0
                : ($assisted ? 1 : ($sourceType === 'speech' ? 3 : 2));

            return [
                'practice_key' => $practiceKey,
                'source_mission_key' => $attempt->mission_key,
                'source_content_node_id' => (int) $releaseItem->content_node_id,
                'source_content_version' => (int) $releaseItem->version,
                'step_id' => $stepId,
                'turn' => (int) ($step['turn'] ?? 0),
                'setting' => $source['setting'],
                'mission_title' => data_get($domainData, 'mission.title.nl'),
                'npc_name' => data_get($domainData, 'npc.name'),
                'npc_line' => $step['npc_line'] ?? ['es' => '', 'nl' => ''],
                'prompt' => (string) ($step['prompt'] ?? ''),
                'hint' => (string) ($step['hint'] ?? ''),
                'examples' => array_values(array_filter($step['choices'] ?? [], 'is_string')),
                'due_state' => $item === null ? 'new' : 'due',
                'last_rating' => $item?->last_rating,
                'priority' => $priority,
                'due_sort' => $due?->getTimestamp() ?? 0,
                'source_order' => array_search($attempt->mission_key, array_keys(self::SOURCES), true),
            ];
        })->filter()->values()->all();
    }
}
