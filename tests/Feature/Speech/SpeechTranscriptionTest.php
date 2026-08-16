<?php

namespace Tests\Feature\Speech;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpeechTranscriptionTest extends TestCase
{
    public function test_webm_recording_is_transcribed_without_persisting_audio(): void
    {
        config()->set('transcription.openai.api_key', 'test-key');
        Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'Quiero una barra de pan, por favor.',
                'logprobs' => [
                    ['token' => 'Quiero', 'logprob' => -0.08],
                    ['token' => ' pan', 'logprob' => -0.12],
                ],
            ]),
        ]);

        $response = $this->post(route('game.madrid.panaderia.transcription'), [
            'audio' => $this->webm(),
            'duration_seconds' => 5.4,
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.transcript', 'Quiero una barra de pan, por favor.')
            ->assertJsonPath('data.language', 'es')
            ->assertJsonPath('data.confidence_status', 'ok')
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.maximum_duration_seconds', 12);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/audio/transcriptions'
            && $request->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_low_transcript_confidence_is_reported_without_pronunciation_score(): void
    {
        config()->set('transcription.openai.api_key', 'test-key');
        Http::fake([
            '*' => Http::response([
                'text' => 'Una napolitana.',
                'logprobs' => [['token' => 'Una', 'logprob' => -1.2]],
            ]),
        ]);

        $this->post(route('game.madrid.panaderia.transcription'), [
            'audio' => $this->webm(),
            'duration_seconds' => 2.1,
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.confidence_status', 'low')
            ->assertJsonMissingPath('data.pronunciation_score');
    }

    public function test_invalid_or_too_long_recording_is_rejected_before_provider_call(): void
    {
        config()->set('transcription.openai.api_key', 'test-key');
        Http::fake();

        $this->post(route('game.madrid.panaderia.transcription'), [
            'audio' => UploadedFile::fake()->createWithContent('antwoord.webm', 'not-a-webm'),
            'duration_seconds' => 13,
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors(['audio', 'duration_seconds']);

        Http::assertNothingSent();
    }

    public function test_missing_server_key_returns_a_stable_service_error(): void
    {
        config()->set('transcription.openai.api_key', null);

        $this->post(route('game.madrid.panaderia.transcription'), [
            'audio' => $this->webm(),
            'duration_seconds' => 3.2,
        ], ['Accept' => 'application/json'])->assertStatus(503)
            ->assertJsonPath('error.code', 'transcription_not_configured');
    }

    private function webm(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'spreekpoging.webm',
            "\x1A\x45\xDF\xA3".str_repeat("\0", 128),
        );
    }
}
