<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scenarioSlug = match (true) {
            $this->routeIs('game.madrid.taxi.feedback') => 'taxi-diego',
            $this->routeIs('game.madrid.restaurant.feedback') => 'restaurant-el-reloj',
            $this->routeIs('game.madrid.health.feedback') => 'consulta-elena',
            default => 'la-espiga-lucia',
        };

        return [
            'scenario_slug' => ['bail', 'required', Rule::in([$scenarioSlug])],
            'step_id' => ['bail', 'required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/'],
            'answer' => ['bail', 'required', 'string', 'max:300'],
            'level' => ['required', Rule::in(['A0', 'A1', 'A2'])],
            'source' => ['required', Rule::in(['speech', 'typed_assist', 'choice_assist'])],
            'transcript_confidence_status' => ['nullable', Rule::in(['ok', 'low', 'unavailable'])],
            'transcript_corrected' => ['required', 'boolean'],
        ];
    }
}
