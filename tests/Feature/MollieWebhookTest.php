<?php

namespace Tests\Feature;

use App\Models\SubscriptionEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MollieWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.mollie.enabled', true);
        config()->set('services.mollie.api_key', 'test_safe_placeholder');
        config()->set('services.mollie.base_url', 'https://api.mollie.test/v2');
    }

    public function test_verified_payment_snapshot_is_recorded_without_personal_or_free_form_data(): void
    {
        Http::fake([
            'https://api.mollie.test/v2/payments/tr_7UhSN1zuXS' => Http::response($this->paymentPayload(), 200),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_7UhSN1zuXS'])
            ->assertOk();

        $event = SubscriptionEvent::query()->sole();
        $this->assertSame('mollie', $event->provider);
        $this->assertSame('payment.paid', $event->event_type);
        $this->assertSame('ignored', $event->processing_status);
        $this->assertNotNull($event->processed_at);
        $this->assertSame('unknown_subscription', $event->processing_error);
        $this->assertSame('sub_safeRef123', $event->event_payload['subscription_id']);

        $stored = json_encode($event->event_payload, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('description', $stored);
        $this->assertStringNotContainsString('metadata', $stored);
        $this->assertStringNotContainsString('customerId', $stored);
        $this->assertStringNotContainsString('speler@example.com', $stored);
    }

    public function test_identical_delivery_is_deduplicated_but_a_changed_financial_state_is_preserved(): void
    {
        Http::fake([
            'https://api.mollie.test/v2/payments/tr_7UhSN1zuXS' => Http::sequence()
                ->push($this->paymentPayload(), 200)
                ->push($this->paymentPayload(), 200)
                ->push($this->paymentPayload(['amountRefunded' => ['currency' => 'EUR', 'value' => '9.95']]), 200),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_7UhSN1zuXS'])->assertOk();
        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_7UhSN1zuXS'])->assertOk();

        $this->assertDatabaseCount('subscription_events', 1);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_7UhSN1zuXS'])->assertOk();

        $this->assertDatabaseCount('subscription_events', 2);
    }

    public function test_unknown_or_invalid_payment_ids_return_ok_without_creating_an_event(): void
    {
        Http::fake([
            'https://api.mollie.test/v2/payments/tr_unknown123' => Http::response([], 404),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'ongeldig'])->assertOk();
        Http::assertNothingSent();

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_unknown123'])->assertOk();

        $this->assertDatabaseCount('subscription_events', 0);
    }

    public function test_temporary_provider_failure_returns_retryable_response(): void
    {
        Http::fake([
            'https://api.mollie.test/v2/payments/tr_7UhSN1zuXS' => Http::response([], 503),
        ]);

        $this->post(route('billing.mollie.webhook'), ['id' => 'tr_7UhSN1zuXS'])
            ->assertStatus(503);

        $this->assertDatabaseCount('subscription_events', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paymentPayload(array $overrides = []): array
    {
        return array_replace([
            'resource' => 'payment',
            'id' => 'tr_7UhSN1zuXS',
            'status' => 'paid',
            'amount' => ['currency' => 'EUR', 'value' => '9.95'],
            'amountRefunded' => ['currency' => 'EUR', 'value' => '0.00'],
            'amountChargedBack' => ['currency' => 'EUR', 'value' => '0.00'],
            'subscriptionId' => 'sub_safeRef123',
            'customerId' => 'cst_not_stored',
            'description' => 'Vrije omschrijving die niet wordt opgeslagen',
            'metadata' => ['email' => 'speler@example.com'],
            'paidAt' => '2026-09-01T10:00:00+00:00',
            'failedAt' => null,
            'canceledAt' => null,
            'expiredAt' => null,
        ], $overrides);
    }
}
