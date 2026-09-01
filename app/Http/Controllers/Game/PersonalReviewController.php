<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\PlayerProgress\PersonalReviewDeck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PersonalReviewController extends Controller
{
    public function show(Request $request, PersonalReviewDeck $deck): View
    {
        return view('game.review', ['deck' => $deck->forUser($request->user())]);
    }

    public function json(Request $request, PersonalReviewDeck $deck): JsonResponse
    {
        return response()->json([
            'schema_version' => '1.0.0',
            'data' => $deck->forUser($request->user()),
            'meta' => [
                'answer_persisted' => false,
                'transcript_persisted' => false,
                'audio_persisted' => false,
                'feedback_persisted' => false,
            ],
        ], headers: [
            'Cache-Control' => 'private, no-store',
            'Vary' => 'Cookie, Accept, Accept-Encoding',
        ]);
    }
}
