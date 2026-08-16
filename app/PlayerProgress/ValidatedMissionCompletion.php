<?php

namespace App\PlayerProgress;

final readonly class ValidatedMissionCompletion
{
    /**
     * @param  list<array{step_id: string, turn: int, source: string, assisted: bool}>  $turns
     * @param  list<string>  $conversationStates
     */
    public function __construct(
        public array $turns,
        public array $conversationStates,
        public int $completedTurns,
        public int $spokenTurns,
        public int $assistCount,
        public int $targetXp,
        public int $targetConfianza,
        public int $targetValentia,
    ) {}
}
