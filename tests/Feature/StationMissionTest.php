<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\ContentStudio\GoldenRouteMedia;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StationMissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_station_routes_require_the_trial_week_entitlement(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('game.madrid.station'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice');

        $this->actingAs($player)
            ->getJson(route('game.madrid.station.content'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');

        $this->actingAs($player)
            ->get(route('game.madrid.station.media', ['version' => 1, 'role' => 'scene_background']))
            ->assertRedirect(route('trial-week.show'));

        $this->actingAs($player)
            ->postJson(route('game.madrid.station.complete'), $this->completionPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_entitled_player_gets_the_visual_station_speaking_shell(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->get(route('game.madrid.station'))
            ->assertOk()
            ->assertSee('data-scenario-dialogue', false)
            ->assertSee('data-station-dialogue', false)
            ->assertSee('data-scenario-slug="estacion-mateo"', false)
            ->assertSee('madrid-station-hall.webp')
            ->assertSee('mateo-station-expressions.webp')
            ->assertSee('data-journey-card', false)
            ->assertSee(route('game.madrid.station.transcription'), false)
            ->assertSee(route('game.madrid.station.feedback'), false)
            ->assertSee(route('game.madrid.station.complete'), false)
            ->assertSee('Mateo kan het loket nog niet openen');
    }

    public function test_published_station_unlocks_day_six_and_persists_only_structural_evidence(): void
    {
        $this->publishStation();
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.5.content_state', 'published')
            ->assertJsonPath('data.days.5.access_state', 'available')
            ->assertJsonPath('data.days.5.action_url', route('game.madrid.station'));

        $response = $this->actingAs($player)->postJson(
            route('game.madrid.station.complete'),
            $this->completionPayload(usedRepairStrategy: true),
        );

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.balances.xp', 160)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.balances.valentia', 1)
            ->assertJsonPath('data.mission.key', 'mission.madrid.station.ticket')
            ->assertJsonPath('data.mission.spoken_goal_completed', true)
            ->assertJsonCount(4, 'data.rewards')
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false)
            ->assertJsonPath('meta.feedback_persisted', false)
            ->assertJsonPath('meta.travel_data_persisted', false);

        $this->assertDatabaseHas('user_mission_progress', [
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.station.ticket',
            'best_xp' => 160,
            'best_spoken_turns' => 3,
        ]);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'stamp.first_madrid_train_ticket']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'item.toledo_train_ticket']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'madrid.week_final.preview']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'badge.viaje_resuelto']);

        $evidence = json_encode($player->missionAttempts()->firstOrFail()->evidence, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('answer', $evidence);
        $this->assertStringNotContainsString('toledo', strtolower($evidence));
        $this->assertStringNotContainsString('domingo', strtolower($evidence));
        $this->assertStringNotContainsString('tarjeta', strtolower($evidence));
        $this->assertStringNotContainsString('transcript', $evidence);
        $this->assertStringNotContainsString('audio', $evidence);
        $this->assertStringNotContainsString('feedback', $evidence);
    }

    public function test_station_content_and_feedback_are_pinned_to_the_private_scenario(): void
    {
        $this->publishStation();
        $player = $this->entitledPlayer();

        $this->getJson('/api/v1/conversations/estacion-mateo')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'published_content_not_found');

        $response = $this->actingAs($player)
            ->getJson(route('game.madrid.station.content', ['locale' => 'nl-NL']))
            ->assertOk()
            ->assertJsonPath('data.slug', 'estacion-mateo')
            ->assertJsonPath('data.content.domain_data.journey.fictional', true)
            ->assertJsonPath('data.content.domain_data.runtime_access.entitlement', 'trial_week')
            ->assertJsonPath('data.content.media.scene_background.url', route('game.madrid.station.media', ['version' => 1, 'role' => 'scene_background']))
            ->assertJsonPath('data.content.media.npc_expression_sheet.url', route('game.madrid.station.media', ['version' => 1, 'role' => 'npc_expression_sheet']));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $mediaResponse = $this->actingAs($player)->get(route('game.madrid.station.media', [
            'version' => 1,
            'role' => 'scene_background',
        ]));
        $mediaResponse->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->assertStringContainsString('private', (string) $mediaResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $mediaResponse->headers->get('Cache-Control'));

        $this->actingAs($player)
            ->get(route('game.madrid.station.media', ['version' => 99, 'role' => 'scene_background']))
            ->assertNotFound();

        $this->actingAs($player)
            ->postJson(route('game.madrid.station.feedback'), [
                'scenario_slug' => 'consulta-elena',
                'step_id' => 'turn.request_ticket',
                'answer' => 'Buenos días',
                'level' => 'A1',
                'source' => 'typed_assist',
                'transcript_confidence_status' => null,
                'transcript_corrected' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario_slug');
    }

    public function test_every_station_level_is_idempotent_and_wrong_routes_are_rejected(): void
    {
        $this->publishStation();

        foreach ([
            ['A0', 'branch.choose_time'],
            ['A1', 'branch.accept_later_train'],
            ['A2', 'branch.compare_connections'],
        ] as [$level, $branchStep]) {
            $player = $this->entitledPlayer();
            $payload = $this->completionPayload(level: $level, branchStep: $branchStep);

            $this->actingAs($player)->postJson(route('game.madrid.station.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.mission.status', 'completed');
            $this->actingAs($player)->postJson(route('game.madrid.station.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.last_attempt.duplicate', true)
                ->assertJsonPath('data.last_attempt.awarded_now.xp', 0);

            $this->assertSame(1, $player->missionAttempts()->where('mission_key', 'mission.madrid.station.ticket')->count());
        }

        $player = $this->entitledPlayer();
        $invalid = $this->completionPayload();
        $invalid['turns'][3]['step_id'] = 'turn.confirm_details';
        $this->actingAs($player)
            ->postJson(route('game.madrid.station.complete'), $invalid)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'mission_evidence_invalid');
        $this->assertSame(0, $player->missionAttempts()->count());
    }

    /** @return array<string, mixed> */
    private function completionPayload(
        bool $usedRepairStrategy = false,
        string $level = 'A1',
        string $branchStep = 'branch.accept_later_train',
    ): array {
        $stepIds = [
            'turn.request_ticket',
            'turn.choose_return',
            $branchStep,
            'turn.ask_price',
            'turn.confirm_details',
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

    private function publishStation(): ContentNode
    {
        $domainData = json_decode(
            file_get_contents(base_path('content/examples/station-dialogue-domain-data.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $assets = app(GoldenRouteMedia::class)->ensure($editor, [
            'madrid_station_hall',
            'mateo_station_expressions',
        ]);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'estacion-mateo',
            locale: 'es-ES',
            title: 'Stationsgesprek met Mateo',
            domainData: $domainData,
            media: [
                'scene_background' => $assets->get('madrid_station_hall')->getKey(),
                'npc_expression_sheet' => $assets->get('mateo_station_expressions')->getKey(),
            ],
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Stationsdialoog, oefenreis en niveaupaden gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Station dag 6',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde stationsmissietest.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }
}
