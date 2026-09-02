<?php

namespace Tests\Feature;

use App\Enums\CheckoutPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MollieCheckoutSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mollie.enabled', true);
        config()->set('services.mollie.checkout_enabled', true);
        config()->set('services.mollie.api_key', 'test_safe_placeholder');
        config()->set('services.mollie.base_url', 'https://api.mollie.test/v2');
        $this->artisan('subscriptions:install-mollie-monthly')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_checkout_registers_buyer_and_recurring_consent_before_redirecting_to_mollie(): void
    {
        $player = User::factory()->create(['name' => 'Ana García', 'email' => 'account@example.com']);

        Http::fake(function (ClientRequest $request) {
            return match (true) {
                $request->method() === 'POST' && $request->url() === 'https://api.mollie.test/v2/customers' => Http::response([
                    'id' => 'cst_safe123',
                ], 201),
                $request->method() === 'POST' && $request->url() === 'https://api.mollie.test/v2/customers/cst_safe123/payments' => Http::response([
                    'id' => 'tr_12345678',
                    'status' => 'open',
                    '_links' => ['checkout' => ['href' => 'https://www.mollie.test/checkout/abc']],
                ], 201),
                default => Http::response([], 500),
            };
        });

        $this->actingAs($player)->post(route('billing.mollie.start'), [
            'first_name' => 'Ana',
            'last_name' => 'García López',
            'email' => 'ana@example.com',
            'recurring_consent' => '1',
        ])->assertRedirect('https://www.mollie.test/checkout/abc');

        $order = SubscriptionOrder::query()->sole();
        $this->assertSame('Ana', $order->first_name);
        $this->assertSame('García López', $order->last_name);
        $this->assertSame('ana@example.com', $order->email);
        $this->assertSame(CheckoutPaymentStatus::Open, $order->payment_status);
        $this->assertSame(995, $order->amount_minor);
        $this->assertSame('EUR', $order->currency);
        $this->assertSame('mollie-monthly-995-v1', $order->consent_version);
        $this->assertNotNull($order->consented_at);
        $this->assertSame('cst_safe123', $order->provider_customer_ref);
        $this->assertSame('tr_12345678', $order->provider_payment_ref);

        Http::assertSent(function (ClientRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://api.mollie.test/v2/customers'
                && $data['name'] === 'Ana García López'
                && $data['email'] === 'ana@example.com';
        });
        Http::assertSent(function (ClientRequest $request) use ($order): bool {
            $data = $request->data();

            return $request->url() === 'https://api.mollie.test/v2/customers/cst_safe123/payments'
                && $data['amount'] === ['currency' => 'EUR', 'value' => '9.95']
                && $data['sequenceType'] === 'first'
                && $data['metadata']['checkout_reference'] === $order->public_id;
        });
    }

    public function test_paid_return_creates_monthly_subscription_and_activates_access_once(): void
    {
        CarbonImmutable::setTestNow('2026-09-01 10:15:00');
        $player = User::factory()->create();
        $order = $this->openOrder($player);

        Http::fake(function (ClientRequest $request) use ($order) {
            return match (true) {
                $request->method() === 'GET' && $request->url() === 'https://api.mollie.test/v2/payments/tr_12345678' => Http::response(
                    $this->paymentPayload($order),
                    200,
                ),
                $request->method() === 'GET' && $request->url() === 'https://api.mollie.test/v2/customers/cst_safe123/mandates' => Http::response([
                    '_embedded' => ['mandates' => [['id' => 'mdt_safe123', 'status' => 'valid']]],
                ], 200),
                $request->method() === 'GET' && str_starts_with($request->url(), 'https://api.mollie.test/v2/customers/cst_safe123/subscriptions') => Http::response([
                    '_embedded' => ['subscriptions' => []],
                ], 200),
                $request->method() === 'POST' && $request->url() === 'https://api.mollie.test/v2/customers/cst_safe123/subscriptions' => Http::response([
                    'id' => 'sub_safe123',
                    'status' => 'active',
                ], 201),
                default => Http::response([], 500),
            };
        });

        $this->actingAs($player)
            ->get(route('billing.mollie.return', $order))
            ->assertOk()
            ->assertSee('Betaald')
            ->assertSee('Ana García')
            ->assertSee('ana@example.com');

        $subscription = Subscription::query()->sole();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame('cst_safe123', $subscription->provider_customer_ref);
        $this->assertSame('sub_safe123', $subscription->provider_subscription_ref);
        $this->assertTrue($subscription->current_period_starts_at->equalTo('2026-09-01 10:00:00'));
        $this->assertTrue($subscription->current_period_ends_at->equalTo('2026-10-01 10:00:00'));

        $order->refresh();
        $this->assertSame(CheckoutPaymentStatus::Paid, $order->payment_status);
        $this->assertSame($subscription->getKey(), $order->subscription_id);
        $this->assertNotNull($order->completed_at);
        $this->assertSame('processed', SubscriptionEvent::query()->sole()->processing_status);

        Http::assertSent(function (ClientRequest $request): bool {
            $data = $request->data();

            return $request->url() === 'https://api.mollie.test/v2/customers/cst_safe123/subscriptions'
                && $data['interval'] === '1 month'
                && $data['startDate'] === '2026-10-01'
                && $data['amount'] === ['currency' => 'EUR', 'value' => '9.95'];
        });

        $this->actingAs($player)->get(route('billing.mollie.return', $order))->assertOk();
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('subscription_events', 1);
    }

    public function test_recurring_paid_webhook_extends_the_local_period(): void
    {
        CarbonImmutable::setTestNow('2026-10-01 10:15:00');
        $player = User::factory()->create();
        $plan = SubscriptionPlan::query()->sole();
        $subscription = Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_safe123',
            'provider_subscription_ref' => 'sub_safe123',
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => '2026-09-01 10:00:00',
            'current_period_ends_at' => '2026-10-01 10:00:00',
        ]);

        Http::fake([
            'https://api.mollie.test/v2/payments/tr_recurring1' => Http::response([
                'id' => 'tr_recurring1',
                'status' => 'paid',
                'amount' => ['currency' => 'EUR', 'value' => '9.95'],
                'customerId' => 'cst_safe123',
                'subscriptionId' => 'sub_safe123',
                'sequenceType' => 'recurring',
                'paidAt' => '2026-10-01T10:00:00+00:00',
            ], 200),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_recurring1'])->assertOk();

        $subscription->refresh();
        $this->assertTrue($subscription->current_period_starts_at->equalTo('2026-10-01 10:00:00'));
        $this->assertTrue($subscription->current_period_ends_at->equalTo('2026-11-01 10:00:00'));
        $this->assertSame('processed', SubscriptionEvent::query()->sole()->processing_status);
    }

    public function test_cancellation_keeps_access_until_period_end(): void
    {
        CarbonImmutable::setTestNow('2026-09-15 10:00:00');
        $player = User::factory()->create();
        $plan = SubscriptionPlan::query()->sole();
        $subscription = Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_safe123',
            'provider_subscription_ref' => 'sub_safe123',
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => '2026-09-01 10:00:00',
            'current_period_ends_at' => '2026-10-01 10:00:00',
        ]);
        Http::fake([
            'https://api.mollie.test/v2/customers/cst_safe123/subscriptions/sub_safe123' => Http::response([], 204),
        ]);

        $this->actingAs($player)->post(route('billing.mollie.cancel'), [
            'confirm_cancellation' => '1',
        ])->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice', 'Je abonnement is opgezegd. Je toegang blijft actief tot en met 01-10-2026.');

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->status);
        $this->assertTrue($subscription->cancel_at_period_end);
        $this->assertNotNull($subscription->cancelled_at);
        $this->assertTrue($subscription->current_period_ends_at->equalTo('2026-10-01 10:00:00'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'DELETE');
    }

    public function test_checkout_requires_buyer_fields_and_explicit_recurring_consent(): void
    {
        $player = User::factory()->create();
        Http::fake();

        $this->actingAs($player)->post(route('billing.mollie.start'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'recurring_consent']);

        $this->assertDatabaseCount('subscription_orders', 0);
        Http::assertNothingSent();
    }

    private function openOrder(User $player): SubscriptionOrder
    {
        return SubscriptionOrder::query()->create([
            'public_id' => '01J6Q8B8V5QJK6M0W9NY7Q3B4C',
            'user_id' => $player->getKey(),
            'subscription_plan_id' => SubscriptionPlan::query()->sole()->getKey(),
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'ana@example.com',
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_safe123',
            'provider_payment_ref' => 'tr_12345678',
            'payment_status' => CheckoutPaymentStatus::Open,
            'currency' => 'EUR',
            'amount_minor' => 995,
            'consent_version' => 'mollie-monthly-995-v1',
            'consented_at' => now()->subMinute(),
            'checkout_started_at' => now()->subMinute(),
        ]);
    }

    /** @return array<string, mixed> */
    private function paymentPayload(SubscriptionOrder $order): array
    {
        return [
            'id' => 'tr_12345678',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '9.95'],
            'amountRefunded' => ['currency' => 'EUR', 'value' => '0.00'],
            'amountChargedBack' => ['currency' => 'EUR', 'value' => '0.00'],
            'customerId' => 'cst_safe123',
            'sequenceType' => 'first',
            'metadata' => ['checkout_reference' => $order->public_id],
            'paidAt' => '2026-09-01T10:00:00+00:00',
        ];
    }
}
