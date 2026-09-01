<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CompletePersonalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'completion_key' => ['bail', 'required', 'uuid'],
            'cards' => ['bail', 'required', 'array', 'min:1', 'max:5'],
            'cards.*' => ['required', 'array:practice_key,source,assisted,rating'],
            'cards.*.practice_key' => ['bail', 'required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'cards.*.source' => ['required', Rule::in(['speech', 'typed_assist'])],
            'cards.*.assisted' => ['required', 'boolean'],
            'cards.*.rating' => ['required', Rule::in(['again', 'hard', 'good', 'easy'])],
            'answer' => ['prohibited'],
            'response' => ['prohibited'],
            'transcript' => ['prohibited'],
            'audio' => ['prohibited'],
            'feedback' => ['prohibited'],
            'cards.*.answer' => ['prohibited'],
            'cards.*.response' => ['prohibited'],
            'cards.*.transcript' => ['prohibited'],
            'cards.*.audio' => ['prohibited'],
            'cards.*.feedback' => ['prohibited'],
        ];
    }

    /** @return list<array{practice_key: string, source: string, assisted: bool, rating: string}> */
    public function cards(): array
    {
        return array_map(static fn (array $card): array => [
            'practice_key' => $card['practice_key'],
            'source' => $card['source'],
            'assisted' => (bool) $card['assisted'],
            'rating' => $card['rating'],
        ], $this->validated('cards'));
    }
}
