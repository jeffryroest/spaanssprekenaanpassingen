<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\Enums\BillingInterval;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Enums\SubscriptionStatus;
use App\Models\ContentNode;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaxiMissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxi_routes_require_the_trial_week_entitlement(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('game.madrid.taxi'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice');

        $this->actingAs($player)
            ->postJson(route('game.madrid.taxi.complete'), $this->completionPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');

        $this->actingAs($player)
            ->getJson(route('game.madrid.taxi.content'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_entitled_player_gets_the_reusable_speaking_and_feedback_shell(): void
    {
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->get(route('game.madrid.taxi'))
            ->assertOk()
            ->assertSee('data-scenario-dialogue', false)
            ->assertSee('data-taxi-dialogue', false)
            ->assertSee('data-scenario-slug="taxi-diego"', false)
            ->assertSee(route('game.madrid.taxi.transcription'), false)
            ->assertSee(route('game.madrid.taxi.feedback'), false)
            ->assertSee(route('game.madrid.taxi.complete'), false)
            ->assertSee('Diego kan nog niet vertrekken');
    }

    public function test_published_taxi_content_unlocks_day_two_and_persists_server_calculated_rewards(): void
    {
        $this->publishTaxi();
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.1.content_state', 'published')
            ->assertJsonPath('data.days.1.access_state', 'available')
            ->assertJsonPath('data.days.1.action_url', route('game.madrid.taxi'));

        $response = $this->actingAs($player)->postJson(
            route('game.madrid.taxi.complete'),
            $this->completionPayload(usedRepairStrategy: true),
        );

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.balances.xp', 130)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.balances.valentia', 1)
            ->assertJsonPath('data.mission.key', 'mission.madrid.taxi.ride')
            ->assertJsonPath('data.mission.status', 'completed')
            ->assertJsonPath('data.mission.spoken_goal_completed', true)
            ->assertJsonCount(4, 'data.rewards')
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false)
            ->assertJsonPath('meta.feedback_persisted', false);

        $this->assertDatabaseHas('user_mission_progress', [
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.taxi.ride',
            'best_xp' => 130,
            'best_spoken_turns' => 3,
        ]);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'stamp.first_taxi_ride']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'item.madrid_taxi_receipt']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'madrid.restaurant.preview']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'badge.buen_viajero']);

        $attemptEvidence = json_encode($player->missionAttempts()->firstOrFail()->evidence, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('answer', $attemptEvidence);
        $this->assertStringNotContainsString('transcript', $attemptEvidence);
        $this->assertStringNotContainsString('audio', $attemptEvidence);
        $this->assertStringNotContainsString('feedback', $attemptEvidence);
    }

    public function test_taxi_dialogue_is_only_served_by_the_private_entitled_content_route(): void
    {
        $this->publishTaxi();
        $player = $this->entitledPlayer();

        $this->getJson('/api/v1/conversations/taxi-diego')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'published_content_not_found');

        $response = $this->actingAs($player)
            ->getJson(route('game.madrid.taxi.content', ['locale' => 'nl-NL']))
            ->assertOk()
            ->assertJsonPath('data.slug', 'taxi-diego')
            ->assertJsonPath('data.links.self', route('game.madrid.taxi.content'))
            ->assertJsonPath('data.content.domain_data.runtime_access.entitlement', 'trial_week');

        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_feedback_routes_are_pinned_to_their_own_published_scenario(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->postJson(route('game.madrid.taxi.feedback'), [
                'scenario_slug' => 'la-espiga-lucia',
                'step_id' => 'turn.greet',
                'answer' => 'Hola',
                'level' => 'A1',
                'source' => 'typed_assist',
                'transcript_confidence_status' => null,
                'transcript_corrected' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario_slug');

        $this->postJson(route('game.madrid.panaderia.feedback'), [
            'scenario_slug' => 'taxi-diego',
            'step_id' => 'turn.greet_destination',
            'answer' => 'Buenas tardes',
            'level' => 'A1',
            'source' => 'typed_assist',
            'transcript_confidence_status' => null,
            'transcript_corrected' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario_slug');
    }

    public function test_misconfigured_runtime_access_fails_closed_everywhere(): void
    {
        $this->publishTaxi([
            'visibility' => 'entitled',
            'entitlement' => 'different_product',
        ]);
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.1.content_state', 'planned')
            ->assertJsonPath('data.days.1.action_url', null);

        $this->actingAs($player)
            ->getJson(route('game.madrid.taxi.content'))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'content_access_contract_invalid');

        $this->actingAs($player)
            ->postJson(route('game.madrid.taxi.complete'), $this->completionPayload())
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'mission_definition_unavailable');
    }

    public function test_each_taxi_level_branch_completes_and_retries_are_idempotent(): void
    {
        $this->publishTaxi();

        foreach ([
            ['A0', 'branch.confirm_destination'],
            ['A1', 'branch.traffic_alternative'],
            ['A2', 'branch.route_preference'],
        ] as [$level, $branchStep]) {
            $player = $this->entitledPlayer();
            $payload = $this->completionPayload(level: $level, branchStep: $branchStep);

            $this->actingAs($player)->postJson(route('game.madrid.taxi.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.mission.status', 'completed');
            $this->actingAs($player)->postJson(route('game.madrid.taxi.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.last_attempt.duplicate', true)
                ->assertJsonPath('data.last_attempt.awarded_now.xp', 0);

            $this->assertSame(1, $player->missionAttempts()->where('mission_key', 'mission.madrid.taxi.ride')->count());
        }
    }

    public function test_wrong_taxi_route_is_rejected_without_mutating_progress(): void
    {
        $this->publishTaxi();
        $player = $this->entitledPlayer();
        $payload = $this->completionPayload();
        $payload['turns'][3]['step_id'] = 'turn.payment_receipt';

        $this->actingAs($player)
            ->postJson(route('game.madrid.taxi.complete'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'mission_evidence_invalid');

        $this->assertDatabaseCount('mission_attempts', 0);
        $this->assertDatabaseCount('game_ledger', 0);
        $this->assertDatabaseCount('user_rewards', 0);
    }

    /** @return array<string, mixed> */
    private function completionPayload(
        bool $usedRepairStrategy = false,
        string $level = 'A1',
        string $branchStep = 'branch.traffic_alternative',
    ): array {
        $stepIds = [
            'turn.greet_destination',
            'turn.passengers',
            $branchStep,
            'turn.ask_price',
            'turn.payment_receipt',
        ];

        return [
            'completion_key' => (string) Str::uuid(),
            'level' => $level,
            'used_repair_strategy' => $usedRepairStrategy,
            'turns' => array_map(
                static fn (string $stepId, int $index): array => [
                    'step_id' => $stepId,
                    'source' => $index < 3 ? 'speech' : 'typed_assist',
                    'assisted' => false,
                ],
                $stepIds,
                array_keys($stepIds),
            ),
        ];
    }

    private function entitledPlayer(): User
    {
        $player = User::factory()->create();
        $plan = SubscriptionPlan::query()->firstOrCreate(['code' => 'test-trial-week'], [
            'name' => 'Testtoegang proefweek',
            'billing_interval' => BillingInterval::Month,
            'currency' => 'EUR',
            'amount_minor' => 1000,
            'trial_days' => 7,
            'entitlements' => ['trial_week'],
            'active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $player->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'provider' => 'test',
            'provider_subscription_ref' => 'sub_'.Str::uuid(),
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        return $player;
    }

    /** @param array<string, string>|null $runtimeAccess */
    private function publishTaxi(?array $runtimeAccess = null): ContentNode
    {
        $domainData = json_decode(
            file_get_contents(base_path('content/examples/taxi-dialogue-domain-data.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        if ($runtimeAccess !== null) {
            $domainData['runtime_access'] = $runtimeAccess;
        }
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'taxi-diego',
            locale: 'es-ES',
            title: 'Taxirit met Diego',
            domainData: $domainData,
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Taxidialoog en niveaupaden gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Taxi dag 2',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde taxi-missietest.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }
}
