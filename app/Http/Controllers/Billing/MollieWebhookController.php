<?php

namespace App\Http\Controllers\Billing;

use App\Billing\Exceptions\BillingProviderUnavailable;
use App\Billing\MollieApiClient;
use App\Billing\ProviderWebhookInbox;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class MollieWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MollieApiClient $mollie,
        ProviderWebhookInbox $inbox,
    ): Response {
        $paymentId = $request->input('id');

        if (! is_string($paymentId) || preg_match('/^tr_[A-Za-z0-9]{8,64}$/', $paymentId) !== 1) {
            return response('', 200);
        }

        try {
            $snapshot = $mollie->fetchPayment($paymentId);
        } catch (BillingProviderUnavailable) {
            Log::warning('Mollie-webhookverificatie tijdelijk mislukt.', [
                'payment_ref_hash' => hash('sha256', $paymentId),
            ]);

            return response('', 503);
        }

        if ($snapshot !== null) {
            $inbox->record($snapshot);
        }

        return response('', 200);
    }
}
