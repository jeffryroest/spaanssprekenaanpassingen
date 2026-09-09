<?php

namespace App\Http\Controllers\ContentStudio;

use App\Enums\CheckoutPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class BillingOverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('billing.manage');

        $validated = $request->isMethod('post')
            ? $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
                'status' => ['nullable', Rule::enum(CheckoutPaymentStatus::class)],
            ])
            : [];
        $search = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;

        $ordersQuery = SubscriptionOrder::query()
            ->with(['plan', 'subscription', 'user'])
            ->when($status, fn ($query, string $value) => $query->where('payment_status', $value))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('public_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest();
        $orders = $search === '' && $status === null
            ? $ordersQuery->paginate(20)
            : $ordersQuery->limit(50)->get();

        $recentEvents = SubscriptionEvent::query()
            ->with('subscription.user')
            ->latest('received_at')
            ->limit(200)
            ->get()
            ->filter(fn (SubscriptionEvent $event): bool => $event->attentionKind() !== null)
            ->take(20)
            ->values();
        $ordersByPayment = SubscriptionOrder::query()
            ->whereIn(
                'provider_payment_ref',
                $recentEvents->pluck('event_payload.payment_id')->filter()->unique()->values(),
            )
            ->get()
            ->keyBy('provider_payment_ref');

        return response()->view('content-studio.billing.index', [
            'orders' => $orders,
            'statuses' => CheckoutPaymentStatus::cases(),
            'search' => $search,
            'selectedStatus' => $status,
            'ordersHavePages' => method_exists($orders, 'hasPages') && $orders->hasPages(),
            'recentEvents' => $recentEvents,
            'ordersByPayment' => $ordersByPayment,
            'totalOrderCount' => SubscriptionOrder::query()->count(),
            'paidOrderCount' => SubscriptionOrder::query()
                ->where('payment_status', CheckoutPaymentStatus::Paid)
                ->count(),
            'attentionOrderCount' => SubscriptionOrder::query()
                ->whereIn('payment_status', [
                    CheckoutPaymentStatus::Failed,
                    CheckoutPaymentStatus::Canceled,
                    CheckoutPaymentStatus::Expired,
                    CheckoutPaymentStatus::Refunded,
                    CheckoutPaymentStatus::ChargedBack,
                ])
                ->count(),
            'activeSubscriptionCount' => Subscription::query()
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Cancelled])
                ->where(function ($query): void {
                    $query->whereNull('current_period_ends_at')
                        ->orWhere('current_period_ends_at', '>', now());
                })
                ->count(),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
