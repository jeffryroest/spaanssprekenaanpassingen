<?php

namespace App\Access;

use App\ContentApi\PublishedContentRepository;
use App\ContentApi\RuntimeContentAccess;
use App\Enums\ContentType;
use App\Models\User;

final class TrialWeekCatalog
{
    public function __construct(
        private readonly PublishedContentRepository $content,
        private readonly RuntimeContentAccess $runtimeAccess,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user, EntitlementSnapshot $access): array
    {
        $completed = $user->missionProgress()
            ->where('status', 'completed')
            ->pluck('mission_key')
            ->all();

        return array_map(function (array $day) use ($access, $completed): array {
            $isCompleted = in_array($day['mission_key'], $completed, true);
            $contentState = $this->contentState($day);
            $accessState = $this->accessState($day['day'], $contentState, $isCompleted, $access);
            $actionUrl = in_array($accessState, ['available', 'completed'], true) && $contentState === 'published'
                ? route($day['route'])
                : null;
            unset($day['conversation_slug'], $day['expected_scene'], $day['route']);

            return $day + [
                'content_state' => $contentState,
                'access_state' => $accessState,
                'action_url' => $actionUrl,
            ];
        }, $this->days());
    }

    /** @return list<array<string, mixed>> */
    private function days(): array
    {
        return [
            ['day' => 1, 'mission_key' => 'mission.madrid.panaderia.breakfast', 'title_es' => 'La panadería', 'title_nl' => 'Bestel je ontbijt', 'setting' => 'La Espiga · Lucía', 'route' => 'game.madrid.panaderia', 'conversation_slug' => null, 'expected_scene' => null],
            ['day' => 2, 'mission_key' => 'mission.madrid.taxi.ride', 'title_es' => 'En taxi', 'title_nl' => 'Vertel waar je heen wilt', 'setting' => 'Madrid · Diego', 'route' => 'game.madrid.taxi', 'conversation_slug' => 'taxi-diego', 'expected_scene' => 'taxi_text_dialogue'],
            ['day' => 3, 'mission_key' => 'mission.madrid.restaurant.order', 'title_es' => 'En el restaurante', 'title_nl' => 'Vraag een tafel en bestel', 'setting' => 'Café El Reloj · Carmen', 'route' => 'game.madrid.restaurant', 'conversation_slug' => 'restaurant-el-reloj', 'expected_scene' => 'restaurant_text_dialogue'],
            ['day' => 4, 'mission_key' => 'mission.madrid.review.personal', 'title_es' => 'Mi repaso', 'title_nl' => 'Persoonlijke herhaling', 'setting' => 'Woorden en zinnen op maat', 'route' => 'trial-week.show', 'conversation_slug' => null, 'expected_scene' => null],
            ['day' => 5, 'mission_key' => 'mission.madrid.health.appointment', 'title_es' => 'En la consulta', 'title_nl' => 'Leg een fictieve klacht uit', 'setting' => 'Consulta La Luz · Elena', 'route' => 'game.madrid.health', 'conversation_slug' => 'consulta-elena', 'expected_scene' => 'health_text_dialogue'],
            ['day' => 6, 'mission_key' => 'mission.madrid.station.ticket', 'title_es' => 'En la estación', 'title_nl' => 'Regel je treinreis', 'setting' => 'Estación del Centro · Mateo', 'route' => 'game.madrid.station', 'conversation_slug' => 'estacion-mateo', 'expected_scene' => 'station_text_dialogue'],
            ['day' => 7, 'mission_key' => 'mission.madrid.week.final', 'title_es' => 'El reto final', 'title_nl' => 'Rond je proefweek af', 'setting' => 'Madrid · slotmissie', 'route' => 'trial-week.show', 'conversation_slug' => null, 'expected_scene' => null],
        ];
    }

    /** @param array<string, mixed> $day */
    private function contentState(array $day): string
    {
        if ($day['day'] === 1) {
            return 'published';
        }

        $slug = $day['conversation_slug'];
        if (! is_string($slug)) {
            return 'planned';
        }

        $node = $this->content->find(ContentType::ConversationScenario, $slug);
        $releaseItem = $node === null ? null : $this->content->latestProductionItem($node);

        $scene = data_get($releaseItem?->contentRevision?->snapshot, 'domain_data.scene');

        return $releaseItem !== null
            && $scene === $day['expected_scene']
            && $this->runtimeAccess->allowsEntitlement($releaseItem, 'trial_week')
            ? 'published'
            : 'planned';
    }

    private function accessState(int $day, string $contentState, bool $completed, EntitlementSnapshot $access): string
    {
        if ($completed) {
            return 'completed';
        }

        if ($day === 1) {
            return 'available';
        }

        if (! $access->allows('trial_week')) {
            return 'requires_access';
        }

        if ($access->state === 'trialing' && ($access->trialDay ?? 1) < $day) {
            return 'scheduled';
        }

        return $contentState === 'published' ? 'available' : 'planned';
    }
}
