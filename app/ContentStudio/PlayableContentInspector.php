<?php

namespace App\ContentStudio;

use App\Enums\ContentType;

final class PlayableContentInspector
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{errors: list<string>, warnings: list<string>, facts: array<string, int|string>}
     */
    public function inspect(?ContentType $contentType, array $data): array
    {
        if ($data === []) {
            return ['errors' => [], 'warnings' => [], 'facts' => []];
        }

        $scene = $data['scene'] ?? null;
        $errors = [];
        $warnings = [];

        if (! is_string($scene) || $scene === '') {
            return [
                'errors' => ['Speeldata vereist een ondersteund scene-contract.'],
                'warnings' => [],
                'facts' => [],
            ];
        }

        if (($data['schema_version'] ?? null) !== '1.0.0') {
            $errors[] = 'Een speelbaar contract moet schema_version 1.0.0 gebruiken.';
        }

        $facts = match ($scene) {
            'madrid_hub' => $this->inspectMadridHub($contentType, $data, $errors, $warnings),
            'panaderia_text_dialogue',
            'taxi_text_dialogue',
            'restaurant_text_dialogue',
            'health_text_dialogue' => $this->inspectDialogue($contentType, $data, $scene, $errors, $warnings),
            default => $this->unsupportedScene($scene, $errors),
        };

        return [
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
            'facts' => $facts,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array<string, int|string>
     */
    private function inspectMadridHub(?ContentType $contentType, array $data, array &$errors, array &$warnings): array
    {
        if ($contentType !== ContentType::Region) {
            $errors[] = 'Het madrid_hub-contract hoort bij contenttype Regio.';
        }

        $intro = is_array($data['intro'] ?? null) ? $data['intro'] : [];
        foreach (['eyebrow', 'title', 'description', 'objective'] as $field) {
            $this->requireString($intro, $field, "Introveld {$field}", $errors);
        }

        $hotspots = is_array($data['hotspots'] ?? null) ? $data['hotspots'] : [];
        $inspectables = is_array($data['inspectables'] ?? null) ? $data['inspectables'] : [];

        if (count($hotspots) < 4) {
            $errors[] = 'De Madrid-wereld vereist minimaal vier hotspots.';
        }

        if (count($inspectables) < 3) {
            $errors[] = 'De Madrid-wereld vereist minimaal drie onderzoekspunten.';
        }

        $this->inspectUniqueIds($hotspots, 'Hotspot', $errors);
        $this->inspectUniqueIds($inspectables, 'Onderzoekspunt', $errors);

        $openHotspots = 0;
        foreach ($hotspots as $index => $hotspot) {
            if (! is_array($hotspot)) {
                $errors[] = 'Iedere hotspot moet een object zijn.';

                continue;
            }

            $label = 'Hotspot '.($index + 1);
            foreach (['id', 'kind', 'description', 'state'] as $field) {
                $this->requireString($hotspot, $field, "{$label} · {$field}", $errors);
            }

            $this->requireTranslation($hotspot['label'] ?? null, "{$label} · label", $errors);
            $this->inspectPosition($hotspot['position'] ?? null, $label, $errors);

            if (! in_array($hotspot['state'] ?? null, ['open', 'locked'], true)) {
                $errors[] = "{$label} gebruikt een onbekende toestand.";
            }

            if (($hotspot['state'] ?? null) === 'open') {
                $openHotspots++;
            }

            $action = is_array($hotspot['action'] ?? null) ? $hotspot['action'] : [];
            if (! in_array($action['type'] ?? null, ['mission_preview', 'preview'], true)
                || ! $this->nonEmptyString($action['target'] ?? null)) {
                $errors[] = "{$label} vereist een geldige previewactie en doelmissie.";
            }
        }

        if ($openHotspots === 0) {
            $errors[] = 'De wereld vereist minimaal één geopende hotspot.';
        }

        foreach ($inspectables as $index => $inspectable) {
            if (! is_array($inspectable)) {
                $errors[] = 'Ieder onderzoekspunt moet een object zijn.';

                continue;
            }

            $label = 'Onderzoekspunt '.($index + 1);
            foreach (['id', 'kind', 'title', 'body'] as $field) {
                $this->requireString($inspectable, $field, "{$label} · {$field}", $errors);
            }
            $this->inspectPosition($inspectable['position'] ?? null, $label, $errors);

            if ((int) data_get($inspectable, 'reward.curiosidad', 0) < 1) {
                $warnings[] = "{$label} geeft nog geen curiosidad-beloning.";
            }
        }

        return [
            'scene' => 'madrid_hub',
            'hotspots' => count($hotspots),
            'inspectables' => count($inspectables),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array<string, int|string>
     */
    private function inspectDialogue(
        ?ContentType $contentType,
        array $data,
        string $scene,
        array &$errors,
        array &$warnings,
    ): array {
        if ($contentType !== ContentType::ConversationScenario) {
            $errors[] = "Het {$scene}-contract hoort bij contenttype Gespreksscenario.";
        }

        $npc = is_array($data['npc'] ?? null) ? $data['npc'] : [];
        foreach (['id', 'name', 'description'] as $field) {
            $this->requireString($npc, $field, "NPC · {$field}", $errors);
        }
        $this->requireTranslation($npc['role'] ?? null, 'NPC · rol', $errors);

        $mission = is_array($data['mission'] ?? null) ? $data['mission'] : [];
        foreach (['id', 'objective', 'start_step'] as $field) {
            $this->requireString($mission, $field, "Missie · {$field}", $errors);
        }
        $this->requireTranslation($mission['title'] ?? null, 'Missie · titel', $errors);
        $expectedMissionIds = [
            'panaderia_text_dialogue' => 'mission.madrid.panaderia.breakfast',
            'taxi_text_dialogue' => 'mission.madrid.taxi.ride',
            'restaurant_text_dialogue' => 'mission.madrid.restaurant.order',
            'health_text_dialogue' => 'mission.madrid.health.appointment',
        ];
        if (($mission['id'] ?? null) !== $expectedMissionIds[$scene]) {
            $errors[] = "Het {$scene}-contract vereist missie-id {$expectedMissionIds[$scene]}.";
        }
        if (($mission['required_text_turns'] ?? null) !== 5) {
            $errors[] = 'De huidige gespreksmotor vereist exact vijf actieve beurten.';
        }

        if ($scene === 'panaderia_text_dialogue'
            && ! in_array(data_get($data, 'runtime_access.visibility'), [null, 'public'], true)) {
            $errors[] = 'De eerste bakkerijmissie moet openbaar bereikbaar blijven.';
        }

        if (in_array($scene, ['taxi_text_dialogue', 'restaurant_text_dialogue', 'health_text_dialogue'], true)
            && (data_get($data, 'runtime_access.visibility') !== 'entitled'
                || data_get($data, 'runtime_access.entitlement') !== 'trial_week')) {
            $errors[] = 'Een afgeschermde proefweekmissie moet het recht trial_week vereisen.';
        }

        $steps = is_array($data['steps'] ?? null) ? $data['steps'] : [];
        if (count($steps) < 7) {
            $errors[] = 'Een speelbaar gesprek vereist minimaal zeven route- en gespreksstappen.';
        }
        $this->inspectUniqueIds($steps, 'Stap', $errors);

        $stepMap = [];
        $optionCount = 0;
        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                $errors[] = 'Iedere gespreksstap moet een object zijn.';

                continue;
            }

            $label = 'Stap '.($index + 1);
            $id = $step['id'] ?? null;
            if ($this->nonEmptyString($id)) {
                $stepMap[$id] = $step;
                $label = "Stap {$id}";
            }

            if (! is_int($step['turn'] ?? null) || (int) $step['turn'] < 1) {
                $errors[] = "{$label} vereist een positief beurtnummer.";
            }
            $this->requireTranslation($step['npc_line'] ?? null, "{$label} · NPC-regel", $errors);
            foreach (['prompt', 'placeholder', 'hint'] as $field) {
                $this->requireString($step, $field, "{$label} · {$field}", $errors);
            }

            $choices = is_array($step['choices'] ?? null) ? $step['choices'] : [];
            if ($choices === [] || collect($choices)->contains(fn ($choice) => ! $this->nonEmptyString($choice))) {
                $errors[] = "{$label} vereist minimaal één volledig voorbeeldantwoord.";
            }

            $fallback = is_array($step['fallback'] ?? null) ? $step['fallback'] : [];
            foreach (['strength', 'focus'] as $field) {
                $this->requireString($fallback, $field, "{$label} · fallback {$field}", $errors);
            }

            $options = is_array($step['options'] ?? null) ? $step['options'] : [];
            if ($options === []) {
                $errors[] = "{$label} vereist minimaal één geldige routeoptie.";
            }

            $this->inspectUniqueIds($options, "{$label} · optie", $errors);
            foreach ($options as $optionIndex => $option) {
                if (! is_array($option)) {
                    $errors[] = "{$label} bevat een ongeldige routeoptie.";

                    continue;
                }
                $optionCount++;
                $optionLabel = "{$label} · optie ".($optionIndex + 1);
                $this->requireString($option, 'id', "{$optionLabel} · id", $errors);
                $this->requireString($option, 'next', "{$optionLabel} · volgende stap", $errors);
                $this->requireTranslation($option['npc_response'] ?? null, "{$optionLabel} · NPC-reactie", $errors);

                $feedback = is_array($option['feedback'] ?? null) ? $option['feedback'] : [];
                foreach (['strength', 'focus'] as $field) {
                    $this->requireString($feedback, $field, "{$optionLabel} · feedback {$field}", $errors);
                }

                $requirements = is_array($option['requirements'] ?? null) ? $option['requirements'] : [];
                if ($requirements === []) {
                    $errors[] = "{$optionLabel} vereist minimaal één herkenningsgroep.";
                }
                foreach ($requirements as $group) {
                    if (! is_array($group) || $group === []
                        || collect($group)->contains(fn ($term) => ! $this->nonEmptyString($term))) {
                        $errors[] = "{$optionLabel} bevat een lege herkenningsgroep.";
                        break;
                    }
                }
            }
        }

        $startStep = $mission['start_step'] ?? null;
        if ($this->nonEmptyString($startStep) && ! array_key_exists($startStep, $stepMap)) {
            $errors[] = "Het startpunt {$startStep} verwijst niet naar een bestaande stap.";
        }

        $branches = is_array($data['level_branches'] ?? null) ? $data['level_branches'] : [];
        foreach (['A0', 'A1', 'A2'] as $level) {
            $branch = $branches[$level] ?? null;
            if (! $this->nonEmptyString($branch) || ! array_key_exists($branch, $stepMap)) {
                $errors[] = "Niveau {$level} verwijst niet naar een bestaande route.";
            }
        }

        foreach ($stepMap as $stepId => $step) {
            foreach (($step['options'] ?? []) as $option) {
                if (! is_array($option)) {
                    continue;
                }
                $next = $option['next'] ?? null;
                if ($this->nonEmptyString($next)
                    && ! in_array($next, ['@complete', '@complication'], true)
                    && ! array_key_exists($next, $stepMap)) {
                    $errors[] = "Stap {$stepId} verwijst naar ontbrekende vervolgstap {$next}.";
                }
            }
        }

        foreach (['A0', 'A1', 'A2'] as $level) {
            if ($this->nonEmptyString($startStep) && isset($stepMap[$startStep], $branches[$level])) {
                $this->inspectRoute($level, $startStep, $stepMap, $branches, $errors);
            }
        }

        $repair = is_array($data['repair'] ?? null) ? $data['repair'] : [];
        if (! is_array($repair['terms'] ?? null) || count($repair['terms']) < 2) {
            $errors[] = 'De herstelroute vereist minimaal twee bruikbare herstelzinnen.';
        }
        $this->requireTranslation($repair['npc_response'] ?? null, 'Herstelroute · NPC-reactie', $errors);
        $repairFeedback = is_array($repair['feedback'] ?? null) ? $repair['feedback'] : [];
        foreach (['strength', 'focus'] as $field) {
            $this->requireString($repairFeedback, $field, "Herstelroute · feedback {$field}", $errors);
        }

        $completion = is_array($data['completion'] ?? null) ? $data['completion'] : [];
        $this->requireTranslation($completion['default_farewell'] ?? null, 'Afronding · afscheid', $errors);
        $rewards = is_array($completion['rewards'] ?? null) ? $completion['rewards'] : [];
        foreach (['xp' => 500, 'confianza' => 10, 'valentia' => 10] as $reward => $maximum) {
            if (! is_int($rewards[$reward] ?? null)
                || (int) $rewards[$reward] < 0
                || (int) $rewards[$reward] > $maximum) {
                $errors[] = "Beloning {$reward} moet een geheel getal tussen 0 en {$maximum} zijn.";
            }
        }
        if ($scene === 'panaderia_text_dialogue' && ($rewards['xp'] ?? null) !== 80) {
            $errors[] = 'De huidige bakkerijvoortgang vereist exact 80 basis-XP.';
        }

        if ($scene === 'health_text_dialogue') {
            $roleplay = is_array($data['roleplay'] ?? null) ? $data['roleplay'] : [];
            if (($roleplay['fictional'] ?? null) !== true
                || ! is_array($roleplay['facts'] ?? null)
                || count($roleplay['facts']) < 4) {
                $errors[] = 'De gezondheidsmissie vereist een expliciet fictieve rolkaart met minimaal vier feiten.';
            }
            foreach (['description', 'privacy_notice', 'medical_disclaimer'] as $field) {
                $this->requireString($roleplay, $field, "Rolkaart · {$field}", $errors);
            }
        }

        return [
            'scene' => $scene,
            'steps' => count($steps),
            'options' => $optionCount,
            'levels' => count(array_intersect_key($branches, array_flip(['A0', 'A1', 'A2']))),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $stepMap
     * @param  array<string, mixed>  $branches
     * @param  list<string>  $errors
     */
    private function inspectRoute(string $level, string $stepId, array $stepMap, array $branches, array &$errors): void
    {
        $walk = function (string $current, array $seen) use (&$walk, $level, $stepMap, $branches, &$errors): void {
            if ($current === '@complete') {
                return;
            }

            if ($current === '@complication') {
                $current = (string) ($branches[$level] ?? '');
            }

            if ($current === '' || ! isset($stepMap[$current])) {
                return;
            }

            if (in_array($current, $seen, true)) {
                $errors[] = "Niveau {$level} bevat een routecyclus bij {$current}.";

                return;
            }

            $nextSeen = [...$seen, $current];
            $options = is_array($stepMap[$current]['options'] ?? null) ? $stepMap[$current]['options'] : [];
            foreach ($options as $option) {
                if (! is_array($option) || ! $this->nonEmptyString($option['next'] ?? null)) {
                    continue;
                }
                $walk((string) $option['next'], $nextSeen);
            }
        };

        $before = count($errors);
        $walk($stepId, []);

        if (count($errors) === $before && ! $this->routeReachesCompletion($level, $stepId, $stepMap, $branches, [])) {
            $errors[] = "Niveau {$level} heeft geen volledige route van start tot afronding.";
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $stepMap
     * @param  array<string, mixed>  $branches
     * @param  list<string>  $seen
     */
    private function routeReachesCompletion(
        string $level,
        string $current,
        array $stepMap,
        array $branches,
        array $seen,
    ): bool {
        if ($current === '@complete') {
            return true;
        }

        if ($current === '@complication') {
            $current = (string) ($branches[$level] ?? '');
        }

        if ($current === '' || ! isset($stepMap[$current]) || in_array($current, $seen, true)) {
            return false;
        }

        $seen[] = $current;
        foreach (($stepMap[$current]['options'] ?? []) as $option) {
            if (is_array($option)
                && $this->nonEmptyString($option['next'] ?? null)
                && $this->routeReachesCompletion($level, (string) $option['next'], $stepMap, $branches, $seen)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<mixed> $items @param list<string> $errors */
    private function inspectUniqueIds(array $items, string $label, array &$errors): void
    {
        $ids = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! $this->nonEmptyString($item['id'] ?? null)) {
                $errors[] = "{$label} vereist een unieke id.";

                continue;
            }
            if (in_array($item['id'], $ids, true)) {
                $errors[] = "{$label}-id {$item['id']} komt meer dan één keer voor.";
            }
            $ids[] = $item['id'];
        }
    }

    /** @param list<string> $errors */
    private function inspectPosition(mixed $position, string $label, array &$errors): void
    {
        if (! is_array($position)) {
            $errors[] = "{$label} vereist een positie op de kaart.";

            return;
        }

        foreach (['x', 'y'] as $axis) {
            $value = $position[$axis] ?? null;
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                $errors[] = "{$label} · {$axis} moet tussen 0 en 100 liggen.";
            }
        }
    }

    /** @param list<string> $errors */
    private function requireTranslation(mixed $value, string $label, array &$errors): void
    {
        if (! is_array($value)
            || ! $this->nonEmptyString($value['es'] ?? null)
            || ! $this->nonEmptyString($value['nl'] ?? null)) {
            $errors[] = "{$label} vereist Spaanse en Nederlandse tekst.";
        }
    }

    /** @param array<string, mixed> $data @param list<string> $errors */
    private function requireString(array $data, string $field, string $label, array &$errors): void
    {
        if (! $this->nonEmptyString($data[$field] ?? null)) {
            $errors[] = "{$label} is verplicht.";
        }
    }

    private function nonEmptyString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /** @param list<string> $errors @return array<string, int|string> */
    private function unsupportedScene(string $scene, array &$errors): array
    {
        $errors[] = "Het speelcontract {$scene} wordt nog niet ondersteund.";

        return ['scene' => $scene];
    }
}
