<?php

namespace App\PlayerProgress;

use App\PlayerProgress\Exceptions\ProgressRecordingFailed;

final readonly class PanaderiaMissionDefinition
{
    public const BASE_XP = 80;

    public const MAX_XP = 120;

    public const SPOKEN_GOAL = 3;

    /**
     * @param  array<string, mixed>  $domainData
     */
    public function __construct(
        public int $sourceContentNodeId,
        public int $sourceContentVersion,
        public array $domainData,
    ) {}

    public function missionKey(): string
    {
        return (string) $this->domainData['mission']['id'];
    }

    /**
     * @param  list<array{step_id: string, source: string, assisted: bool}>  $submittedTurns
     */
    public function validateCompletion(string $level, array $submittedTurns): ValidatedMissionCompletion
    {
        if (! array_key_exists($level, $this->domainData['level_branches'])) {
            throw $this->invalidRoute();
        }

        $steps = collect($this->domainData['steps'])->keyBy('id');
        $expectedStepId = (string) $this->domainData['mission']['start_step'];
        $requiredTurns = (int) $this->domainData['mission']['required_text_turns'];
        $validatedTurns = [];
        $states = [];

        if (count($submittedTurns) !== $requiredTurns) {
            throw $this->invalidRoute();
        }

        foreach ($submittedTurns as $submittedTurn) {
            if ($submittedTurn['step_id'] !== $expectedStepId) {
                throw $this->invalidRoute();
            }

            $step = $steps->get($expectedStepId);
            if (! is_array($step)) {
                throw $this->invalidRoute();
            }

            $options = is_array($step['options'] ?? null) ? $step['options'] : [];
            if (count($options) !== 1 || ! is_array($options[0])) {
                throw new ProgressRecordingFailed(
                    'mission_definition_unsupported',
                    503,
                    'De gepubliceerde missie kan nog niet veilig worden opgeslagen.',
                );
            }

            $option = $options[0];
            $source = $submittedTurn['source'];
            $assisted = (bool) $submittedTurn['assisted'] || $source === 'choice_assist';
            $validatedTurns[] = [
                'step_id' => $expectedStepId,
                'turn' => (int) $step['turn'],
                'source' => $source,
                'assisted' => $assisted,
            ];
            $states = array_values(array_unique([
                ...$states,
                ...array_values(array_filter($option['states'] ?? [], 'is_string')),
            ]));

            $next = $option['next'] ?? null;
            $expectedStepId = match ($next) {
                '@complete' => null,
                '@complication' => $this->domainData['level_branches'][$level] ?? null,
                default => is_string($next) ? $next : null,
            };
        }

        if ($expectedStepId !== null) {
            throw $this->invalidRoute();
        }

        $spokenTurns = count(array_filter($validatedTurns, fn (array $turn): bool => $turn['source'] === 'speech'));
        $assistCount = count(array_filter($validatedTurns, fn (array $turn): bool => $turn['assisted']));
        $independentTurns = max(0, $requiredTurns - $assistCount);

        return new ValidatedMissionCompletion(
            turns: $validatedTurns,
            conversationStates: $states,
            completedTurns: count($validatedTurns),
            spokenTurns: $spokenTurns,
            assistCount: $assistCount,
            targetXp: min(self::MAX_XP, self::BASE_XP + min(40, $independentTurns * 8)),
            targetConfianza: min(self::SPOKEN_GOAL, $spokenTurns),
            targetValentia: 1,
        );
    }

    /** @return array{es: string, nl: string} */
    public function stamp(): array
    {
        return $this->localizedReward('stamp', 'Mi primera compra', 'Mijn eerste aankoop');
    }

    /** @return array{es: string, nl: string} */
    public function collectible(): array
    {
        return $this->localizedReward('collectible', 'Bolsa de pan de La Espiga', 'Broodzak van La Espiga');
    }

    /** @return array{es: string, nl: string} */
    public function repairBadge(): array
    {
        return $this->localizedReward('repair_badge', 'Sin miedo', 'Zonder angst');
    }

    /** @return array{es: string, nl: string} */
    private function localizedReward(string $key, string $fallbackEs, string $fallbackNl): array
    {
        $reward = $this->domainData['completion']['rewards'][$key] ?? [];

        return [
            'es' => is_string($reward['es'] ?? null) ? $reward['es'] : $fallbackEs,
            'nl' => is_string($reward['nl'] ?? null) ? $reward['nl'] : $fallbackNl,
        ];
    }

    private function invalidRoute(): ProgressRecordingFailed
    {
        return new ProgressRecordingFailed(
            'mission_evidence_invalid',
            422,
            'De voltooide beurten vormen niet de actuele gepubliceerde missieroute.',
        );
    }
}
