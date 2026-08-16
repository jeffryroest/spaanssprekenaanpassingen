<?php

namespace Tests\Feature;

use App\Access\EntitlementService;
use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Http\Middleware\EnsureEntitled;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class TrialWeekAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_must_log_in_before_viewing_account_trial_week(): void
    {
        $this->get(route('trial-week.show'))
            ->assertRedirect(route('login'));

        $this->getJson(route('game.trial-week.status'))
            ->assertUnauthorized();
    }

    public function test_account_without_subscription_sees_seven_days_and_only_the_public_sample(): void
    {
        $player = User::factory()->create();

        $response = $this->actingAs($player)->get(route('trial-week.show'));

        $response->assertOk()
            ->assertSee('Zeven dagen spreken in Madrid')
            ->assertSee('Nog niet actief')
            ->assertSee('La panadería')
            ->assertSee('En taxi')
            ->assertSee('En el restaurante')
            ->assertSee('En la consulta')
            ->assertSee('En la estación')
            ->assertViewHas('days', fn (array $days): bool => count($days) === 7
                && $days[0]['access_state'] === 'available'
                && $days[1]['access_state'] === 'requires_access');
    }

    public function test_trial_access_is_calculated_server_side_and_unlocks_days_over_time(): void
    {
        CarbonImmutable::setTestNow('2026-08-16 12:00:00');
        $player = User::factory()->create();
        $plan = $this->plan(['trial_week', 'progress']);
        $this->subscription($player, $plan, SubscriptionStatus::Trialing, [
            'trial_starts_at' => now()->subDays(2)->subHour(),
            'trial_ends_at' => now()->addDays(4),
        ]);

        $response = $this->actingAs($player)->getJson(route('game.trial-week.status'));

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.access.state', 'trialing')
            ->assertJsonPath('data.access.access_active', true)
            ->assertJsonPath('data.access.trial_day', 3)
            ->assertJsonPath('data.access.can_access_trial_week', true)
            ->assertJsonPath('data.days.1.access_state', 'planned')
            ->assertJsonPath('data.days.2.access_state', 'planned')
            ->assertJsonPath('data.days.3.access_state', 'scheduled')
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('meta.payment_data_included', false)
            ->assertJsonPath('meta.provider_references_included', false);

        $payload = $response->getContent();
        $this->assertStringNotContainsString('provider_customer_ref', $payload);
        $this->assertStringNotContainsString('provider_subscription_ref', $payload);
        $this->assertStringNotContainsString('amount_minor', $payload);
    }

    public function test_expired_trial_and_paused_subscription_do_not_grant_access(): void
    {
        CarbonImmutable::setTestNow('2026-08-16 12:00:00');
        $player = User::factory()->create();
        $plan = $this->plan(['trial_week']);
        $this->subscription($player, $plan, SubscriptionStatus::Paused, [
            'current_period_ends_at' => now()->addMonth(),
        ]);
        $this->subscription($player, $plan, SubscriptionStatus::Trialing, [
            'trial_starts_at' => now()->subDays(8),
            'trial_ends_at' => now()->subDay(),
        ]);

        $snapshot = app(EntitlementService::class)->snapshotFor($player);

        $this->assertSame('expired', $snapshot->state);
        $this->assertFalse($snapshot->accessActive);
        $this->assertFalse($snapshot->allows('trial_week'));
        $this->assertSame([], $snapshot->entitlements);
    }

    public function test_active_and_not_yet_ended_cancelled_subscriptions_keep_configured_rights(): void
    {
        CarbonImmutable::setTestNow('2026-08-16 12:00:00');
        $plan = $this->plan(['trial_week' => true, 'npc_memory' => false]);
        $activePlayer = User::factory()->create();
        $cancelledPlayer = User::factory()->create();
        $this->subscription($activePlayer, $plan, SubscriptionStatus::Active, [
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
        $this->subscription($cancelledPlayer, $plan, SubscriptionStatus::Cancelled, [
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->addDay(),
            'cancelled_at' => now()->subDay(),
        ]);

        $active = app(EntitlementService::class)->snapshotFor($activePlayer);
        $cancelled = app(EntitlementService::class)->snapshotFor($cancelledPlayer);

        $this->assertTrue($active->allows('trial_week'));
        $this->assertSame(['trial_week'], $active->entitlements);
        $this->assertTrue($cancelled->allows('trial_week'));
        $this->assertSame('cancelled', $cancelled->state);
    }

    public function test_entitlement_middleware_denies_missing_right_and_allows_active_right(): void
    {
        $player = User::factory()->create();
        $request = Request::create('/premium-missie', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn (): User => $player);
        $middleware = app(EnsureEntitled::class);

        $denied = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'trial_week');

        $this->assertSame(403, $denied->getStatusCode());
        $this->assertStringContainsString('entitlement_required', $denied->getContent());

        $plan = $this->plan(['trial_week']);
        $this->subscription($player, $plan, SubscriptionStatus::Active, [
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $allowed = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'trial_week');

        $this->assertSame(200, $allowed->getStatusCode());
    }

    public function test_plan_and_trial_invariants_are_rejected_before_persistence(): void
    {
        try {
            SubscriptionPlan::query()->create([
                'code' => 'ongeldig-plan',
                'name' => 'Ongeldig plan',
                'billing_interval' => BillingInterval::Month,
                'currency' => 'EUR',
                'amount_minor' => 0,
                'trial_days' => 7,
                'entitlements' => ['trial_week'],
                'active' => true,
            ]);
            $this->fail('Een nulbedrag mag niet worden opgeslagen.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Een abonnementsbedrag moet groter dan nul zijn.', $exception->getMessage());
        }

        $player = User::factory()->create();
        $plan = $this->plan(['trial_week']);

        $this->expectException(InvalidArgumentException::class);
        $this->subscription($player, $plan, SubscriptionStatus::Trialing, [
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    /** @param array<int|string, mixed> $entitlements */
    private function plan(array $entitlements): SubscriptionPlan
    {
        return SubscriptionPlan::query()->create([
            'code' => 'test-proefweek-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Testplan proefweek',
            'billing_interval' => BillingInterval::Month,
            'currency' => 'EUR',
            'amount_minor' => 1000,
            'trial_days' => 7,
            'entitlements' => $entitlements,
            'active' => true,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function subscription(
        User $user,
        SubscriptionPlan $plan,
        SubscriptionStatus $status,
        array $attributes = [],
    ): Subscription {
        return Subscription::query()->create($attributes + [
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'test',
            'provider_subscription_ref' => 'sub_'.fake()->unique()->uuid(),
            'status' => $status,
        ]);
    }
}
