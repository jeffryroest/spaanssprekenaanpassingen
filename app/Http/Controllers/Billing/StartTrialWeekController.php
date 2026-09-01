<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Exceptions\TrialActivationUnavailable;
use App\Billing\StartTrialWeek;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StartTrialWeekController extends Controller
{
    public function __invoke(Request $request, StartTrialWeek $startTrialWeek): RedirectResponse
    {
        try {
            $startTrialWeek->handle($request->user());
        } catch (TrialActivationUnavailable $exception) {
            return to_route('trial-week.show')->with('access_notice', $exception->getMessage());
        }

        return to_route('trial-week.show')->with(
            'access_notice',
            'Je proefweek is gestart. Vandaag kun je meteen met je eerste missiedag verder.',
        );
    }
}
