<?php

namespace App\Http\Controllers\ContentStudio;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class PlayerAccountOverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('accounts.manage');

        $validated = $request->isMethod('post')
            ? $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
                'account_type' => ['nullable', Rule::in(['player', 'staff'])],
                'subscription_status' => [
                    'nullable',
                    Rule::in(['none', ...array_map(
                        fn (SubscriptionStatus $status): string => $status->value,
                        SubscriptionStatus::cases(),
                    )]),
                ],
            ])
            : [];
        $search = trim((string) ($validated['q'] ?? ''));
        $accountType = $validated['account_type'] ?? null;
        $subscriptionStatus = $validated['subscription_status'] ?? null;

        $accountsQuery = User::query()
            ->with('latestSubscription.plan')
            ->withCount('missionAttempts')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($accountType === 'player', fn ($query) => $query->whereNull('content_role'))
            ->when($accountType === 'staff', fn ($query) => $query->whereNotNull('content_role'))
            ->when($subscriptionStatus === 'none', fn ($query) => $query->whereDoesntHave('subscriptions'))
            ->when(
                is_string($subscriptionStatus) && $subscriptionStatus !== 'none',
                fn ($query) => $query->whereHas(
                    'latestSubscription',
                    fn ($query) => $query->where('status', $subscriptionStatus),
                ),
            )
            ->latest('id');
        $accounts = $search === '' && $accountType === null && $subscriptionStatus === null
            ? $accountsQuery->paginate(25)
            : $accountsQuery->limit(50)->get();

        return response()->view('content-studio.accounts.index', [
            'accounts' => $accounts,
            'accountsHavePages' => method_exists($accounts, 'hasPages') && $accounts->hasPages(),
            'search' => $search,
            'selectedAccountType' => $accountType,
            'selectedSubscriptionStatus' => $subscriptionStatus,
            'subscriptionStatuses' => SubscriptionStatus::cases(),
            'playerCount' => User::query()->whereNull('content_role')->count(),
            'staffCount' => User::query()->whereNotNull('content_role')->count(),
            'activeAccessCount' => User::query()
                ->whereNull('content_role')
                ->whereHas('latestSubscription', function ($query): void {
                    $query->where(function ($query): void {
                        $query->where('status', SubscriptionStatus::Trialing)
                            ->where('trial_starts_at', '<=', now())
                            ->where('trial_ends_at', '>', now());
                    })->orWhere(function ($query): void {
                        $query->where('status', SubscriptionStatus::Active)
                            ->where(function ($query): void {
                                $query->whereNull('current_period_starts_at')
                                    ->orWhere('current_period_starts_at', '<=', now());
                            })
                            ->where(function ($query): void {
                                $query->whereNull('current_period_ends_at')
                                    ->orWhere('current_period_ends_at', '>', now());
                            });
                    })->orWhere(function ($query): void {
                        $query->where('status', SubscriptionStatus::Cancelled)
                            ->where('current_period_ends_at', '>', now());
                    })->orWhere(function ($query): void {
                        $query->where('status', SubscriptionStatus::PastDue)
                            ->where('grace_ends_at', '>', now());
                    });
                })
                ->count(),
            'attentionCount' => User::query()
                ->whereNull('content_role')
                ->whereHas('latestSubscription', fn ($query) => $query->whereIn('status', [
                    SubscriptionStatus::PastDue,
                    SubscriptionStatus::Paused,
                ]))
                ->count(),
        ])->withHeaders([
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
