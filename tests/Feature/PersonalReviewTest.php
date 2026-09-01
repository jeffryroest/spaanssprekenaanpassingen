<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\Actions\PlayerProgress\CompletePersonalReview;
use App\ContentStudio\GoldenRouteMedia;
use App\Enums\BillingInterval;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Enums\SubscriptionStatus;
use App\Models\ContentNode;
use App\Models\MissionAttempt;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserMissionProgress;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonalReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        CarbonImmutable::setTestNow('2026-09-01 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_personal_review_requires_trial_week_access(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('game.madrid.review'))
            ->assertRedirect(route('trial-week.show'));

        $this->actingAs($player)
            ->getJson(route('game.madrid.review.deck'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_empty_review_explains_that_a_mission_must_be_completed_first(): void
    {
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->get(route('game.madrid.review'))
            ->assertOk()
            ->assertSee('Je kaartenbak is nog leeg')
            ->assertSee('Speel eerst een gesprek')
            ->assertSee('data-personal-review', false);

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.3.content_state', 'published')
            ->assertJsonPath('data.days.3.access_state', 'available')
            ->assertJsonPath('data.days.3.action_url', route('game.madrid.review'));
    }

    public function test_review_prioritizes_assisted_turns_and_never_returns_personal_answers(): void
    {
        $node = $this->publishPanaderia();
        $player = $this->entitledPlayer();
        $this->recordPanaderiaCompletion($player, $node);

        $response = $this->actingAs($player)->getJson(route('game.madrid.review.deck'));

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonCount(5, 'data.cards')
            ->assertJsonPath('data.cards.0.step_id', 'turn.finish_order')
            ->assertJsonPath('data.cards.0.due_state', 'new')
            ->assertJsonPath('data.meta.completed_sources', 1)
            ->assertJsonPath('meta.answer_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false);
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $payload = strtolower($response->getContent());
        $this->assertStringNotContainsString('mijn eigen geheime antwoord', $payload);
        $this->assertStringNotContainsString('provider_subscription_ref', $payload);
    }

    public function test_review_schedules_cards_and_books_the_daily_reward_once(): void
    {
        $node = $this->publishPanaderia();
        $player = $this->entitledPlayer();
        $this->recordPanaderiaCompletion($player, $node);
        $cards = $this->actingAs($player)
            ->getJson(route('game.madrid.review.deck'))
            ->json('data.cards');
        $payload = $this->completionPayload($cards);

        $response = $this->actingAs($player)
            ->postJson(route('game.madrid.review.complete'), $payload);

        $response->assertOk()
            ->assertJsonPath('data.balances.xp', 20)
            ->assertJsonPath('data.balances.confianza', 1)
            ->assertJsonPath('data.mission.key', CompletePersonalReview::MISSION_KEY)
            ->assertJsonPath('data.last_attempt.awarded_now.xp', 20)
            ->assertJsonPath('data.last_attempt.awarded_now.confianza', 1)
            ->assertJsonPath('data.review.personal_answers_persisted', false)
            ->assertJsonPath('meta.answer_persisted', false)
            ->assertJsonPath('meta.audio_persisted', false);
        $this->assertDatabaseCount('user_practice_items', 5);
        $this->assertDatabaseHas('user_practice_items', [
            'user_id' => $player->getKey(),
            'practice_key' => $cards[0]['practice_key'],
            'last_rating' => 'again',
            'interval_days' => 0,
            'lapse_count' => 1,
        ]);
        $this->assertDatabaseHas('user_practice_items', [
            'user_id' => $player->getKey(),
            'practice_key' => $cards[1]['practice_key'],
            'last_rating' => 'hard',
            'interval_days' => 1,
        ]);
        $this->assertDatabaseHas('user_practice_items', [
            'user_id' => $player->getKey(),
            'practice_key' => $cards[2]['practice_key'],
            'last_rating' => 'good',
            'interval_days' => 3,
        ]);
        $this->assertDatabaseHas('user_practice_items', [
            'user_id' => $player->getKey(),
            'practice_key' => $cards[3]['practice_key'],
            'last_rating' => 'easy',
            'interval_days' => 7,
        ]);
        $this->assertDatabaseCount('game_ledger', 2);

        $evidence = json_encode(
            MissionAttempt::query()->where('mission_key', CompletePersonalReview::MISSION_KEY)->firstOrFail()->evidence,
            JSON_THROW_ON_ERROR,
        );
        $this->assertStringNotContainsString('answer', $evidence);
        $this->assertStringNotContainsString('transcript', $evidence);
        $this->assertStringNotContainsString('audio', $evidence);
        $this->assertStringNotContainsString('napolitana', strtolower($evidence));

        $this->actingAs($player)
            ->postJson(route('game.madrid.review.complete'), $payload)
            ->assertOk()
            ->assertJsonPath('data.last_attempt.duplicate', true);
        $this->assertDatabaseCount('user_practice_items', 5);
        $this->assertDatabaseCount('game_ledger', 2);

        CarbonImmutable::setTestNow('2026-09-01 09:11:00');
        $dueCard = $this->actingAs($player)
            ->getJson(route('game.madrid.review.deck'))
            ->assertJsonCount(1, 'data.cards')
            ->json('data.cards.0');
        $second = $this->completionPayload([$dueCard]);
        $second['cards'][0]['rating'] = 'hard';
        $this->actingAs($player)
            ->postJson(route('game.madrid.review.complete'), $second)
            ->assertOk()
            ->assertJsonPath('data.last_attempt.awarded_now.xp', 0)
            ->assertJsonPath('data.last_attempt.awarded_now.confianza', 0)
            ->assertJsonPath('data.review.daily_reward_already_claimed', true);
        $this->assertDatabaseCount('game_ledger', 2);
    }

    public function test_unknown_cards_and_personal_content_are_rejected(): void
    {
        $node = $this->publishPanaderia();
        $player = $this->entitledPlayer();
        $this->recordPanaderiaCompletion($player, $node);

        $this->actingAs($player)
            ->postJson(route('game.madrid.review.complete'), [
                'completion_key' => (string) Str::uuid(),
                'cards' => [[
                    'practice_key' => str_repeat('a', 64),
                    'source' => 'speech',
                    'assisted' => false,
                    'rating' => 'easy',
                    'answer' => 'Mijn geheime antwoord',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cards.0');

        $this->actingAs($player)
            ->postJson(route('game.madrid.review.complete'), [
                'completion_key' => (string) Str::uuid(),
                'cards' => [[
                    'practice_key' => str_repeat('a', 64),
                    'source' => 'speech',
                    'assisted' => false,
                    'rating' => 'easy',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'review_evidence_invalid');
        $this->assertDatabaseCount('user_practice_items', 0);
    }

    /** @param list<array<string, mixed>> $cards @return array<string, mixed> */
    private function completionPayload(array $cards): array
    {
        $ratings = ['again', 'hard', 'good', 'easy', 'good'];

        return [
            'completion_key' => (string) Str::uuid(),
            'cards' => array_map(static fn (array $card, int $index): array => [
                'practice_key' => $card['practice_key'],
                'source' => $index < 3 ? 'speech' : 'typed_assist',
                'assisted' => $index === 0,
                'rating' => $ratings[$index] ?? 'good',
            ], $cards, array_keys($cards)),
        ];
    }

    private function recordPanaderiaCompletion(User $player, ContentNode $node): void
    {
        $completedAt = now()->subDay();
        UserMissionProgress::query()->create([
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.panaderia.breakfast',
            'source_content_node_id' => $node->getKey(),
            'source_content_version' => 1,
            'status' => 'completed',
            'completion_count' => 1,
            'best_xp' => 100,
            'best_spoken_turns' => 2,
            'spoken_goal_completed' => false,
            'state_snapshot' => ['world_states' => ['madrid.panaderia.completed']],
            'first_completed_at' => $completedAt,
            'last_completed_at' => $completedAt,
        ]);
        MissionAttempt::query()->create([
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.panaderia.breakfast',
            'source_content_node_id' => $node->getKey(),
            'source_content_version' => 1,
            'attempt_number' => 1,
            'completion_key' => (string) Str::uuid(),
            'status' => 'completed',
            'level' => 'A1',
            'completed_turns' => 5,
            'spoken_turns' => 2,
            'assist_count' => 1,
            'used_repair_strategy' => false,
            'earned_xp' => 100,
            'earned_confianza' => 2,
            'earned_valentia' => 1,
            'evidence' => [
                'turns' => [
                    ['step_id' => 'turn.greet_order', 'turn' => 1, 'source' => 'speech', 'assisted' => false],
                    ['step_id' => 'turn.finish_order', 'turn' => 2, 'source' => 'typed_assist', 'assisted' => true],
                    ['step_id' => 'branch.unavailable', 'turn' => 3, 'source' => 'typed_assist', 'assisted' => false],
                    ['step_id' => 'turn.takeaway', 'turn' => 4, 'source' => 'speech', 'assisted' => false],
                    ['step_id' => 'turn.payment', 'turn' => 5, 'source' => 'typed_assist', 'assisted' => false],
                ],
                'performance' => ['target_xp' => 100, 'target_confianza' => 2, 'target_valentia' => 1],
            ],
            'completed_at' => $completedAt,
        ]);
    }

    private function publishPanaderia(): ContentNode
    {
        $domainData = json_decode(
            file_get_contents(base_path('content/examples/panaderia-dialogue-domain-data.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $assets = app(GoldenRouteMedia::class)->ensure($editor, ['la_espiga_interior', 'lucia_expressions']);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'la-espiga-lucia',
            locale: 'es-ES',
            title: 'La Espiga · Lucía',
            domainData: $domainData,
            media: [
                'scene_background' => $assets->get('la_espiga_interior')->getKey(),
                'npc_expression_sheet' => $assets->get('lucia_expressions')->getKey(),
            ],
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Herhalingsbronscenario gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Herhalingsbronscenario',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde persoonlijke-herhalingstest.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }

    private function entitledPlayer(): User
    {
        $player = User::factory()->create();
        $plan = SubscriptionPlan::query()->create([
            'code' => 'test-personal-review-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Testtoegang persoonlijke herhaling',
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
}
