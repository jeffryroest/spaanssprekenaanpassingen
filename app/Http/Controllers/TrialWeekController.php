<?php

namespace App\Http\Controllers;

use App\Access\EntitlementService;
use App\Access\TrialWeekCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TrialWeekController extends Controller
{
    public function show(
        Request $request,
        EntitlementService $entitlements,
        TrialWeekCatalog $catalog,
    ): View {
        $snapshot = $entitlements->snapshotFor($request->user());

        return view('player.trial-week', [
            'access' => $snapshot->toArray(),
            'days' => $catalog->forUser($request->user(), $snapshot),
        ]);
    }

    public function json(
        Request $request,
        EntitlementService $entitlements,
        TrialWeekCatalog $catalog,
    ): JsonResponse {
        $snapshot = $entitlements->snapshotFor($request->user());

        return response()->json([
            'schema_version' => '1.0.0',
            'data' => [
                'access' => $snapshot->toArray(),
                'days' => $catalog->forUser($request->user(), $snapshot),
            ],
            'meta' => [
                'payment_data_included' => false,
                'provider_references_included' => false,
                'content_publication_required' => true,
            ],
        ]);
    }
}
