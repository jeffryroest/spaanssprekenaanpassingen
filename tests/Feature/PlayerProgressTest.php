<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlayerProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_completion_is_calculated_server_side_and_persisted_without_sensitive_content(): void
    {
        $this->publishPanaderia();
        $player = User::factory()->create();

        $response = $this->actingAs($player)->postJson(
            route('game.madrid.panaderia.complete'),
            $this->completionPayload(usedRepairStrategy: true),
        );

        $response->assertOk()
            ->assertJsonPath('schema_version', '1.0.0')
            ->assertJsonPath('meta.account_persisted', true)
            ->assertJsonPath('meta.audio_persisted', false)
            ->assertJsonPath('meta.transcript_persisted', false)
            ->assertJsonPath('meta.feedback_persisted', false)
            ->assertJsonPath('data.balances.xp', 120)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.balances.valentia', 1)
            ->assertJsonPath('data.mission.status', 'completed')
            ->assertJsonPath('data.mission.spoken_goal_completed', true)
            ->assertJsonPath('data.last_attempt.awarded_now.xp', 120)
            ->assertJsonCount(5, 'data.rewards');

        $this->assertDatabaseCount('mission_attempts', 1);
        $this->assertDatabaseCount('game_ledger', 3);
        $this->assertDatabaseCount('user_rewards', 5);

        $evidence = json_encode(
            $player->missionAttempts()->firstOrFail()->evidence,
            JSON_THROW_ON_ERROR,
        );
        $this->assertStringNotContainsString('answer', $evidence);
        $this->assertStringNotContainsString('transcript', $evidence);
        $this->assertStringNotContainsString('audio', $evidence);
        $this->assertStringNotContainsString('feedback', $evidence);
    }

    public function test_repeating_the_same_completion_key_is_idempotent(): void
    {
        $this->publishPanaderia();
        $player = User::factory()->create();
        $payload = $this->completionPayload();

        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $payload)->assertOk();
        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $payload)
            ->assertOk()
            ->assertJsonPath('data.last_attempt.duplicate', true)
            ->assertJsonPath('data.last_attempt.awarded_now.xp', 0)
            ->assertJsonPath('data.balances.xp', 120);

        $this->assertDatabaseCount('mission_attempts', 1);
        $this->assertDatabaseCount('game_ledger', 3);
        $this->assertDatabaseCount('user_rewards', 4);
    }

    public function test_each_published_level_branch_can_complete(): void
    {
        $this->publishPanaderia();

        foreach ([
            ['A0', 'branch.quantity'],
            ['A1', 'branch.unavailable'],
            ['A2', 'branch.bread_type'],
        ] as [$level, $branchStep]) {
            $player = User::factory()->create();

            $this->actingAs($player)->postJson(
                route('game.madrid.panaderia.complete'),
                $this->completionPayload(level: $level, branchStep: $branchStep),
            )->assertOk()
                ->assertJsonPath('data.mission.status', 'completed')
                ->assertJsonPath('data.balances.xp', 120);
        }
    }

    public function test_published_route_mismatch_is_rejected_without_an_attempt(): void
    {
        $this->publishPanaderia();
        $player = User::factory()->create();
        $payload = $this->completionPayload();
        $payload['turns'][2]['step_id'] = 'branch.quantity';

        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'mission_evidence_invalid');

        $this->assertDatabaseCount('mission_attempts', 0);
        $this->assertDatabaseCount('game_ledger', 0);
    }

    public function test_client_cannot_submit_rewards_or_private_learning_content(): void
    {
        $player = User::factory()->create();
        $payload = $this->completionPayload();
        $payload['xp'] = 999999;
        $payload['transcript'] = 'gevoelige inhoud';

        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['xp', 'transcript']);

        $this->assertDatabaseCount('game_ledger', 0);
    }

    public function test_guest_cannot_write_account_progress(): void
    {
        $this->postJson(route('game.madrid.panaderia.complete'), $this->completionPayload())
            ->assertUnauthorized();
    }

    public function test_progress_survives_a_new_login_session_and_is_visible_on_dashboard(): void
    {
        $this->publishPanaderia();
        $player = User::factory()->create(['name' => 'María']);

        $this->actingAs($player)
            ->postJson(route('game.madrid.panaderia.complete'), $this->completionPayload())
            ->assertOk();
        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->post(route('login.store'), [
            'email' => $player->email,
            'password' => 'password',
        ])->assertRedirect(route('player.progress', absolute: false));

        $this->get(route('player.progress'))
            ->assertOk()
            ->assertSee('Hola, María')
            ->assertSee('120 XP')
            ->assertSee('Mi primera compra')
            ->assertSee('Broodzak van La Espiga');
    }

    public function test_a_better_replay_only_books_the_improvement_and_never_duplicates_unique_rewards(): void
    {
        $this->publishPanaderia();
        $player = User::factory()->create();
        $assisted = $this->completionPayload(sources: array_fill(0, 5, 'choice_assist'), assisted: true);

        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $assisted)
            ->assertOk()
            ->assertJsonPath('data.balances.xp', 80)
            ->assertJsonPath('data.balances.confianza', 0)
            ->assertJsonPath('data.balances.valentia', 1);

        $improved = $this->completionPayload();
        $this->actingAs($player)->postJson(route('game.madrid.panaderia.complete'), $improved)
            ->assertOk()
            ->assertJsonPath('data.last_attempt.awarded_now.xp', 40)
            ->assertJsonPath('data.last_attempt.awarded_now.confianza', 3)
            ->assertJsonPath('data.last_attempt.awarded_now.valentia', 0)
            ->assertJsonPath('data.balances.xp', 120)
            ->assertJsonPath('data.balances.confianza', 3)
            ->assertJsonPath('data.mission.completion_count', 2);

        $this->assertDatabaseCount('mission_attempts', 2);
        $this->assertDatabaseCount('game_ledger', 4);
        $this->assertDatabaseCount('user_rewards', 4);
    }

    /**
     * @param  list<string>|null  $sources
     * @return array<string, mixed>
     */
    private function completionPayload(
        bool $usedRepairStrategy = false,
        ?array $sources = null,
        bool $assisted = false,
        string $level = 'A1',
        string $branchStep = 'branch.unavailable',
    ): array {
        $sources ??= ['speech', 'speech', 'speech', 'typed_assist', 'typed_assist'];
        $stepIds = [
            'turn.greet_order',
            'turn.finish_order',
            $branchStep,
            'turn.takeaway',
            'turn.payment',
        ];

        return [
            'completion_key' => (string) Str::uuid(),
            'level' => $level,
            'used_repair_strategy' => $usedRepairStrategy,
            'turns' => array_map(
                static fn (string $stepId, int $index): array => [
                    'step_id' => $stepId,
                    'source' => $sources[$index],
                    'assisted' => $assisted,
                ],
                $stepIds,
                array_keys($stepIds),
            ),
        ];
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
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'la-espiga-lucia',
            locale: 'es-ES',
            title: 'La Espiga met Lucía',
            domainData: $domainData,
        );
        app(SubmitContentForReview::class)->handle($editor, $contentNode, 1);
        app(DecideContentReview::class)->handle(
            actor: $reviewer,
            contentNode: $contentNode,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Dialoog en beloningscontract gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'La Espiga voor voortgangstest',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde accountvoortgangstest.',
            acknowledgeWarnings: true,
        );

        return $contentNode->refresh();
    }
}
