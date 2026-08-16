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

class RestaurantMissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_routes_require_the_trial_week_entitlement(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('game.madrid.restaurant'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice');

        $this->actingAs($player)
            ->getJson(route('game.madrid.restaurant.content'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');

        $this->actingAs($player)
            ->postJson(route('game.madrid.restaurant.complete'), $this->completionPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_entitled_player_gets_the_restaurant_speaking_shell(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->get(route('game.madrid.restaurant'))
            ->assertOk()
            ->assertSee('data-scenario-dialogue', false)
            ->assertSee('data-restaurant-dialogue', false)
            ->assertSee('data-scenario-slug="restaurant-el-reloj"', false)
            ->assertSee(route('game.madrid.restaurant.transcription'), false)
            ->assertSee(route('game.madrid.restaurant.feedback'), false)
            ->assertSee(route('game.madrid.restaurant.complete'), false)
            ->assertSee('Carmen kan je tafel nog niet klaarmaken');
    }

    public function test_published_restaurant_unlocks_day_three_and_persists_safe_rewards(): void
    {
        $this->publishRestaurant();
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.2.content_state', 'published')
            ->assertJsonPath('data.days.2.access_state', 'available')
            ->assertJsonPath('data.days.2.action_url', route('game.madrid.restaurant'));

        $response = $this->actingAs($player)->postJson(
            route('game.madrid.restaurant.complete'),
            $this->completionPayload(usedRepairStrategy: true),
        );

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.balances.xp', 140)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.balances.valentia', 1)
            ->assertJsonPath('data.mission.key', 'mission.madrid.restaurant.order')
            ->assertJsonPath('data.mission.status', 'completed')
            ->assertJsonPath('data.mission.spoken_goal_completed', true)
            ->assertJsonCount(4, 'data.rewards')
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false)
            ->assertJsonPath('meta.feedback_persisted', false);

        $this->assertDatabaseHas('user_mission_progress', [
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.restaurant.order',
            'best_xp' => 140,
            'best_spoken_turns' => 3,
        ]);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'stamp.first_madrid_dinner']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'item.el_reloj_coaster']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'madrid.health.preview']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'badge.con_soltura']);

        $evidence = json_encode($player->missionAttempts()->firstOrFail()->evidence, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('answer', $evidence);
        $this->assertStringNotContainsString('transcript', $evidence);
        $this->assertStringNotContainsString('audio', $evidence);
        $this->assertStringNotContainsString('feedback', $evidence);
    }

    public function test_restaurant_content_is_only_served_by_the_private_entitled_route(): void
    {
        $this->publishRestaurant();
        $player = $this->entitledPlayer();

        $this->getJson('/api/v1/conversations/restaurant-el-reloj')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'published_content_not_found');

        $response = $this->actingAs($player)
            ->getJson(route('game.madrid.restaurant.content', ['locale' => 'nl-NL']))
            ->assertOk()
            ->assertJsonPath('data.slug', 'restaurant-el-reloj')
            ->assertJsonPath('data.links.self', route('game.madrid.restaurant.content'))
            ->assertJsonPath('data.content.domain_data.runtime_access.entitlement', 'trial_week');

        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_restaurant_feedback_is_pinned_to_carmens_scenario(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->postJson(route('game.madrid.restaurant.feedback'), [
                'scenario_slug' => 'taxi-diego',
                'step_id' => 'turn.ask_table',
                'answer' => 'Buenas noches',
                'level' => 'A1',
                'source' => 'typed_assist',
                'transcript_confidence_status' => null,
                'transcript_corrected' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario_slug');
    }

    public function test_every_restaurant_level_is_idempotent_and_wrong_routes_are_rejected(): void
    {
        $this->publishRestaurant();

        foreach ([
            ['A0', 'branch.still_or_sparkling'],
            ['A1', 'branch.drink_alternative'],
            ['A2', 'branch.drink_recommendation'],
        ] as [$level, $branchStep]) {
            $player = $this->entitledPlayer();
            $payload = $this->completionPayload(level: $level, branchStep: $branchStep);

            $this->actingAs($player)->postJson(route('game.madrid.restaurant.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.mission.status', 'completed');
            $this->actingAs($player)->postJson(route('game.madrid.restaurant.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.last_attempt.duplicate', true)
                ->assertJsonPath('data.last_attempt.awarded_now.xp', 0);

            $this->assertSame(1, $player->missionAttempts()->where('mission_key', 'mission.madrid.restaurant.order')->count());
        }

        $player = $this->entitledPlayer();
        $invalid = $this->completionPayload();
        $invalid['turns'][3]['step_id'] = 'turn.request_bill';
        $this->actingAs($player)
            ->postJson(route('game.madrid.restaurant.complete'), $invalid)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'mission_evidence_invalid');
        $this->assertSame(0, $player->missionAttempts()->count());
    }

    /** @return array<string, mixed> */
    private function completionPayload(
        bool $usedRepairStrategy = false,
        string $level = 'A1',
        string $branchStep = 'branch.drink_alternative',
    ): array {
        $stepIds = [
            'turn.ask_table',
            'turn.order_drink',
            $branchStep,
            'turn.order_food',
            'turn.request_bill',
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

    private function publishRestaurant(): ContentNode
    {
        $domainData = json_decode(
            file_get_contents(base_path('content/examples/restaurant-dialogue-domain-data.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'restaurant-el-reloj',
            locale: 'es-ES',
            title: 'Restaurantgesprek met Carmen',
            domainData: $domainData,
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Restaurantdialoog en niveaupaden gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Restaurant dag 3',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde restaurant-missietest.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }
}
