<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\TranscribeSpeechRequest;
use App\Speech\Contracts\Transcriber;
use App\Speech\Exceptions\TranscriptionFailed;
use Illuminate\Http\JsonResponse;

class SpeechTranscriptionController extends Controller
{
    public function __invoke(TranscribeSpeechRequest $request, Transcriber $transcriber): JsonResponse
    {
        try {
            $result = $transcriber->transcribe($request->file('audio'));
        } catch (TranscriptionFailed $exception) {
            return response()->json([
                'schema_version' => '1.0.0',
                'error' => [
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                ],
            ], $exception->status);
        }

        return response()->json([
            'schema_version' => '1.0.0',
            'data' => $result->toArray(),
            'meta' => [
                'audio_persisted' => false,
                'maximum_duration_seconds' => 12,
            ],
        ]);
    }
}
