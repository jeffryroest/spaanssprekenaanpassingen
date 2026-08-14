<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\RevisionStatus;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class ContentFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_content_is_created_as_draft_with_localization_and_revision(): void
    {
        $actor = $this->editor();

        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $actor,
            contentType: ContentType::Location,
            slug: 'la-panaderia',
            locale: 'es-ES',
            title: 'La panadería',
            summary: 'Eerste locatie in Madrid.',
            metadata: ['city' => 'Madrid'],
            domainData: ['location_type' => 'bakery'],
        );

        $this->assertSame(ContentStatus::Draft, $contentNode->status);
        $this->assertSame(1, $contentNode->current_version);
        $this->assertNull($contentNode->published_at);
        $this->assertSame($actor->getKey(), $contentNode->created_by);
        $this->assertCount(1, $contentNode->localizations);
        $this->assertSame('La panadería', $contentNode->localizations->first()->title);
        $this->assertCount(1, $contentNode->revisions);
        $this->assertSame(RevisionStatus::Draft, $contentNode->revisions->first()->status);
        $this->assertSame(
            'bakery',
            $contentNode->revisions->first()->snapshot['domain_data']['location_type'],
        );
    }

    public function test_slug_must_be_canonical_and_no_data_is_written_on_validation_failure(): void
    {
        $this->expectException(ValidationException::class);

        try {
            app(CreateDraftContent::class)->handle(
                actor: $this->editor(),
                contentType: ContentType::Phrase,
                slug: 'Geen geldige slug',
                locale: 'es-ES',
                title: 'Buenos días',
            );
        } finally {
            $this->assertDatabaseCount('content_nodes', 0);
        }
    }

    public function test_slug_is_unique_within_content_type_but_reusable_across_types(): void
    {
        $actor = $this->editor();
        $action = app(CreateDraftContent::class);

        $action->handle($actor, ContentType::Region, 'madrid', 'nl-NL', 'Madrid');
        $action->handle($actor, ContentType::Location, 'madrid', 'nl-NL', 'Madrid-locatie');

        $this->expectException(QueryException::class);
        $action->handle($actor, ContentType::Region, 'madrid', 'es-ES', 'Madrid');
    }

    public function test_published_content_requires_publication_timestamp(): void
    {
        $this->expectException(LogicException::class);

        ContentNode::query()->create([
            'content_type' => ContentType::Phrase,
            'slug' => 'buenos-dias',
            'status' => ContentStatus::Published,
            'default_locale' => 'es-ES',
            'schema_version' => 1,
            'current_version' => 1,
        ]);
    }

    public function test_revision_cannot_be_updated_or_deleted_separately(): void
    {
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $this->editor(),
            contentType: ContentType::Phrase,
            slug: 'por-favor',
            locale: 'es-ES',
            title: 'Por favor',
        );

        $revision = $contentNode->revisions->first();
        $revision->change_summary = 'Stille wijziging';

        try {
            $revision->save();
            $this->fail('Een bestaande revisie mocht niet worden bijgewerkt.');
        } catch (LogicException) {
            $this->assertDatabaseHas('content_revisions', [
                'id' => $revision->getKey(),
                'change_summary' => 'Eerste conceptversie',
            ]);
        }

        $this->expectException(LogicException::class);
        $revision->fresh()->delete();
    }

    public function test_actor_without_edit_permission_cannot_create_content(): void
    {
        $this->expectException(AuthorizationException::class);

        try {
            app(CreateDraftContent::class)->handle(
                actor: User::factory()->create(['content_role' => ContentRole::Auditor]),
                contentType: ContentType::Phrase,
                slug: 'hasta-luego',
                locale: 'es-ES',
                title: 'Hasta luego',
            );
        } finally {
            $this->assertDatabaseCount('content_nodes', 0);
        }
    }

    private function editor(): User
    {
        return User::factory()->create(['content_role' => ContentRole::Editor]);
    }
}
