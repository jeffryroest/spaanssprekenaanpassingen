<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AssignContentRole;
use App\Enums\AccountDeletionStatus;
use App\Enums\AccountSupportCategory;
use App\Enums\CheckoutPaymentStatus;
use App\Enums\ContentRole;
use App\Enums\SubscriptionStatus;
use App\Models\AccountDeletionRequest;
use App\Models\AccountSupportNote;
use App\Models\MissionAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountSupportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_only_an_administrator_can_open_account_detail(): void
    {
        $player = User::factory()->create();
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);

        $this->actingAs($player)->get(route('content-studio.accounts.show', $player))->assertForbidden();
        $this->actingAs($editor)->get(route('content-studio.accounts.show', $player))->assertForbidden();
        $this->actingAs($administrator)
            ->get(route('content-studio.accounts.show', $player))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($player->email);
    }

    public function test_role_assignment_and_removal_are_audited_and_protect_administrators(): void
    {
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $backupAdministrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create();

        $this->actingAs($administrator)
            ->put(route('content-studio.accounts.role.update', $player), ['role' => ContentRole::Editor->value])
            ->assertSessionHas('role_status');
        $this->assertSame(ContentRole::Editor, $player->fresh()->content_role);

        $this->put(route('content-studio.accounts.role.update', $player), ['role' => ''])
            ->assertSessionHas('role_status');
        $this->assertNull($player->fresh()->content_role);
        $this->assertDatabaseHas('content_role_audits', [
            'user_id' => $player->getKey(),
            'actor_id' => $administrator->getKey(),
            'from_role' => ContentRole::Editor->value,
            'to_role' => null,
        ]);

        $this->put(route('content-studio.accounts.role.update', $administrator), ['role' => ContentRole::Editor->value])
            ->assertSessionHasErrors(['role'], null, 'role');
        $this->assertSame(ContentRole::Administrator, $administrator->fresh()->content_role);

        app(AssignContentRole::class)->handle($administrator, ContentRole::Editor, $backupAdministrator);
        $this->assertSame(ContentRole::Editor, $administrator->fresh()->content_role);

        $this->expectException(DomainException::class);
        app(AssignContentRole::class)->handle($backupAdministrator, null);
    }

    public function test_administrator_can_add_and_resolve_a_minimal_support_note(): void
    {
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create();

        $this->actingAs($administrator)
            ->post(route('content-studio.accounts.notes.store', $player), [
                'category' => AccountSupportCategory::Access->value,
                'summary' => 'Speler ziet dag twee nog niet; toegangsstatus gecontroleerd.',
                'follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])->assertSessionHas('support_status');

        $note = AccountSupportNote::query()->sole();
        $this->assertSame($administrator->getKey(), $note->actor_id);

        $this->post(route('content-studio.accounts.notes.resolve', [$player, $note]))
            ->assertSessionHas('support_status');

        $this->assertNotNull($note->fresh()->resolved_at);
        $this->assertSame($administrator->getKey(), $note->fresh()->resolved_by_id);
    }

    public function test_player_can_request_and_cancel_account_deletion_with_current_password(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->post(route('player.account.deletion.store'), [
                'current_password' => 'verkeerd',
                'confirm_deletion' => '1',
            ])->assertSessionHasErrors(['current_password'], null, 'deletion');

        $this->post(route('player.account.deletion.store'), [
            'current_password' => 'password',
            'confirm_deletion' => '1',
        ])->assertSessionHas('deletion_status');

        $deletionRequest = AccountDeletionRequest::query()->sole();
        $this->assertSame(AccountDeletionStatus::Requested, $deletionRequest->status);

        $this->post(route('player.account.deletion.cancel', $deletionRequest))
            ->assertSessionHas('deletion_status');
        $this->assertSame(AccountDeletionStatus::Cancelled, $deletionRequest->fresh()->status);
    }

    public function test_processing_a_deletion_erases_game_data_and_retains_billing_until_the_fiscal_deadline(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 12:00:00');
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create(['email' => 'ana@example.com']);
        $plan = $this->createPlan();
        $subscription = $this->createSubscription($player, $plan, SubscriptionStatus::Expired, now()->subDay());
        $order = $this->createOrder($player, $plan, $subscription);
        $attempt = MissionAttempt::query()->create([
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
        AccountSupportNote::query()->create([
            'user_id' => $player->getKey(),
            'actor_id' => $administrator->getKey(),
            'category' => AccountSupportCategory::Privacy,
            'summary' => 'Verwijderverzoek gecontroleerd.',
        ]);
        $deletionRequest = $this->createDeletionRequest($player);

        $this->actingAs($administrator)
            ->post(route('content-studio.accounts.deletions.process', [$player, $deletionRequest]), [
                'confirmation_email' => 'ana@example.com',
                'current_password' => 'password',
            ])->assertRedirect(route('content-studio.accounts.show', $player));

        $erasedPlayer = $player->fresh();
        $this->assertSame('Verwijderd account', $erasedPlayer->name);
        $this->assertStringStartsWith('deleted-', $erasedPlayer->email);
        $this->assertNotNull($erasedPlayer->privacy_erased_at);
        $this->assertDatabaseMissing('mission_attempts', ['id' => $attempt->getKey()]);
        $this->assertDatabaseMissing('account_support_notes', ['user_id' => $player->getKey()]);
        $this->assertDatabaseHas('subscription_orders', ['id' => $order->getKey()]);

        $completedRequest = $deletionRequest->fresh();
        $this->assertSame(AccountDeletionStatus::Completed, $completedRequest->status);
        $this->assertSame('2033-09-10', $completedRequest->billing_retained_until->format('Y-m-d'));
    }

    public function test_active_subscription_blocks_erasure_before_future_charges_are_resolved(): void
    {
        $administrator = User::factory()->create(['content_role' => ContentRole::Administrator]);
        $player = User::factory()->create(['email' => 'ana@example.com']);
        $plan = $this->createPlan();
        $this->createSubscription($player, $plan, SubscriptionStatus::Active);
        $deletionRequest = $this->createDeletionRequest($player);

        $this->actingAs($administrator)
            ->post(route('content-studio.accounts.deletions.process', [$player, $deletionRequest]), [
                'confirmation_email' => 'ana@example.com',
                'current_password' => 'password',
            ])->assertSessionHasErrors(['confirmation_email'], null, 'deletion');

        $this->assertSame(AccountDeletionStatus::Blocked, $deletionRequest->fresh()->status);
        $this->assertNull($player->fresh()->privacy_erased_at);
    }

    private function createPlan(): SubscriptionPlan
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

    private function createSubscription(
        User $player,
        SubscriptionPlan $plan,
        SubscriptionStatus $status,
        ?DateTimeInterface $endedAt = null,
    ): Subscription {
        return Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'mollie',
            'provider_customer_ref' => 'cst_test',
            'provider_subscription_ref' => 'sub_'.Str::lower(Str::random(10)),
            'status' => $status,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->addMonth(),
            'ended_at' => $endedAt,
        ]);
    }

    private function createOrder(User $player, SubscriptionPlan $plan, Subscription $subscription): SubscriptionOrder
    {
        return SubscriptionOrder::query()->create([
            'public_id' => Str::ulid(),
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'subscription_id' => $subscription->getKey(),
            'first_name' => 'Ana',
            'last_name' => 'Speler',
            'email' => $player->email,
            'provider' => 'mollie',
            'payment_status' => CheckoutPaymentStatus::Paid,
            'currency' => 'EUR',
            'amount_minor' => 995,
            'consent_version' => '2026-09',
            'consented_at' => now(),
            'paid_at' => now(),
        ]);
    }

    private function createDeletionRequest(User $player): AccountDeletionRequest
    {
        return AccountDeletionRequest::query()->create([
            'public_id' => Str::ulid(),
            'user_id' => $player->getKey(),
            'requested_by_id' => $player->getKey(),
            'status' => AccountDeletionStatus::Requested,
            'requested_at' => now(),
        ]);
    }
}
