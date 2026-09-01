<?php

namespace App\Http\Controllers\Billing;

use App\Billing\CancelMollieSubscription;
use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\Exceptions\CheckoutUnavailable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CancelMollieSubscriptionController extends Controller
{
    public function __invoke(Request $request, CancelMollieSubscription $cancel): RedirectResponse
    {
        $request->validate([
            'confirm_cancellation' => ['accepted'],
        ], [
            'confirm_cancellation.accepted' => 'Bevestig dat je aan het einde van de betaalperiode wilt opzeggen.',
        ]);

        try {
            $subscription = $cancel->handle($request->user());
        } catch (CheckoutUnavailable $exception) {
            return to_route('trial-week.show')->with('access_notice', $exception->getMessage());
        } catch (BillingProviderUnavailable) {
            return to_route('trial-week.show')->with(
                'access_notice',
                'Opzeggen bij Mollie lukte tijdelijk niet. Je abonnement is nog niet gewijzigd; probeer het opnieuw.',
            );
        }

        $endsAt = $subscription->current_period_ends_at?->format('d-m-Y');

        return to_route('trial-week.show')->with(
            'access_notice',
            $endsAt === null
                ? 'Je abonnement is opgezegd.'
                : 'Je abonnement is opgezegd. Je toegang blijft actief tot en met '.$endsAt.'.',
        );
    }
}
