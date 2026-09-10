<?php

namespace Tests\Feature;

use App\Enums\ContentRole;
use App\Enums\SubscriptionStatus;
use App\Models\MissionAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ContentStudioPlayerAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_overview_is_exclusive_to_administrators(): void
    {
        foreach (ContentRole::cases() as $role) {
            $user = User::factory()->create(['content_role' => $role]);
            $expected = $role === ContentRole::Administrator;

            $this->assertSame($expected, Gate::forUser($user)->allows('accounts.manage'));

            $response = $this->actingAs($user)->get(route('content-studio.accounts.index'));
            $expected ? $response->assertOk() : $response->assertForbidden();
        }
    }

    public function test_administrator_can_search_accounts_and_see_structural_support_status(): void
    {
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create([
            'name' => 'Ana Speler',
            'email' => 'ana@example.com',
        ]);
        User::factory()->create([
            'name' => 'Andere Speler',
            'email' => 'ander@example.com',
        ]);
        $plan = SubscriptionPlan::query()->create([
            'code' => 'madrid-maandelijks',
            'name' => 'Spaansspreken Madrid',
            'billing_interval' => 'month',
            'currency' => 'EUR',
            'amount_minor' => 995,
            'trial_days' => 7,
            'entitlements' => ['trial_week'],
            'active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'mollie',
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);
        MissionAttempt::query()->create([
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.panaderia.breakfast',
            'source_content_node_id' => null,
            'source_content_version' => 1,
            'attempt_number' => 1,
            'completion_key' => fake()->uuid(),
            'status' => 'completed',
            'level' => 'A1',
            'completed_turns' => 5,
            'spoken_turns' => 2,
            'assist_count' => 0,
            'used_repair_strategy' => false,
            'earned_xp' => 100,
            'earned_confianza' => 2,
            'earned_valentia' => 1,
            'evidence' => ['version' => 1],
            'completed_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->post(route('content-studio.accounts.search'), [
                'q' => 'ana@example.com',
                'account_type' => 'player',
                'subscription_status' => SubscriptionStatus::Active->value,
            ])->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Ana Speler')
            ->assertSee('ana@example.com')
            ->assertSee('Toegang actief')
            ->assertSee('Spaansspreken Madrid')
            ->assertDontSee('Andere Speler')
            ->assertViewHas('accounts', fn ($accounts): bool => $accounts->first()->mission_attempts_count === 1);
    }

    public function test_regular_player_cannot_view_other_accounts(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('content-studio.accounts.index'))
            ->assertForbidden();
    }
}
