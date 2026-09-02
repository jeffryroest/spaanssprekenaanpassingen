<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\Exceptions\CheckoutUnavailable;
use App\Billing\StartMollieCheckout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StartMollieCheckoutRequest;
use Illuminate\Http\RedirectResponse;

final class StartMollieCheckoutController extends Controller
{
    public function __invoke(
        StartMollieCheckoutRequest $request,
        StartMollieCheckout $startCheckout,
    ): RedirectResponse {
        $validated = $request->validated();

        try {
            $checkout = $startCheckout->handle(
                user: $request->user(),
                firstName: trim($validated['first_name']),
                lastName: trim($validated['last_name']),
                email: mb_strtolower(trim($validated['email'])),
            );
        } catch (CheckoutUnavailable $exception) {
            return to_route('trial-week.show')->with('access_notice', $exception->getMessage());
        } catch (BillingProviderUnavailable) {
            return to_route('trial-week.show')->with(
                'access_notice',
                'Mollie is tijdelijk niet bereikbaar. Er is niets afgeschreven; probeer het later opnieuw.',
            );
        }

        return redirect()->away($checkout->checkoutUrl);
    }
}
