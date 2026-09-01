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
use App\Models\UserMissionProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinalMissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_final_routes_require_the_trial_week_entitlement(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)->get(route('game.madrid.final'))
            ->assertRedirect(route('trial-week.show'));
        $this->actingAs($player)->getJson(route('game.madrid.final.content'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
        $this->actingAs($player)->postJson(route('game.madrid.final.complete'), $this->completionPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_final_shell_recognizes_only_structural_completed_encounters(): void
    {
        $player = $this->entitledPlayer();

        $this->actingAs($player)->get(route('game.madrid.final'))
            ->assertOk()
            ->assertSee('data-final-dialogue', false)
            ->assertSee('data-memory-returning="false"', false)
            ->assertSee('0/5 ontmoet');

        UserMissionProgress::query()->create([
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.panaderia.breakfast',
            'source_content_version' => 1,
            'status' => 'completed',
            'completion_count' => 2,
            'best_xp' => 120,
            'best_spoken_turns' => 3,
            'spoken_goal_completed' => true,
            'state_snapshot' => ['world_states' => ['madrid.panaderia.completed']],
            'first_completed_at' => now()->subDay(),
            'last_completed_at' => now(),
        ]);

        $response = $this->actingAs($player)->get(route('game.madrid.final'))
            ->assertOk()
            ->assertSee('data-memory-returning="true"', false)
            ->assertSee('Lucía herkent je')
            ->assertSee('1/5 ontmoet')
            ->assertSee('Vrije antwoorden, transcripties, audio en feedback worden niet gebruikt');

        $this->assertStringNotContainsString('state_snapshot', $response->getContent());
        $this->assertStringNotContainsString('mission_key', $response->getContent());
    }

    public function test_published_final_unlocks_day_seven_and_uses_private_revision_media(): void
    {
        $this->publishFinal();
        $player = $this->entitledPlayer();

        $this->actingAs($player)->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.6.content_state', 'published')
            ->assertJsonPath('data.days.6.access_state', 'available')
            ->assertJsonPath('data.days.6.action_url', route('game.madrid.final'));

        $response = $this->actingAs($player)
            ->getJson(route('game.madrid.final.content', ['locale' => 'nl-NL']))
            ->assertOk()
            ->assertJsonPath('data.slug', 'madrid-final-lucia')
            ->assertJsonPath('data.content.domain_data.memory.returning_npc_id', 'npc.lucia.martin')
            ->assertJsonCount(5, 'data.content.domain_data.memory.recap_sources')
            ->assertJsonPath('data.content.media.scene_background.url', route('game.madrid.final.media', ['version' => 1, 'role' => 'scene_background']))
            ->assertJsonPath('data.content.media.npc_expression_sheet.url', route('game.madrid.final.media', ['version' => 1, 'role' => 'npc_expression_sheet']));

        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_every_final_level_is_idempotent_and_persists_no_free_answers(): void
    {
        $this->publishFinal();

        foreach ([
            ['A0', 'branch.choose_drink'],
            ['A1', 'branch.accept_alternative'],
            ['A2', 'branch.explain_preference'],
        ] as [$level, $branchStep]) {
            $player = $this->entitledPlayer();
            $payload = $this->completionPayload(level: $level, branchStep: $branchStep);

            $this->actingAs($player)->postJson(route('game.madrid.final.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.mission.key', 'mission.madrid.week.final')
                ->assertJsonPath('data.mission.status', 'completed')
                ->assertJsonPath('data.balances.xp', 200)
                ->assertJsonPath('meta.memory_source', 'completed_missions_only');
            $this->actingAs($player)->postJson(route('game.madrid.final.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.last_attempt.duplicate', true)
                ->assertJsonPath('data.last_attempt.awarded_now.xp', 0);

            $this->assertDatabaseHas('user_rewards', ['user_id' => $player->getKey(), 'reward_key' => 'stamp.madrid_week_complete']);
            $this->assertDatabaseHas('user_rewards', ['user_id' => $player->getKey(), 'reward_key' => 'item.madrid_memory_postcard']);
            $this->assertDatabaseHas('user_rewards', ['user_id' => $player->getKey(), 'reward_key' => 'spain.next_city.preview']);
            $this->assertSame(1, $player->missionAttempts()->where('mission_key', 'mission.madrid.week.final')->count());

            $evidence = json_encode($player->missionAttempts()->firstOrFail()->evidence, JSON_THROW_ON_ERROR);
            foreach (['answer', 'transcript', 'audio', 'feedback', 'tarta de naranja', 'pago con tarjeta'] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, strtolower($evidence));
            }
        }
    }

    /** @return array<string, mixed> */
    private function completionPayload(string $level = 'A1', string $branchStep = 'branch.accept_alternative'): array
    {
        $stepIds = [
            'turn.return_greeting',
            'turn.share_week',
            $branchStep,
            'turn.plan_next_stop',
            'turn.confirm_order',
        ];

        return [
            'completion_key' => (string) Str::uuid(),
            'level' => $level,
            'used_repair_strategy' => false,
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
        $plan = SubscriptionPlan::query()->firstOrCreate(['code' => 'test-final-week'], [
            'name' => 'Testtoegang finale',
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

    private function publishFinal(): ContentNode
    {
        $domainData = json_decode(file_get_contents(base_path('content/examples/final-dialogue-domain-data.json')), true, flags: JSON_THROW_ON_ERROR);
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $assets = app(GoldenRouteMedia::class)->ensure($editor, ['la_espiga_interior', 'lucia_expressions']);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'madrid-final-lucia',
            locale: 'es-ES',
            title: 'Slotmissie met Lucía',
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
            note: 'Slotdialoog en privacyveilige NPC-herkenning gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Slotmissie dag 7',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde slotmissietest.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }
}
