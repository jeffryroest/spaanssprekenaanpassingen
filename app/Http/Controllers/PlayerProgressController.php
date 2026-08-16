<?php

namespace App\Http\Controllers;

use App\PlayerProgress\PlayerProgressSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerProgressController extends Controller
{
    public function show(Request $request, PlayerProgressSnapshot $snapshot): View
    {
        return view('player.progress', [
            'progress' => $snapshot->forUser($request->user()),
            'taxiProgress' => $snapshot->forUser(
                user: $request->user(),
                missionKey: PlayerProgressSnapshot::TAXI_MISSION_KEY,
            ),
        ]);
    }

    public function json(Request $request, PlayerProgressSnapshot $snapshot): JsonResponse
    {
        return response()->json([
            'schema_version' => '1.0.0',
            'data' => $snapshot->forUser($request->user()),
            'meta' => [
                'audio_included' => false,
                'transcript_included' => false,
                'feedback_included' => false,
            ],
        ]);
    }
}
