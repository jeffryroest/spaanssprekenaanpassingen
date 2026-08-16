<?php

namespace App\Http\Controllers\Game;

use App\Feedback\Contracts\TurnAssessor;
use App\Feedback\Contracts\TurnContextResolver;
use App\Feedback\Exceptions\FeedbackAssessmentFailed;
use App\Feedback\LayeredFeedbackComposer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\AssessTurnRequest;
use Illuminate\Http\JsonResponse;

class TurnFeedbackController extends Controller
{
    public function __invoke(
        AssessTurnRequest $request,
        TurnContextResolver $contextResolver,
        TurnAssessor $assessor,
        LayeredFeedbackComposer $composer,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $context = $contextResolver->resolve($validated['scenario_slug'], $validated['step_id']);
            $assessment = $assessor->assess(
                context: $context,
                answer: $validated['answer'],
                source: $validated['source'],
                transcriptConfidenceStatus: $validated['transcript_confidence_status'] ?? null,
                transcriptCorrected: $validated['transcript_corrected'],
                level: $validated['level'],
            );
        } catch (FeedbackAssessmentFailed $exception) {
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
            'data' => $composer->compose($assessment, $validated['source']),
            'meta' => [
                'progress_affecting' => false,
                'rewards_affecting' => false,
                'audio_assessed' => false,
                'answer_persisted_server_side' => false,
            ],
        ]);
    }
}
