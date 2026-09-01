<?php

namespace App\PlayerProgress;

use App\Models\User;

final class NpcMemorySnapshot
{
    /**
     * Minimal, privacy-safe memory: only completed mission structure is read.
     *
     * @return array{met_count: int, returning_to_lucia: bool, encounters: list<array<string, mixed>>}
     */
    public function forUser(User $user): array
    {
        $progress = $user->missionProgress()
            ->where('status', 'completed')
            ->whereIn('mission_key', array_column($this->encounters(), 'mission_key'))
            ->get()
            ->keyBy('mission_key');

        $encounters = array_map(function (array $encounter) use ($progress): array {
            $mission = $progress->get($encounter['mission_key']);

            return $encounter + [
                'met' => $mission !== null,
                'completion_count' => (int) ($mission?->completion_count ?? 0),
                'last_completed_at' => $mission?->last_completed_at?->toAtomString(),
            ];
        }, $this->encounters());

        return [
            'met_count' => count(array_filter($encounters, fn (array $encounter): bool => $encounter['met'])),
            'returning_to_lucia' => collect($encounters)->firstWhere('npc_id', 'npc.lucia.martin')['met'] ?? false,
            'encounters' => $encounters,
        ];
    }

    /** @return list<array{npc_id: string, name: string, setting: string, mission_key: string}> */
    private function encounters(): array
    {
        return [
            ['npc_id' => 'npc.lucia.martin', 'name' => 'Lucía', 'setting' => 'La Espiga', 'mission_key' => PlayerProgressSnapshot::MISSION_KEY],
            ['npc_id' => 'npc.diego.ruiz', 'name' => 'Diego', 'setting' => 'En taxi', 'mission_key' => PlayerProgressSnapshot::TAXI_MISSION_KEY],
            ['npc_id' => 'npc.carmen.santos', 'name' => 'Carmen', 'setting' => 'Café El Reloj', 'mission_key' => PlayerProgressSnapshot::RESTAURANT_MISSION_KEY],
            ['npc_id' => 'npc.elena.ortiz', 'name' => 'Elena', 'setting' => 'Consulta La Luz', 'mission_key' => PlayerProgressSnapshot::HEALTH_MISSION_KEY],
            ['npc_id' => 'npc.mateo.alvarez', 'name' => 'Mateo', 'setting' => 'Estación del Centro', 'mission_key' => PlayerProgressSnapshot::STATION_MISSION_KEY],
        ];
    }
}
