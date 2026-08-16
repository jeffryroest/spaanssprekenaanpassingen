<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletePanaderiaMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'completion_key' => ['bail', 'required', 'uuid'],
            'level' => ['required', Rule::in(['A0', 'A1', 'A2'])],
            'used_repair_strategy' => ['required', 'boolean'],
            'turns' => ['bail', 'required', 'array', 'size:5'],
            'turns.*' => ['required', 'array:step_id,source,assisted'],
            'turns.*.step_id' => ['bail', 'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'turns.*.source' => ['required', Rule::in(['speech', 'typed_assist', 'choice_assist'])],
            'turns.*.assisted' => ['required', 'boolean'],
            'answer' => ['prohibited'],
            'transcript' => ['prohibited'],
            'audio' => ['prohibited'],
            'feedback' => ['prohibited'],
            'xp' => ['prohibited'],
            'confianza' => ['prohibited'],
            'valentia' => ['prohibited'],
            'rewards' => ['prohibited'],
            'conversation_states' => ['prohibited'],
            'turns.*.answer' => ['prohibited'],
            'turns.*.transcript' => ['prohibited'],
            'turns.*.audio' => ['prohibited'],
            'turns.*.feedback' => ['prohibited'],
            'turns.*.states' => ['prohibited'],
        ];
    }

    /**
     * @return list<array{step_id: string, source: string, assisted: bool}>
     */
    public function turns(): array
    {
        return array_map(
            static fn (array $turn): array => [
                'step_id' => $turn['step_id'],
                'source' => $turn['source'],
                'assisted' => (bool) $turn['assisted'],
            ],
            $this->validated('turns'),
        );
    }
}
