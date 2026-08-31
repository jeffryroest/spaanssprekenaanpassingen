<?php

namespace App\Rules;

use App\ContentStudio\PlayableContentInspector;
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

        foreach (app(PlayableContentInspector::class)->inspect($this->contentType, $data)['errors'] as $error) {
            $fail($error);
        }
    }
}
