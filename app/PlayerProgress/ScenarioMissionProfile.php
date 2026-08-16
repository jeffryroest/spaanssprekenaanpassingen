<?php

namespace App\PlayerProgress;

final readonly class ScenarioMissionProfile
{
    /**
     * @param  list<string>  $worldStates
     * @param  list<array{key: string, type: string, title: array{es: string, nl: string}}>  $extraRewards
     */
    public function __construct(
        public string $stampKey,
        public string $collectibleKey,
        public string $repairBadgeKey,
        public array $worldStates,
        public array $extraRewards = [],
    ) {}
}
