<?php

namespace App\Http\Controllers\Game;

use App\Actions\PlayerProgress\CompletePersonalReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CompletePersonalReviewRequest;
use App\PlayerProgress\Exceptions\ProgressRecordingFailed;
use Illuminate\Http\JsonResponse;

final class CompletePersonalReviewController extends Controller
{
    public function __invoke(
        CompletePersonalReviewRequest $request,
        CompletePersonalReview $completeReview,
    ): JsonResponse {
        try {
            $data = $completeReview->handle(
                user: $request->user(),
                completionKey: $request->string('completion_key')->toString(),
                cards: $request->cards(),
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
                'answer_persisted' => false,
                'audio_persisted' => false,
                'transcript_persisted' => false,
                'feedback_persisted' => false,
            ],
        ]);
    }
}
