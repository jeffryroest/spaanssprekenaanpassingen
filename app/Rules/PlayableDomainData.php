<?php

namespace App\Rules;

use App\Enums\ContentType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use JsonException;

final class PlayableDomainData implements ValidationRule
{
    public function __construct(private readonly ?ContentType $contentType) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (is_array($value)) {
            $data = $value;
        } elseif (! is_string($value)) {
            $fail('De speeldata moet als JSON worden ingevoerd.');

            return;
        } else {
            try {
                $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $fail('De speeldata bevat ongeldige JSON. Controleer komma’s, aanhalingstekens en haakjes.');

                return;
            }
        }

        if ($data === []) {
            return;
        }

        if (! is_array($data) || array_is_list($data)) {
            $fail('De speeldata moet één JSON-object zijn.');

            return;
        }

        $scene = $data['scene'] ?? null;

        if ($scene === null) {
            return;
        }

        if (($data['schema_version'] ?? null) !== '1.0.0') {
            $fail('Een speelbaar contract moet schema_version 1.0.0 gebruiken.');

            return;
        }

        match ($scene) {
            'madrid_hub' => $this->validateMadridHub($data, $fail),
            'panaderia_text_dialogue', 'taxi_text_dialogue', 'restaurant_text_dialogue' => $this->validateDialogue($data, $scene, $fail),
            default => $fail("Het speelcontract {$scene} wordt nog niet ondersteund."),
        };
    }

    /** @param array<string, mixed> $data */
    private function validateMadridHub(array $data, Closure $fail): void
    {
        if ($this->contentType !== ContentType::Region) {
            $fail('Het madrid_hub-contract hoort bij contenttype Regio.');

            return;
        }

        if (! $this->hasKeys($data, ['intro', 'hotspots', 'inspectables'])
            || ! is_array($data['hotspots'] ?? null)
            || count($data['hotspots']) < 4
            || ! is_array($data['inspectables'] ?? null)
            || count($data['inspectables']) < 3) {
            $fail('De Madrid-wereld vereist intro, minimaal vier hotspots en drie onderzoekspunten.');
        }
    }

    /** @param array<string, mixed> $data */
    private function validateDialogue(array $data, string $scene, Closure $fail): void
    {
        if ($this->contentType !== ContentType::ConversationScenario) {
            $fail("Het {$scene}-contract hoort bij contenttype Gespreksscenario.");

            return;
        }

        if (! $this->hasKeys($data, ['npc', 'mission', 'level_branches', 'repair', 'steps', 'completion'])
            || ! is_array($data['steps'] ?? null)
            || count($data['steps']) < 7) {
            $fail('Een speelbaar gesprek vereist NPC-, missie-, niveau-, herstel-, stappen- en beloningsdata met minimaal zeven stappen.');

            return;
        }

        $requiredBranches = ['A0', 'A1', 'A2'];
        $branches = is_array($data['level_branches'] ?? null) ? $data['level_branches'] : [];

        if (array_diff($requiredBranches, array_keys($branches)) !== []) {
            $fail('Een speelbaar gesprek vereist aparte A0-, A1- en A2-paden.');
        }

        if (in_array($scene, ['taxi_text_dialogue', 'restaurant_text_dialogue'], true)
            && data_get($data, 'runtime_access.visibility') !== 'entitled') {
            $fail('Een afgeschermde proefweekmissie moet runtime_access.visibility entitled gebruiken.');
        }
    }

    /** @param array<string, mixed> $data @param list<string> $keys */
    private function hasKeys(array $data, array $keys): bool
    {
        return array_diff($keys, array_keys($data)) === [];
    }
}
