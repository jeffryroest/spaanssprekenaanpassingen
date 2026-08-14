<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\ContentReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ContentStudioReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_reviewers_can_open_the_review_queue(): void
    {
        $contentNode = $this->createContent($this->editor(), 'la-panaderia', 'La panadería');
        $this->submit($contentNode, $contentNode->creator);

        $this->actingAs($this->editor())
            ->get(route('content-studio.reviews.index'))
            ->assertForbidden();

        $this->actingAs($this->reviewer())
            ->get(route('content-studio.reviews.index'))
            ->assertOk()
            ->assertSee('La panadería')
            ->assertSee('Reviewwachtrij');
    }

    public function test_editor_can_submit_own_current_draft_for_review(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'buenos-dias', 'Buenos días');

        $this->actingAs($editor)
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => 1,
                'note' => 'Controleer de regionale neutraliteit.',
            ])
            ->assertRedirect(route('content-studio.content.show', $contentNode));

        $contentNode->refresh();

        $this->assertSame(ContentStatus::InReview, $contentNode->status);
        $this->assertDatabaseHas('content_reviews', [
            'content_node_id' => $contentNode->getKey(),
            'version' => 1,
            'action' => ContentReviewAction::Submitted->value,
            'actor_user_id' => $editor->getKey(),
            'note' => 'Controleer de regionale neutraliteit.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $contentNode->getKey(),
            'action' => 'content.review_submitted',
        ]);
    }

    public function test_submission_rejects_stale_or_unauthorized_requests_without_partial_write(): void
    {
        $owner = $this->editor();
        $contentNode = $this->createContent($owner, 'gracias', 'Gracias');

        $this->actingAs($owner)
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => 2,
            ])
            ->assertSessionHasErrors('expected_version');

        $this->actingAs($this->editor())
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => 1,
            ])
            ->assertForbidden();

        $this->assertSame(ContentStatus::Draft, $contentNode->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 0);
    }

    public function test_content_is_locked_for_editing_while_in_review(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'por-favor', 'Por favor');
        $this->submit($contentNode, $editor);

        $this->actingAs($editor)
            ->put(route('content-studio.content.update', $contentNode), [
                'expected_version' => 1,
                'slug' => 'por-favor',
                'locale' => 'es-ES',
                'title' => 'Por favor, señor',
                'summary' => null,
                'body' => null,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(ContentStatus::InReview, $contentNode->fresh()->status);
        $this->assertSame(1, $contentNode->fresh()->current_version);
    }

    public function test_language_reviewer_can_approve_another_users_revision(): void
    {
        $editor = $this->editor();
        $reviewer = $this->reviewer();
        $contentNode = $this->createContent($editor, 'un-cafe', 'Un café');
        $this->submit($contentNode, $editor);

        $this->actingAs($reviewer)
            ->post(route('content-studio.reviews.decide', $contentNode), [
                'expected_version' => 1,
                'action' => ContentReviewAction::Approved->value,
                'note' => 'Taal en context zijn gecontroleerd en correct.',
            ])
            ->assertRedirect(route('content-studio.content.show', $contentNode));

        $contentNode->refresh();

        $this->assertSame(ContentStatus::Approved, $contentNode->status);
        $this->assertDatabaseHas('content_reviews', [
            'content_node_id' => $contentNode->getKey(),
            'content_revision_id' => $contentNode->revisions()->sole()->getKey(),
            'version' => 1,
            'action' => ContentReviewAction::Approved->value,
            'actor_user_id' => $reviewer->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $contentNode->getKey(),
            'action' => 'content.review_approved',
        ]);
    }

    public function test_four_eyes_rule_blocks_reviewing_own_revision(): void
    {
        $editorInChief = $this->userWithRole(ContentRole::EditorInChief);
        $contentNode = $this->createContent($editorInChief, 'hasta-luego', 'Hasta luego');
        $this->submit($contentNode, $editorInChief);

        $this->actingAs($editorInChief)
            ->post(route('content-studio.reviews.decide', $contentNode), [
                'expected_version' => 1,
                'action' => ContentReviewAction::Approved->value,
                'note' => 'Eigen controle mag niet tellen.',
            ])
            ->assertSessionHasErrors('reviewer');

        $this->assertSame(ContentStatus::InReview, $contentNode->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 1);
    }

    public function test_reviewer_can_request_changes_and_editor_can_create_a_new_revision(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'buenas-noches', 'Buenas noches');
        $this->submit($contentNode, $editor);

        $this->actingAs($this->reviewer())
            ->post(route('content-studio.reviews.decide', $contentNode), [
                'expected_version' => 1,
                'action' => ContentReviewAction::ChangesRequested->value,
                'note' => 'Voeg een korte gebruikscontext toe.',
            ])
            ->assertRedirect();

        $this->assertSame(ContentStatus::ChangesRequested, $contentNode->fresh()->status);

        $this->actingAs($editor)
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => 1,
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($editor)
            ->put(route('content-studio.content.update', $contentNode), [
                'expected_version' => 1,
                'slug' => 'buenas-noches',
                'locale' => 'es-ES',
                'title' => 'Buenas noches',
                'summary' => 'Begroeting bij vertrek in de avond.',
                'body' => null,
            ])
            ->assertRedirect();

        $contentNode->refresh();
        $this->assertSame(ContentStatus::Draft, $contentNode->status);
        $this->assertSame(2, $contentNode->current_version);
        $this->assertDatabaseHas('content_reviews', [
            'version' => 1,
            'action' => ContentReviewAction::ChangesRequested->value,
            'note' => 'Voeg een korte gebruikscontext toe.',
        ]);
    }

    public function test_unprivileged_or_stale_review_decisions_write_nothing(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'adios', 'Adiós');
        $this->submit($contentNode, $editor);

        $payload = [
            'expected_version' => 1,
            'action' => ContentReviewAction::Approved->value,
            'note' => 'Niet bevoegd.',
        ];

        $this->actingAs($this->userWithRole(ContentRole::Auditor))
            ->post(route('content-studio.reviews.decide', $contentNode), $payload)
            ->assertForbidden();

        $this->actingAs($this->reviewer())
            ->post(route('content-studio.reviews.decide', $contentNode), array_replace($payload, ['expected_version' => 2]))
            ->assertSessionHasErrors('expected_version');

        $this->assertSame(ContentStatus::InReview, $contentNode->fresh()->status);
        $this->assertDatabaseCount('content_reviews', 1);
    }

    public function test_review_history_cannot_be_changed_or_deleted(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'que-tal', '¿Qué tal?');
        $this->submit($contentNode, $editor);
        $review = ContentReview::query()->sole();
        $review->note = 'Gewijzigd';

        try {
            $review->save();
            $this->fail('Een reviewgebeurtenis mocht niet worden gewijzigd.');
        } catch (LogicException) {
            $this->assertDatabaseHas('content_reviews', [
                'id' => $review->getKey(),
                'note' => null,
            ]);
        }

        $this->expectException(LogicException::class);
        $review->fresh()->delete();
    }

    private function submit(ContentNode $contentNode, User $actor): void
    {
        $this->actingAs($actor)
            ->post(route('content-studio.content.submit-review', $contentNode), [
                'expected_version' => $contentNode->current_version,
            ])
            ->assertRedirect();

        $contentNode->refresh();
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

    private function editor(): User
    {
        return $this->userWithRole(ContentRole::Editor);
    }

    private function reviewer(): User
    {
        return $this->userWithRole(ContentRole::LanguageReviewer);
    }

    private function userWithRole(ContentRole $role): User
    {
        return User::factory()->create(['content_role' => $role]);
    }
}
