<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ContentStudioReleaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_publishers_can_create_or_manage_releases_while_read_roles_can_view_them(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);

        $this->actingAs($this->editor())
            ->get(route('content-studio.releases.create'))
            ->assertForbidden();

        $this->actingAs($this->editor())
            ->post(route('content-studio.releases.store'), [
                'name' => 'Niet toegestaan',
                'target_channel' => ContentReleaseChannel::Preview->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->userWithRole(ContentRole::Auditor))
            ->get(route('content-studio.releases.show', $release))
            ->assertOk()
            ->assertSee($release->name)
            ->assertDontSee('Release uitvoeren naar');
    }

    public function test_publisher_can_create_audited_draft_release_through_the_form(): void
    {
        $publisher = $this->publisher();

        $response = $this->actingAs($publisher)->post(route('content-studio.releases.store'), [
            'name' => 'Madrid basisrelease',
            'description' => 'Eerste gecontroleerde bundel.',
            'target_channel' => ContentReleaseChannel::Staging->value,
        ]);

        $release = ContentRelease::query()->sole();

        $response->assertRedirect(route('content-studio.releases.show', $release));
        $this->assertSame(ContentReleaseStatus::Draft, $release->status);
        $this->assertSame(ContentReleaseChannel::Staging, $release->target_channel);
        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $release->getKey(),
            'action' => 'content.release_created',
        ]);
    }

    public function test_approved_exact_revision_can_be_added_and_becomes_scheduled(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);
        $contentNode = $this->createApprovedContent('un-cafe', 'Un café');

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.items.store', $release), [
                'content_node_id' => $contentNode->getKey(),
                'expected_version' => 1,
            ])
            ->assertRedirect(route('content-studio.releases.show', $release));

        $contentNode->refresh();
        $this->assertSame(ContentStatus::Scheduled, $contentNode->status);
        $this->assertDatabaseHas('content_release_items', [
            'content_release_id' => $release->getKey(),
            'content_node_id' => $contentNode->getKey(),
            'content_revision_id' => $contentNode->revisions()->sole()->getKey(),
            'version' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.release_scheduled',
            'subject_id' => $contentNode->getKey(),
        ]);
    }

    public function test_draft_unapproved_or_stale_content_is_rejected_without_partial_write(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);
        $draft = $this->createContent($this->editor(), 'concept', 'Concept');

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.items.store', $release), [
                'content_node_id' => $draft->getKey(),
                'expected_version' => 1,
            ])
            ->assertSessionHasErrors('content_node_id');

        $approved = $this->createApprovedContent('gracias', 'Gracias');

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.items.store', $release), [
                'content_node_id' => $approved->getKey(),
                'expected_version' => 2,
            ])
            ->assertSessionHasErrors('expected_version');

        $this->assertDatabaseCount('content_release_items', 0);
        $this->assertSame(ContentStatus::Draft, $draft->fresh()->status);
        $this->assertSame(ContentStatus::Approved, $approved->fresh()->status);
    }

    public function test_item_can_be_removed_from_draft_release_and_returns_to_approved(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);
        $contentNode = $this->createApprovedContent('por-favor', 'Por favor');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);
        $item = $release->items()->sole();

        $this->actingAs($publisher)
            ->delete(route('content-studio.releases.items.destroy', [$release, $item]))
            ->assertRedirect(route('content-studio.releases.show', $release));

        $this->assertDatabaseCount('content_release_items', 0);
        $this->assertSame(ContentStatus::Approved, $contentNode->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.release_item_removed',
            'subject_id' => $release->getKey(),
        ]);
    }

    public function test_production_release_requires_explicit_confirmation_and_publishes_atomically(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher, ContentReleaseChannel::Production);
        $first = $this->createApprovedContent('buenos-dias', 'Buenos días');
        $second = $this->createApprovedContent('hasta-luego', 'Hasta luego');
        app(AddContentToRelease::class)->handle($publisher, $release, $first, 1);
        app(AddContentToRelease::class)->handle($publisher, $release, $second, 1);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'publiceren',
                'reason' => 'Eerste productiepublicatie.',
                'acknowledge_warnings' => true,
            ])
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(ContentReleaseStatus::Draft, $release->fresh()->status);
        $this->assertSame(ContentStatus::Scheduled, $first->fresh()->status);
        $this->assertNull($first->fresh()->published_at);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'PUBLICEREN',
                'reason' => 'De waarschuwing is nog niet bevestigd.',
            ])
            ->assertSessionHasErrors('acknowledge_warnings');

        $this->assertSame(ContentReleaseStatus::Draft, $release->fresh()->status);
        $this->assertSame(ContentStatus::Scheduled, $first->fresh()->status);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'PUBLICEREN',
                'reason' => 'Eerste productiepublicatie.',
                'acknowledge_warnings' => true,
            ])
            ->assertRedirect(route('content-studio.releases.show', $release));

        $release->refresh();
        $this->assertSame(ContentReleaseStatus::Published, $release->status);
        $this->assertNotNull($release->published_at);
        $this->assertSame($publisher->getKey(), $release->published_by);

        foreach ([$first, $second] as $contentNode) {
            $contentNode->refresh();
            $this->assertSame(ContentStatus::Published, $contentNode->status);
            $this->assertNotNull($contentNode->published_at);
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.release_published',
            'subject_id' => $release->getKey(),
        ]);
    }

    public function test_preview_release_is_recorded_without_making_content_public(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher, ContentReleaseChannel::Preview);
        $contentNode = $this->createApprovedContent('buenas-noches', 'Buenas noches');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'UITVOEREN',
                'reason' => 'Preview doorlopen.',
            ])
            ->assertRedirect();

        $this->assertSame(ContentReleaseStatus::Published, $release->fresh()->status);
        $this->assertSame(ContentStatus::Approved, $contentNode->fresh()->status);
        $this->assertNull($contentNode->fresh()->published_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.release_validated',
            'subject_id' => $contentNode->getKey(),
        ]);
    }

    public function test_preflight_rechecks_version_and_writes_nothing_when_release_is_stale(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher, ContentReleaseChannel::Production);
        $contentNode = $this->createApprovedContent('adios', 'Adiós');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);
        $contentNode->update(['current_version' => 2]);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'PUBLICEREN',
                'reason' => 'Dit moet blokkeren.',
                'acknowledge_warnings' => true,
            ])
            ->assertSessionHasErrors('preflight');

        $this->assertSame(ContentReleaseStatus::Draft, $release->fresh()->status);
        $this->assertSame(ContentStatus::Scheduled, $contentNode->fresh()->status);
        $this->assertNull($contentNode->fresh()->published_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'content.release_published']);
    }

    public function test_preflight_blocks_legacy_approved_playable_content_without_a_scene_contract(): void
    {
        $publisher = $this->publisher();
        $reviewer = $this->reviewer();
        $release = $this->createRelease($publisher, ContentReleaseChannel::Production);
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $publisher,
            contentType: ContentType::ConversationScenario,
            slug: 'legacy-onvolledig-gesprek',
            locale: 'es-ES',
            title: 'Legacy onvolledig gesprek',
        );
        $revision = $contentNode->revisions()->sole();
        $contentNode->update(['status' => ContentStatus::Approved]);
        $contentNode->reviews()->create([
            'content_revision_id' => $revision->getKey(),
            'version' => 1,
            'action' => ContentReviewAction::Approved,
            'from_status' => ContentStatus::InReview,
            'to_status' => ContentStatus::Approved,
            'note' => 'Legacy goedkeuring vóór de inhoudelijke preflight.',
            'actor_user_id' => $reviewer->getKey(),
            'actor_role' => $reviewer->content_role->value,
            'created_at' => now(),
        ]);
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'PUBLICEREN',
                'reason' => 'Dit onvolledige contract moet blokkeren.',
                'acknowledge_warnings' => true,
            ])
            ->assertSessionHasErrors('preflight');

        $this->assertSame(ContentReleaseStatus::Draft, $release->fresh()->status);
        $this->assertSame(ContentStatus::Scheduled, $contentNode->fresh()->status);
        $this->assertNull($contentNode->fresh()->published_at);
    }

    public function test_future_release_cannot_be_executed_early(): void
    {
        $publisher = $this->publisher();
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Release voor morgen',
            targetChannel: ContentReleaseChannel::Preview,
            desiredPublishAt: now()->addDay()->toDateTimeString(),
        );
        $contentNode = $this->createApprovedContent('que-tal', '¿Qué tal?');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.publish', $release), [
                'confirmation' => 'UITVOEREN',
                'reason' => 'Te vroeg.',
            ])
            ->assertSessionHasErrors('desired_publish_at');

        $this->assertSame(ContentReleaseStatus::Draft, $release->fresh()->status);
        $this->assertSame(ContentStatus::Scheduled, $contentNode->fresh()->status);
    }

    public function test_cancelling_draft_release_returns_all_content_to_approved(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);
        $contentNode = $this->createApprovedContent('de-nada', 'De nada');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);

        $this->actingAs($publisher)
            ->post(route('content-studio.releases.cancel', $release), [
                'cancel_reason' => 'De bundel wordt opnieuw samengesteld.',
            ])
            ->assertRedirect(route('content-studio.releases.show', $release));

        $release->refresh();
        $this->assertSame(ContentReleaseStatus::Cancelled, $release->status);
        $this->assertNotNull($release->cancelled_at);
        $this->assertSame(ContentStatus::Approved, $contentNode->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.release_cancelled',
            'subject_id' => $release->getKey(),
        ]);
    }

    public function test_executed_release_and_its_version_items_are_immutable(): void
    {
        $publisher = $this->publisher();
        $release = $this->createRelease($publisher);
        $contentNode = $this->createApprovedContent('hola', 'Hola');
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);

        $this->actingAs($publisher)->post(route('content-studio.releases.publish', $release), [
            'confirmation' => 'UITVOEREN',
            'reason' => 'Preview is akkoord.',
        ])->assertRedirect();

        $release->refresh();
        $release->name = 'Stille wijziging';

        try {
            $release->save();
            $this->fail('Een uitgevoerde release mocht niet worden gewijzigd.');
        } catch (LogicException) {
            $this->assertDatabaseHas('content_releases', [
                'id' => $release->getKey(),
                'name' => 'Release voor geautomatiseerde test',
            ]);
        }

        $this->expectException(LogicException::class);
        $release->items()->sole()->delete();
    }

    private function createApprovedContent(string $slug, string $title): ContentNode
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, $slug, $title);

        $this->actingAs($editor)
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($this->reviewer())
            ->post(route('content-studio.reviews.decide', $contentNode), [
                'expected_version' => 1,
                'action' => ContentReviewAction::Approved->value,
                'note' => 'Taal en didactiek zijn gecontroleerd.',
            ])
            ->assertRedirect();

        return $contentNode->refresh();
    }

    private function createContent(User $actor, string $slug, string $title): ContentNode
    {
        return app(CreateDraftContent::class)->handle(
            actor: $actor,
            contentType: ContentType::Phrase,
            slug: $slug,
            locale: 'es-ES',
            title: $title,
        );
    }

    private function createRelease(
        User $publisher,
        ContentReleaseChannel $channel = ContentReleaseChannel::Preview,
    ): ContentRelease {
        return app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Release voor geautomatiseerde test',
            targetChannel: $channel,
        );
    }

    private function editor(): User
    {
        return $this->userWithRole(ContentRole::Editor);
    }

    private function reviewer(): User
    {
        return $this->userWithRole(ContentRole::LanguageReviewer);
    }

    private function publisher(): User
    {
        return $this->userWithRole(ContentRole::EditorInChief);
    }

    private function userWithRole(ContentRole $role): User
    {
        return User::factory()->create(['content_role' => $role]);
    }
}
