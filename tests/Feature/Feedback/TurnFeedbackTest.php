<?php

namespace Tests\Feature\Feedback;

use App\Feedback\Contracts\TurnContextResolver;
use App\Feedback\TurnContext;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnFeedbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(TurnContextResolver::class, new class implements TurnContextResolver
        {
            public function resolve(string $stepId): TurnContext
            {
                return new TurnContext(
                    scenario: 'la-espiga-lucia',
                    contentVersion: 3,
                    stepId: $stepId,
                    turn: 2,
                    npcLine: '¿Qué te pongo?',
                    prompt: 'Bestel brood en iets zoets.',
                    hint: 'Gebruik quiero of quisiera.',
                    acceptedRequirements: [[['pan'], ['napolitana', 'croissant']]],
                    repairTerms: ['¿Puede repetir?', 'Más despacio, por favor.'],
                );
            }
        });

        config()->set('feedback.openai.api_key', 'test-key');
        config()->set('feedback.openai.model', 'gpt-4o-mini');
    }

    public function test_validated_layered_feedback_never_affects_progress_or_claims_pronunciation_evidence(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode($this->assessment()),
                    ],
                ]],
            ]),
        ]);

        $response = $this->postJson(route('game.madrid.panaderia.feedback'), [
            'step_id' => 'order-products',
            'answer' => 'Quiero una pan y una napolitana, por favor.',
            'level' => 'A1',
            'source' => 'speech',
            'transcript_confidence_status' => 'low',
            'transcript_corrected' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.rubric.task_execution.score', 4)
            ->assertJsonPath('data.rubric.grammar.score', 2)
            ->assertJsonPath('data.rubric.pronunciation.score', null)
            ->assertJsonPath('data.rubric.pronunciation.status', 'not_assessed')
            ->assertJsonPath('data.overall.pronunciation_included', false)
            ->assertJsonPath('data.summary.focus.dimension', 'grammar')
            ->assertJsonPath('meta.progress_affecting', false)
            ->assertJsonPath('meta.rewards_affecting', false)
            ->assertJsonPath('meta.audio_assessed', false)
            ->assertJsonPath('meta.answer_persisted_server_side', false)
            ->assertJsonMissingPath('data.answer');

        Http::assertSent(function ($request): bool {
            $schema = $request->data()['response_format']['json_schema'] ?? [];
            $userData = json_decode($request->data()['messages'][1]['content'] ?? '', true);

            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($schema['strict'] ?? false) === true
                && ! array_key_exists('pronunciation', $schema['schema']['properties']['scores']['properties'] ?? [])
                && ($userData['step']['id'] ?? null) === 'order-products'
                && ($userData['learner']['answer_es'] ?? null) === 'Quiero una pan y una napolitana, por favor.';
        });
    }

    public function test_untrusted_model_shape_is_rejected_without_echoing_the_answer(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'scores' => ['task_execution' => 99],
                            'strength' => '<script>alert(1)</script>',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $answer = 'Dit mag niet in de foutrespons verschijnen.';
        $response = $this->postJson(route('game.madrid.panaderia.feedback'), [
            'step_id' => 'order-products',
            'answer' => $answer,
            'level' => 'A1',
            'source' => 'typed_assist',
            'transcript_confidence_status' => null,
            'transcript_corrected' => false,
        ]);

        $response
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'feedback_invalid');

        $this->assertStringNotContainsString($answer, $response->getContent());
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }

    public function test_missing_key_and_invalid_input_have_stable_safe_errors(): void
    {
        config()->set('feedback.openai.api_key', null);
        Http::fake();

        $valid = [
            'step_id' => 'order-products',
            'answer' => 'Quiero pan.',
            'level' => 'A0',
            'source' => 'typed_assist',
            'transcript_confidence_status' => null,
            'transcript_corrected' => false,
        ];

        $this->postJson(route('game.madrid.panaderia.feedback'), $valid)
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'feedback_not_configured');

        $this->postJson(route('game.madrid.panaderia.feedback'), [
            ...$valid,
            'step_id' => '../concept',
            'source' => 'raw_audio',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['step_id', 'source']);

        Http::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function assessment(): array
    {
        return [
            'scores' => [
                'task_execution' => 4,
                'comprehensibility' => 3,
                'vocabulary' => 3,
                'grammar' => 2,
                'conversation_strategy' => 3,
            ],
            'strength' => 'Je complete bestelling was meteen duidelijk.',
            'focus_dimension' => 'grammar',
            'focus_message' => 'Gebruik un bij het mannelijke woord pan.',
            'example_es' => 'Quiero un pan, por favor.',
            'retry_recommended' => false,
        ];
    }
}
