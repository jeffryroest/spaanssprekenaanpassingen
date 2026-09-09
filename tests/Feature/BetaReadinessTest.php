<?php

namespace Tests\Feature;

use App\Beta\SchedulerHeartbeat;
use App\Enums\CheckoutPaymentStatus;
use App\Enums\ContentRole;
use App\Enums\SubscriptionStatus;
use App\Models\MissionAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BetaReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_beta_overview_is_exclusive_to_administrators(): void
    {
        foreach (ContentRole::cases() as $role) {
            $user = User::factory()->create(['content_role' => $role]);
            $expected = $role === ContentRole::Administrator;

            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows('beta.manage'),
                "Onjuiste bètabevoegdheid voor {$role->value}.",
            );

            $response = $this->actingAs($user)->get(route('content-studio.beta.index'));
            $expected ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_overview_shows_an_aggregate_player_cohort_without_personal_data(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 12:00:00');
        $administrator = User::factory()->create([
            'content_role' => ContentRole::Administrator,
            'created_at' => now()->subDays(5),
        ]);
        $player = User::factory()->create([
            'name' => 'Niet tonen',
            'email' => 'verborgen@example.com',
            'created_at' => now()->subDays(10),
        ]);
        User::factory()->create(['created_at' => now()->subDays(3)]);
        $oldPlayer = User::factory()->create(['created_at' => now()->subDays(40)]);
        $plan = $this->plan();
        $subscription = $this->trial($player, $plan);
        $this->trial($oldPlayer, $plan);
        $this->attempt($player, 'mission.madrid.panaderia.breakfast', 2);
        $this->attempt($player, 'mission.madrid.week.final', 1, 2);
        $this->paidOrder($player, $plan, $subscription);

        $this->actingAs($administrator)
            ->get(route('content-studio.beta.index', ['periode' => 30]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertViewHas('metrics', function (array $metrics): bool {
                $counts = collect($metrics['stages'])->pluck('count', 'key');

                return $metrics['days'] === 30
                    && $counts->get('accounts') === 2
                    && $counts->get('trials') === 1
                    && $counts->get('learners') === 1
                    && $counts->get('speakers') === 1
                    && $counts->get('finalists') === 1
                    && $counts->get('customers') === 1;
            })
            ->assertSee('Bètastatus')
            ->assertDontSee('verborgen@example.com')
            ->assertDontSee('Niet tonen');
    }

    public function test_heartbeat_command_makes_scheduler_check_ready(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 12:00:00');

        $this->artisan('operations:heartbeat')->assertSuccessful();

        $this->assertTrue(app(SchedulerHeartbeat::class)->isFresh());
        CarbonImmutable::setTestNow('2026-09-10 12:06:00');
        $this->assertFalse(app(SchedulerHeartbeat::class)->isFresh());
    }

    public function test_browser_security_headers_are_applied_without_blocking_same_origin_microphone(): void
    {
        $this->get('https://spaansspreken.nl/')
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; frame-ancestors 'none'; object-src 'none'")
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=(self)')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->create([
            'code' => 'madrid-maandelijks',
            'name' => 'Spaansspreken Madrid',
            'billing_interval' => 'month',
            'currency' => 'EUR',
            'amount_minor' => 995,
            'trial_days' => 7,
            'entitlements' => ['trial_week'],
            'active' => true,
        ]);
    }

    private function trial(User $user, SubscriptionPlan $plan): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'internal_trial',
            'status' => SubscriptionStatus::Trialing,
            'trial_starts_at' => now()->subDays(2),
            'trial_ends_at' => now()->addDays(5),
        ]);
    }

    private function attempt(User $user, string $missionKey, int $attemptNumber, int $spokenTurns = 0): void
    {
        MissionAttempt::query()->create([
            'user_id' => $user->getKey(),
            'mission_key' => $missionKey,
            'source_content_node_id' => null,
            'source_content_version' => 1,
            'attempt_number' => $attemptNumber,
            'completion_key' => fake()->uuid(),
            'status' => 'completed',
            'level' => 'A1',
            'completed_turns' => 5,
            'spoken_turns' => $spokenTurns,
            'assist_count' => 0,
            'used_repair_strategy' => false,
            'earned_xp' => 10,
            'earned_confianza' => 1,
            'earned_valentia' => 1,
            'evidence' => ['version' => 1],
            'completed_at' => now()->subDay(),
        ]);
    }

    private function paidOrder(User $user, SubscriptionPlan $plan, Subscription $subscription): void
    {
        SubscriptionOrder::query()->create([
            'public_id' => '01J6Q8B8V5QJK6M0W9NY7Q3B4C',
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'subscription_id' => $subscription->getKey(),
            'first_name' => 'Verborgen',
            'last_name' => 'Speler',
            'email' => 'verborgen@example.com',
            'provider' => 'mollie',
            'payment_status' => CheckoutPaymentStatus::Paid,
            'currency' => 'EUR',
            'amount_minor' => 995,
            'consent_version' => 'mollie-monthly-995-v2',
            'consented_at' => now()->subDay(),
            'paid_at' => now()->subDay(),
        ]);
    }
}
