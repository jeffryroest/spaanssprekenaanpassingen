<?php

namespace App\Http\Controllers\Game;

use App\Actions\PlayerProgress\CompletePanaderiaMission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CompletePanaderiaMissionRequest;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;
use Illuminate\Http\JsonResponse;

class CompletePanaderiaMissionController extends Controller
{
    public function __invoke(
        CompletePanaderiaMissionRequest $request,
        CompletePanaderiaMission $completeMission,
    ): JsonResponse {
        try {
            $data = $completeMission->handle(
                user: $request->user(),
                completionKey: $request->string('completion_key')->toString(),
                level: $request->string('level')->toString(),
                turns: $request->turns(),
                usedRepairStrategy: $request->boolean('used_repair_strategy'),
            );
        } catch (ProgressRecordingFailed $exception) {
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
            'data' => $data,
            'meta' => [
                'account_persisted' => true,
                'idempotent' => true,
                'audio_persisted' => false,
                'transcript_persisted' => false,
                'feedback_persisted' => false,
            ],
        ]);
    }
}
