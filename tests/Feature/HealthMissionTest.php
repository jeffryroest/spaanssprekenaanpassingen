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

class HealthMissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_routes_require_the_trial_week_entitlement(): void
    {
        $player = User::factory()->create();

        $this->actingAs($player)
            ->get(route('game.madrid.health'))
            ->assertRedirect(route('trial-week.show'))
            ->assertSessionHas('access_notice');

        $this->actingAs($player)
            ->getJson(route('game.madrid.health.content'))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');

        $this->actingAs($player)
            ->postJson(route('game.madrid.health.complete'), $this->completionPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'entitlement_required');
    }

    public function test_entitled_player_gets_the_fictional_roleplay_and_privacy_shell(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->get(route('game.madrid.health'))
            ->assertOk()
            ->assertSee('data-scenario-dialogue', false)
            ->assertSee('data-health-dialogue', false)
            ->assertSee('data-sensitive-roleplay="true"', false)
            ->assertSee('data-scenario-slug="consulta-elena"', false)
            ->assertSee(route('game.madrid.health.transcription'), false)
            ->assertSee(route('game.madrid.health.feedback'), false)
            ->assertSee(route('game.madrid.health.complete'), false)
            ->assertSee('Gebruik alleen deze gegevens')
            ->assertSee('Deel geen echte medische gegevens')
            ->assertSee('geen medisch advies')
            ->assertSee('niet in <code>sessionStorage</code>', false)
            ->assertSee('Elena kan het rollenspel nog niet openen');
    }

    public function test_published_health_mission_unlocks_day_five_and_persists_only_structural_evidence(): void
    {
        $this->publishHealthMission();
        $player = $this->entitledPlayer();

        $this->actingAs($player)
            ->getJson(route('game.trial-week.status'))
            ->assertOk()
            ->assertJsonPath('data.days.4.content_state', 'published')
            ->assertJsonPath('data.days.4.access_state', 'available')
            ->assertJsonPath('data.days.4.action_url', route('game.madrid.health'));

        $response = $this->actingAs($player)->postJson(
            route('game.madrid.health.complete'),
            $this->completionPayload(usedRepairStrategy: true),
        );

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('data.balances.xp', 150)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.balances.valentia', 1)
            ->assertJsonPath('data.mission.key', 'mission.madrid.health.appointment')
            ->assertJsonPath('data.mission.status', 'completed')
            ->assertJsonPath('data.mission.spoken_goal_completed', true)
            ->assertJsonCount(4, 'data.rewards')
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false)
            ->assertJsonPath('meta.feedback_persisted', false)
            ->assertJsonPath('meta.health_data_persisted', false);

        $this->assertDatabaseHas('user_mission_progress', [
            'user_id' => $player->getKey(),
            'mission_key' => 'mission.madrid.health.appointment',
            'best_xp' => 150,
            'best_spoken_turns' => 3,
        ]);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'stamp.first_consulta_conversation']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'item.consulta_phrase_card']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'madrid.station.preview']);
        $this->assertDatabaseHas('user_rewards', ['reward_key' => 'badge.pregunta_clara']);

        $evidence = json_encode($player->missionAttempts()->firstOrFail()->evidence, JSON_THROW_ON_ERROR);
        $normalizedEvidence = strtolower($evidence);
        foreach (['answer', 'transcript', 'audio', 'feedback', 'garganta', 'tos seca', 'fiebre', 'respirar'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $normalizedEvidence);
        }
    }

    public function test_health_content_is_only_served_by_the_private_entitled_route(): void
    {
        $this->publishHealthMission();
        $player = $this->entitledPlayer();

        $this->getJson('/api/v1/conversations/consulta-elena')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'published_content_not_found');

        $response = $this->actingAs($player)
            ->getJson(route('game.madrid.health.content', ['locale' => 'nl-NL']))
            ->assertOk()
            ->assertJsonPath('data.slug', 'consulta-elena')
            ->assertJsonPath('data.links.self', route('game.madrid.health.content'))
            ->assertJsonPath('data.content.domain_data.runtime_access.entitlement', 'trial_week')
            ->assertJsonPath('data.content.domain_data.roleplay.fictional', true);

        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_health_feedback_is_pinned_to_elenas_scenario(): void
    {
        $this->actingAs($this->entitledPlayer())
            ->postJson(route('game.madrid.health.feedback'), [
                'scenario_slug' => 'restaurant-el-reloj',
                'step_id' => 'turn.describe_symptoms',
                'answer' => 'Me duele la garganta.',
                'level' => 'A1',
                'source' => 'typed_assist',
                'transcript_confidence_status' => null,
                'transcript_corrected' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scenario_slug');
    }

    public function test_every_health_level_is_idempotent_and_wrong_routes_are_rejected(): void
    {
        $this->publishHealthMission();

        foreach ([
            ['A0', 'branch.fever_check'],
            ['A1', 'branch.cough_detail'],
            ['A2', 'branch.swallow_or_breathe'],
        ] as [$level, $branchStep]) {
            $player = $this->entitledPlayer();
            $payload = $this->completionPayload(level: $level, branchStep: $branchStep);

            $this->actingAs($player)->postJson(route('game.madrid.health.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.mission.status', 'completed');
            $this->actingAs($player)->postJson(route('game.madrid.health.complete'), $payload)
                ->assertOk()
                ->assertJsonPath('data.last_attempt.duplicate', true)
                ->assertJsonPath('data.last_attempt.awarded_now.xp', 0);

            $this->assertSame(1, $player->missionAttempts()->where('mission_key', 'mission.madrid.health.appointment')->count());
        }

        $player = $this->entitledPlayer();
        $invalid = $this->completionPayload();
        $invalid['turns'][2]['step_id'] = 'branch.fever_check';
        $this->actingAs($player)
            ->postJson(route('game.madrid.health.complete'), $invalid)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'mission_evidence_invalid');
        $this->assertSame(0, $player->missionAttempts()->count());
    }

    /** @return array<string, mixed> */
    private function completionPayload(
        bool $usedRepairStrategy = false,
        string $level = 'A1',
        string $branchStep = 'branch.cough_detail',
    ): array {
        $stepIds = [
            'turn.describe_symptoms',
            'turn.explain_duration',
            $branchStep,
            'turn.request_written_explanation',
            'turn.confirm_and_locate_pharmacy',
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

    private function publishHealthMission(): ContentNode
    {
        $domainData = json_decode(
            file_get_contents(base_path('content/examples/health-dialogue-domain-data.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'consulta-elena',
            locale: 'es-ES',
            title: 'Fictief consultrollenspel met Elena',
            domainData: $domainData,
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Taal, fictieve rolkaart en privacygrenzen gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Gezondheidsrollenspel dag 5',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde gezondheidsmissietest met fictieve rolkaart.',
            acknowledgeWarnings: true,
        );

        return $node->refresh();
    }
}
