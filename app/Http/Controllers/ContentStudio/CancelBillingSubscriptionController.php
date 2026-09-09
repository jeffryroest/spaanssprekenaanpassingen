<?php

namespace App\Http\Controllers\ContentStudio;

use App\Billing\CancelMollieSubscription;
use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\Exceptions\CheckoutUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CancelBillingSubscriptionController extends Controller
{
    public function __invoke(
        Request $request,
        Subscription $subscription,
        CancelMollieSubscription $cancel,
    ): RedirectResponse {
        Gate::authorize('billing.manage');
        $request->validate([
            'confirm_cancellation' => ['accepted'],
        ]);

        try {
            $cancel->handleSubscription($subscription);
        } catch (CheckoutUnavailable $exception) {
            return to_route('content-studio.billing.index')->with('billing_notice', $exception->getMessage());
        } catch (BillingProviderUnavailable) {
            return to_route('content-studio.billing.index')->with(
                'billing_notice',
                'Opzeggen bij Mollie lukte tijdelijk niet; lokaal is niets gewijzigd.',
            );
        }

        return to_route('content-studio.billing.index')->with(
            'billing_notice',
            'Het abonnement is opgezegd namens de klant. Toegang loopt door tot het betaalde periode-einde.',
        );
    }
}
