<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\MollieApiClient;
use App\Billing\ProcessMolliePayment;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MollieReturnController extends Controller
{
    public function __invoke(
        Request $request,
        SubscriptionOrder $subscriptionOrder,
        MollieApiClient $mollie,
        ProcessMolliePayment $processor,
    ): View {
        abort_unless($subscriptionOrder->user_id === $request->user()->getKey(), 404);

        $notice = null;

        if ($subscriptionOrder->provider_payment_ref !== null) {
            try {
                $snapshot = $mollie->fetchPayment($subscriptionOrder->provider_payment_ref);

                if ($snapshot !== null) {
                    $processor->handle($snapshot);
                    $subscriptionOrder->refresh();
                }
            } catch (BillingProviderUnavailable) {
                $notice = 'De actuele betaalstatus kon nog niet worden opgehaald. Mollie probeert dit automatisch opnieuw.';
            }
        }

        return view('billing.order', [
            'order' => $subscriptionOrder,
            'notice' => $notice,
        ]);
    }
}
