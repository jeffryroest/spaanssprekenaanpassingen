<?php

namespace App\Speech;

use App\Speech\Contracts\Transcriber;
use App\Speech\Exceptions\TranscriptionFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiTranscriber implements Transcriber
{
    public function transcribe(UploadedFile $audio): TranscriptionResult
    {
        $apiKey = trim((string) config('transcription.openai.api_key'));
        if ($apiKey === '') {
            throw new TranscriptionFailed(
                'transcription_not_configured',
                503,
                'De transcriptiedienst is nog niet geconfigureerd.',
            );
        }

        $path = $audio->getRealPath();
        $stream = $path === false ? false : fopen($path, 'rb');
        if ($stream === false) {
            throw new TranscriptionFailed(
                'audio_unreadable',
                422,
                'De opname kon niet worden gelezen. Neem de zin opnieuw op.',
            );
        }

        try {
            $model = (string) config('transcription.openai.model');
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->connectTimeout((int) config('transcription.openai.connect_timeout'))
                ->timeout((int) config('transcription.openai.timeout'))
                ->attach('file', $stream, 'spreekpoging.webm', ['Content-Type' => 'audio/webm'])
                ->post(rtrim((string) config('transcription.openai.base_url'), '/').'/audio/transcriptions', [
                    'model' => $model,
                    'language' => 'es',
                    'response_format' => 'json',
                    'include[]' => 'logprobs',
                    'prompt' => 'Conversación breve en una panadería de Madrid. Pan, barra, napolitana, croissant, para llevar, tarjeta y efectivo.',
                ]);
        } catch (ConnectionException) {
            throw new TranscriptionFailed(
                'transcription_unavailable',
                502,
                'De transcriptiedienst reageert niet. Probeer opnieuw of gebruik tekst.',
            );
        } catch (Throwable) {
            throw new TranscriptionFailed(
                'transcription_failed',
                502,
                'De opname kon niet worden getranscribeerd. Probeer opnieuw of gebruik tekst.',
            );
        } finally {
            fclose($stream);
        }

        if (! $response->successful()) {
            throw new TranscriptionFailed(
                'transcription_failed',
                502,
                'De opname kon niet worden getranscribeerd. Probeer opnieuw of gebruik tekst.',
            );
        }

        $transcript = trim((string) $response->json('text'));
        if ($transcript === '') {
            throw new TranscriptionFailed(
                'speech_not_detected',
                422,
                'We hoorden nog geen Spaanse zin. Neem de zin opnieuw op of gebruik tekst.',
            );
        }

        $confidence = $this->confidenceFrom($response->json('logprobs'));
        $threshold = (float) config('transcription.low_confidence_threshold');

        return new TranscriptionResult(
            transcript: $transcript,
            confidence: $confidence,
            confidenceStatus: $confidence === null ? 'unavailable' : ($confidence < $threshold ? 'low' : 'ok'),
            provider: 'openai',
            model: $model,
        );
    }

    private function confidenceFrom(mixed $logprobs): ?float
    {
        if (! is_array($logprobs)) {
            return null;
        }

        $values = array_values(array_filter(
            array_map(
                fn (mixed $item): mixed => is_array($item) ? ($item['logprob'] ?? null) : null,
                $logprobs,
            ),
            fn (mixed $value): bool => is_numeric($value),
        ));

        if ($values === []) {
            return null;
        }

        $meanLogProbability = array_sum($values) / count($values);

        return round(max(0.0, min(1.0, exp($meanLogProbability))), 3);
    }
}
