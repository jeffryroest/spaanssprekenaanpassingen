<?php

namespace App\Http\Controllers\ContentStudio;

use App\Beta\BetaCohortMetrics;
use App\Beta\BetaOperationsSnapshot;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class BetaOverviewController extends Controller
{
    public function __invoke(
        Request $request,
        BetaCohortMetrics $metrics,
        BetaOperationsSnapshot $operations,
    ): Response {
        Gate::authorize('beta.manage');

        $validated = $request->validate([
            'periode' => ['nullable', 'integer', Rule::in(BetaCohortMetrics::ALLOWED_PERIODS)],
        ]);
        $days = (int) ($validated['periode'] ?? 30);

        return response()->view('content-studio.beta.index', [
            'metrics' => $metrics->forDays($days),
            'operations' => $operations->current(),
            'periods' => BetaCohortMetrics::ALLOWED_PERIODS,
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
