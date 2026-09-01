<?php

namespace Tests\Feature;

use App\Enums\BillingInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingConversionFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_approved_mollie_monthly_plan_is_installed_exactly_and_idempotently(): void
    {
        $this->artisan('subscriptions:install-mollie-monthly')
            ->expectsOutput('Het Mollie-maandplan is geïnstalleerd: € 9,95 per maand. Live betaling staat nog los hiervan.')
            ->assertSuccessful();

        $plan = SubscriptionPlan::query()->sole();

        $this->assertSame('madrid-maandelijks', $plan->code);
        $this->assertSame('Spaansspreken Madrid', $plan->name);
        $this->assertSame(BillingInterval::Month, $plan->billing_interval);
        $this->assertSame('EUR', $plan->currency);
        $this->assertSame(995, $plan->amount_minor);
        $this->assertSame(7, $plan->trial_days);
        $this->assertSame(['trial_week'], $plan->entitlements);
        $this->assertNull($plan->provider_price_ref);
        $this->assertTrue($plan->active);

        $this->artisan('subscriptions:install-mollie-monthly')
            ->expectsOutput('Het Mollie-maandplan is al exact en actief: € 9,95 per maand.')
            ->assertSuccessful();

        $this->assertDatabaseCount('subscription_plans', 1);
    }

    public function test_installer_never_overwrites_a_divergent_existing_plan(): void
    {
        SubscriptionPlan::query()->create([
            'code' => 'madrid-maandelijks',
            'name' => 'Afwijkend plan',
            'billing_interval' => BillingInterval::Month,
            'currency' => 'EUR',
            'amount_minor' => 1495,
            'trial_days' => 7,
            'entitlements' => ['trial_week'],
            'active' => true,
        ]);

        $this->artisan('subscriptions:install-mollie-monthly')
            ->expectsOutput('Het bestaande maandplan wijkt af. Er is niets overschreven; controleer het plan handmatig.')
            ->assertFailed();

        $this->assertSame(1495, SubscriptionPlan::query()->sole()->amount_minor);
    }

    public function test_trial_week_offer_displays_the_approved_price_without_exposing_provider_references(): void
    {
        $player = User::factory()->create();

        $response = $this->actingAs($player)->get(route('trial-week.show'));

        $response->assertOk()
            ->assertSee('€ 9,95')
            ->assertSee('per maand')
            ->assertSee('Mollie')
            ->assertSee('Proefactivatie wordt voorbereid')
            ->assertDontSee('provider_customer_ref')
            ->assertDontSee('provider_subscription_ref');
    }

    public function test_enabled_trial_activation_creates_one_seven_day_internal_projection(): void
    {
        CarbonImmutable::setTestNow('2026-09-01 10:15:00');
        config()->set('subscriptions.trial_activation_enabled', true);
        $this->artisan('subscriptions:install-mollie-monthly')->assertSuccessful();
        $player = User::factory()->create();

        $this->actingAs($player)
            ->post(route('trial-week.start'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice', 'Je proefweek is gestart. Vandaag kun je meteen met je eerste missiedag verder.');

        $subscription = Subscription::query()->sole();
        $this->assertSame($player->getKey(), $subscription->user_id);
        $this->assertSame('internal', $subscription->provider);
        $this->assertSame(SubscriptionStatus::Trialing, $subscription->status);
        $this->assertTrue($subscription->trial_starts_at->equalTo(now()));
        $this->assertTrue($subscription->trial_ends_at->equalTo(now()->addDays(7)));

        $this->actingAs($player)
            ->post(route('trial-week.start'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice', 'Je proefweek is al gestart.');

        $this->assertDatabaseCount('subscriptions', 1);

        $this->actingAs($player)
            ->get(route('trial-week.show'))
            ->assertOk()
            ->assertSee('Dag 1 van 7')
            ->assertSee('Geldig tot');
    }

    public function test_trial_cannot_start_without_activation_switch_or_exact_plan(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->post(route('trial-week.start'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice', 'Proefactivatie staat nog niet aan.');

        config()->set('subscriptions.trial_activation_enabled', true);

        $this->actingAs($player)
            ->post(route('trial-week.start'))
            ->assertSessionHas('access_notice', 'Het maandplan is nog niet veilig geïnstalleerd.');

        $this->assertDatabaseCount('subscriptions', 0);
    }
}
