<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_share_the_same_brand_and_guest_navigation(): void
    {
        foreach ([route('home'), route('game.madrid'), route('game.madrid.panaderia')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('player-site-header', false)
                ->assertSee('player-brand-mark', false)
                ->assertSee(route('game.madrid'), false)
                ->assertSee(route('login'), false)
                ->assertSee(route('register'), false);
        }
    }

    public function test_every_authenticated_player_page_uses_the_same_primary_menu(): void
    {
        $player = User::factory()->create();
        $this->activateTrial($player);

        $urls = [
            route('player.account'),
            route('player.progress'),
            route('trial-week.show'),
            route('game.madrid'),
            route('game.madrid.panaderia'),
            route('game.madrid.taxi'),
            route('game.madrid.restaurant'),
            route('game.madrid.review'),
            route('game.madrid.health'),
            route('game.madrid.station'),
            route('game.madrid.final'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($player)
                ->get($url)
                ->assertOk()
                ->assertSee('player-site-header', false)
                ->assertSee('Spaansspreken', false)
                ->assertSee(route('game.madrid'), false)
                ->assertSee(route('trial-week.show'), false)
                ->assertSee(route('player.progress'), false)
                ->assertSee(route('player.account'), false)
                ->assertSee('Uitloggen');
        }
    }

    private function activateTrial(User $player): void
    {
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
            'provider' => 'internal_trial',
            'status' => SubscriptionStatus::Trialing,
            'trial_starts_at' => now()->subDays(7),
            'trial_ends_at' => now()->addDay(),
        ]);
    }
}
