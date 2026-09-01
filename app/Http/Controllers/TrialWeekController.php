<?php

namespace App\Http\Controllers;

use App\Access\EntitlementService;
use App\Access\TrialWeekCatalog;
use App\Billing\MollieMonthlyOffer;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TrialWeekController extends Controller
{
    public function show(
        Request $request,
        EntitlementService $entitlements,
        TrialWeekCatalog $catalog,
        MollieMonthlyOffer $offer,
    ): View {
        $snapshot = $entitlements->snapshotFor($request->user());
        $nameParts = preg_split('/\s+/', trim($request->user()->name), 2) ?: [];

        return view('player.trial-week', [
            'access' => $snapshot->toArray(),
            'days' => $catalog->forUser($request->user(), $snapshot),
            'offer' => $offer->presentation(),
            'buyer' => [
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'email' => $request->user()->email,
            ],
            'latestOrder' => SubscriptionOrder::query()
                ->where('user_id', $request->user()->getKey())
                ->latest('id')
                ->first(),
            'mollieSubscription' => Subscription::query()
                ->where('user_id', $request->user()->getKey())
                ->where('provider', 'mollie')
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue, SubscriptionStatus::Cancelled])
                ->latest('id')
                ->first(),
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
