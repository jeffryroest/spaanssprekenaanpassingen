<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\PlayerProgress\NpcMemorySnapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FinalMissionController extends Controller
{
    public function __invoke(Request $request, NpcMemorySnapshot $memory): View
    {
        return view('game.final', [
            'npcMemory' => $memory->forUser($request->user()),
        ]);
    }
}
