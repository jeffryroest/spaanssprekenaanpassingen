<?php

namespace Tests\Feature;

use App\Access\EntitlementService;
use App\Enums\CheckoutPaymentStatus;
use App\Enums\ContentRole;
use App\Enums\SubscriptionStatus;
use App\Mail\PaymentRecoveryMail;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mollie.enabled', true);
        config()->set('services.mollie.api_key', 'test_safe_placeholder');
        config()->set('services.mollie.base_url', 'https://api.mollie.test/v2');
        $this->artisan('subscriptions:install-mollie-monthly')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_only_administrators_can_search_and_filter_billing_orders(): void
    {
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $player = User::factory()->create();
        $this->order($player, '01J6Q8B8V5QJK6M0W9NY7Q3B4C', 'Ana', 'ana@example.com', CheckoutPaymentStatus::Paid);
        $this->order($player, '01J6Q8B8V5QJK6M0W9NY7Q3B4D', 'Luis', 'luis@example.com', CheckoutPaymentStatus::Failed);

        $this->actingAs($editor)
            ->get(route('content-studio.billing.index'))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post(route('content-studio.billing.search'), ['q' => 'ana@', 'status' => 'paid'])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Ana García')
            ->assertSee('ana@example.com')
            ->assertSee('Betaald')
            ->assertDontSee('luis@example.com');

        $this->actingAs($administrator)
            ->get(route('content-studio.billing.index', ['q' => 'luis@example.com']))
            ->assertOk()
            ->assertSee('ana@example.com')
            ->assertSee('luis@example.com');
    }

    public function test_billing_management_gate_is_exclusive_to_administrators(): void
    {
        foreach (ContentRole::cases() as $role) {
            $user = User::factory()->create(['content_role' => $role]);

            $this->assertSame(
                $role === ContentRole::Administrator,
                Gate::forUser($user)->allows('billing.manage'),
                "Onjuiste betaalbeheerbevoegdheid voor {$role->value}.",
            );
        }
    }

    public function test_refund_becomes_an_admin_attention_item_and_blocks_access(): void
    {
        CarbonImmutable::setTestNow('2026-09-09 10:00:00');
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create();
        $subscription = $this->subscription($player);
        $order = $this->order(
            $player,
            '01J6Q8B8V5QJK6M0W9NY7Q3B4C',
            'Ana',
            'ana@example.com',
            CheckoutPaymentStatus::Paid,
            $subscription,
        );

        Http::fake([
            'https://api.mollie.test/v2/payments/tr_refund123' => Http::response([
                'id' => 'tr_refund123',
                'status' => 'paid',
                'amount' => ['currency' => 'EUR', 'value' => '9.95'],
                'amountRefunded' => ['currency' => 'EUR', 'value' => '9.95'],
                'amountChargedBack' => ['currency' => 'EUR', 'value' => '0.00'],
                'customerId' => 'cst_safe123',
                'sequenceType' => 'first',
                'metadata' => ['checkout_reference' => $order->public_id],
                'paidAt' => '2026-09-01T10:00:00+00:00',
            ], 200),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_refund123'])->assertOk();

        $order->refresh();
        $subscription->refresh();
        $event = SubscriptionEvent::query()->sole();

        $this->assertSame(CheckoutPaymentStatus::Refunded, $order->payment_status);
        $this->assertSame('mollie_refunded', $order->failure_code);
        $this->assertSame($subscription->getKey(), $event->subscription_id);
        $this->assertSame('Betaling terugbetaald', $event->attentionLabel());
        $this->assertTrue($event->occurred_at->equalTo('2026-09-09 10:00:00'));
        $this->assertSame(SubscriptionStatus::Paused, $subscription->status);
        $this->assertTrue($subscription->ended_at->equalTo('2026-09-09 10:00:00'));
        $this->assertTrue($subscription->current_period_ends_at->equalTo('2026-10-01 10:00:00'));

        $this->actingAs($administrator)
            ->get(route('content-studio.billing.index'))
            ->assertOk()
            ->assertSee('Betaling terugbetaald')
            ->assertSee('Ana García')
            ->assertSee($order->public_id)
            ->assertDontSee('cst_safe123');
    }

    public function test_failed_recurring_payment_starts_the_approved_fourteen_day_grace_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-01 10:15:00');
        $player = User::factory()->create();
        $subscription = $this->subscription($player);
        $this->order(
            $player,
            '01J6Q8B8V5QJK6M0W9NY7Q3B4E',
            'Ana',
            'ana@example.com',
            CheckoutPaymentStatus::Paid,
            $subscription,
        );

        Http::fake([
            'https://api.mollie.test/v2/payments/tr_failed123' => Http::response([
                'id' => 'tr_failed123',
                'status' => 'failed',
                'amount' => ['currency' => 'EUR', 'value' => '9.95'],
                'customerId' => 'cst_safe123',
                'subscriptionId' => 'sub_safe123',
                'sequenceType' => 'recurring',
                'failedAt' => '2026-10-01T10:00:00+00:00',
            ], 200),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_failed123'])->assertOk();

        $event = SubscriptionEvent::query()->sole();
        $subscription->refresh();

        $this->assertSame($subscription->getKey(), $event->subscription_id);
        $this->assertSame('Betaling mislukt', $event->attentionLabel());
        $this->assertSame('processed', $event->processing_status);
        $this->assertSame(SubscriptionStatus::PastDue, $subscription->status);
        $this->assertTrue($subscription->past_due_since_at->equalTo('2026-10-01 10:15:00'));
        $this->assertTrue($subscription->grace_ends_at->equalTo('2026-10-15 10:15:00'));
        $this->assertTrue($subscription->current_period_ends_at->equalTo('2026-10-01 10:00:00'));
        $this->assertDatabaseCount('billing_email_deliveries', 3);

        $entitlements = app(EntitlementService::class);
        $this->assertTrue($entitlements->snapshotFor($player, CarbonImmutable::parse('2026-10-15 10:14:59'))->accessActive);
        $this->assertFalse($entitlements->snapshotFor($player, CarbonImmutable::parse('2026-10-15 10:15:00'))->accessActive);

        Mail::fake();
        $this->artisan('billing:send-due-emails')->assertSuccessful();
        Mail::assertSent(PaymentRecoveryMail::class, 1);

        CarbonImmutable::setTestNow('2026-10-08 10:15:00');
        $this->artisan('billing:send-due-emails')->assertSuccessful();
        Mail::assertSent(PaymentRecoveryMail::class, 2);

        CarbonImmutable::setTestNow('2026-10-14 10:15:00');
        $this->artisan('billing:send-due-emails')->assertSuccessful();
        Mail::assertSent(PaymentRecoveryMail::class, 3);
    }

    public function test_administrator_can_cancel_on_behalf_of_the_customer(): void
    {
        CarbonImmutable::setTestNow('2026-09-15 10:00:00');
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $player = User::factory()->create();
        $subscription = $this->subscription($player);
        $this->order(
            $player,
            '01J6Q8B8V5QJK6M0W9NY7Q3B4F',
            'Ana',
            'ana@example.com',
            CheckoutPaymentStatus::Paid,
            $subscription,
        );
        Http::fake([
            'https://api.mollie.test/v2/customers/cst_safe123/subscriptions/sub_safe123' => Http::response([], 204),
        ]);

        $this->actingAs($editor)
            ->post(route('content-studio.billing.subscriptions.cancel', $subscription), [
                'confirm_cancellation' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post(route('content-studio.billing.subscriptions.cancel', $subscription), [
                'confirm_cancellation' => '1',
            ])
            ->assertRedirect(route('content-studio.billing.index'));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->status);
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertDatabaseHas('billing_email_deliveries', [
            'subscription_id' => $subscription->getKey(),
            'kind' => 'subscription_cancelled',
        ]);
    }

    private function subscription(User $player): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => SubscriptionPlan::query()->sole()->getKey(),
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_safe123',
            'provider_subscription_ref' => 'sub_safe123',
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => '2026-09-01 10:00:00',
            'current_period_ends_at' => '2026-10-01 10:00:00',
        ]);
    }

    private function order(
        User $player,
        string $publicId,
        string $firstName,
        string $email,
        CheckoutPaymentStatus $status,
        ?Subscription $subscription = null,
    ): SubscriptionOrder {
        return SubscriptionOrder::query()->create([
            'public_id' => $publicId,
            'user_id' => $player->getKey(),
            'subscription_plan_id' => SubscriptionPlan::query()->sole()->getKey(),
            'subscription_id' => $subscription?->getKey(),
            'first_name' => $firstName,
            'last_name' => 'García',
            'email' => $email,
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_safe123',
            'provider_payment_ref' => $publicId === '01J6Q8B8V5QJK6M0W9NY7Q3B4C' ? 'tr_refund123' : 'tr_other123',
            'payment_status' => $status,
            'currency' => 'EUR',
            'amount_minor' => 995,
            'consent_version' => 'mollie-monthly-995-v1',
            'consented_at' => now()->subMinute(),
            'checkout_started_at' => now()->subMinute(),
            'paid_at' => $status === CheckoutPaymentStatus::Paid ? now()->subDay() : null,
        ]);
    }
}
