<?php

namespace App\Access;

use App\Models\User;

final class TrialWeekCatalog
{
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
            $accessState = $this->accessState($day['day'], $day['content_state'], $isCompleted, $access);

            return $day + [
                'access_state' => $accessState,
                'action_url' => $day['day'] === 1 && in_array($accessState, ['available', 'completed'], true)
                    ? route('game.madrid.panaderia')
                    : null,
            ];
        }, $this->days());
    }

    /** @return list<array<string, mixed>> */
    private function days(): array
    {
        return [
            ['day' => 1, 'mission_key' => 'mission.madrid.panaderia.breakfast', 'title_es' => 'La panadería', 'title_nl' => 'Bestel je ontbijt', 'setting' => 'La Espiga · Lucía', 'content_state' => 'published'],
            ['day' => 2, 'mission_key' => 'mission.madrid.taxi.ride', 'title_es' => 'En taxi', 'title_nl' => 'Vertel waar je heen wilt', 'setting' => 'Madrid · taxi', 'content_state' => 'planned'],
            ['day' => 3, 'mission_key' => 'mission.madrid.restaurant.order', 'title_es' => 'En el restaurante', 'title_nl' => 'Reserveer en bestel', 'setting' => 'Madrid · restaurant', 'content_state' => 'planned'],
            ['day' => 4, 'mission_key' => 'mission.madrid.review.personal', 'title_es' => 'Mi repaso', 'title_nl' => 'Persoonlijke herhaling', 'setting' => 'Woorden en zinnen op maat', 'content_state' => 'planned'],
            ['day' => 5, 'mission_key' => 'mission.madrid.health.appointment', 'title_es' => 'En la consulta', 'title_nl' => 'Leg een klacht uit', 'setting' => 'Madrid · gezondheid', 'content_state' => 'planned'],
            ['day' => 6, 'mission_key' => 'mission.madrid.station.ticket', 'title_es' => 'En la estación', 'title_nl' => 'Regel je treinreis', 'setting' => 'Madrid · station', 'content_state' => 'planned'],
            ['day' => 7, 'mission_key' => 'mission.madrid.week.final', 'title_es' => 'El reto final', 'title_nl' => 'Rond je proefweek af', 'setting' => 'Madrid · slotmissie', 'content_state' => 'planned'],
        ];
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
