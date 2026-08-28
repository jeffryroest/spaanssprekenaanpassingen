<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\AuditLog;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ContentStudioContentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_content_catalog(): void
    {
        $this->get(route('content-studio.content.index'))
            ->assertRedirect(route('login'));
    }

    public function test_auditor_can_read_catalog_and_detail_but_cannot_write(): void
    {
        $contentNode = $this->createContent($this->editor(), 'la-panaderia', 'La panadería');
        $auditor = $this->userWithRole(ContentRole::Auditor);

        $this->actingAs($auditor)
            ->get(route('content-studio.content.index'))
            ->assertOk()
            ->assertSee('La panadería')
            ->assertDontSee('Nieuw concept');

        $this->actingAs($auditor)
            ->get(route('content-studio.content.show', $contentNode))
            ->assertOk()
            ->assertSee('La panadería');

        $this->actingAs($auditor)
            ->get(route('content-studio.content.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->put(route('content-studio.content.update', $contentNode), $this->updatePayload($contentNode))
            ->assertForbidden();
    }

    public function test_editor_can_create_draft_through_form_with_revision_and_audit(): void
    {
        $editor = $this->editor();

        $response = $this->actingAs($editor)->post(route('content-studio.content.store'), [
            'content_type' => ContentType::Location->value,
            'slug' => 'la-panaderia',
            'locale' => 'es-ES',
            'title' => 'La panadería',
            'summary' => 'Een bakkerij in Madrid.',
            'body' => 'Hier oefent de speler een bestelling.',
        ]);

        $contentNode = ContentNode::query()->with(['localizations', 'revisions'])->sole();

        $response->assertRedirect(route('content-studio.content.show', $contentNode));
        $this->assertSame(ContentStatus::Draft, $contentNode->status);
        $this->assertSame('La panadería', $contentNode->defaultLocalization()?->title);
        $this->assertCount(1, $contentNode->revisions);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $editor->getKey(),
            'action' => 'content.created',
            'subject_id' => $contentNode->getKey(),
        ]);
        $this->assertNotNull(AuditLog::query()->sole()->request_id);
    }

    public function test_editor_can_start_from_a_playable_template_without_publishing_it(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->get(route('content-studio.content.create', ['template' => 'madrid-hub']))
            ->assertOk()
            ->assertSee('Start met speelbare content')
            ->assertSee('madrid_hub')
            ->assertSee('value="madrid"', false)
            ->assertSee('Alleen concept');

        $this->assertDatabaseCount('content_nodes', 0);
    }

    public function test_restaurant_template_prefills_carmens_private_dialogue_as_draft_only(): void
    {
        $this->actingAs($this->editor())
            ->get(route('content-studio.content.create', ['template' => 'restaurant']))
            ->assertOk()
            ->assertSee('restaurant_text_dialogue')
            ->assertSee('npc.carmen.santos')
            ->assertSee('value="restaurant-el-reloj"', false)
            ->assertSee('Alleen concept');

        $this->assertDatabaseCount('content_nodes', 0);
    }

    public function test_playable_domain_data_is_validated_and_saved_in_the_revision(): void
    {
        $editor = $this->editor();
        $domainData = file_get_contents(base_path('content/examples/madrid-hub-domain-data.json'));
        $this->assertIsString($domainData);

        $this->actingAs($editor)->post(route('content-studio.content.store'), [
            'content_type' => ContentType::Region->value,
            'slug' => 'madrid',
            'locale' => 'es-ES',
            'title' => 'Madrid',
            'summary' => 'Speelbare wereld',
            'body' => null,
            'domain_data' => $domainData,
        ])->assertRedirect();

        $revision = ContentNode::query()->with('revisions')->sole()->revisions->sole();
        $this->assertSame('madrid_hub', $revision->snapshot['domain_data']['scene']);
        $this->assertCount(4, $revision->snapshot['domain_data']['hotspots']);
        $this->assertDatabaseHas('content_nodes', [
            'slug' => 'madrid',
            'status' => ContentStatus::Draft->value,
        ]);
    }

    public function test_invalid_playable_domain_data_writes_nothing(): void
    {
        $this->actingAs($this->editor())
            ->post(route('content-studio.content.store'), [
                'content_type' => ContentType::Region->value,
                'slug' => 'madrid',
                'locale' => 'es-ES',
                'title' => 'Madrid',
                'domain_data' => '{"schema_version":"1.0.0","scene":"madrid_hub","hotspots":[]}',
            ])
            ->assertSessionHasErrors('domain_data');

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('content_revisions', 0);
    }

    public function test_invalid_content_form_writes_nothing(): void
    {
        $this->actingAs($this->editor())
            ->post(route('content-studio.content.store'), [
                'content_type' => ContentType::Phrase->value,
                'slug' => 'Geen geldige slug',
                'locale' => 'spaans',
                'title' => '',
            ])
            ->assertSessionHasErrors(['slug', 'locale', 'title']);

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_editor_cannot_change_another_editors_content_but_editor_in_chief_can(): void
    {
        $owner = $this->editor();
        $contentNode = $this->createContent($owner, 'un-cafe', 'Un café');
        $otherEditor = $this->editor();

        $this->actingAs($otherEditor)
            ->put(route('content-studio.content.update', $contentNode), $this->updatePayload($contentNode))
            ->assertForbidden();

        $this->actingAs($otherEditor)
            ->delete(route('content-studio.content.destroy', $contentNode), [
                'expected_version' => 1,
                'reason' => 'Niet mijn concept',
            ])
            ->assertForbidden();

        $editorInChief = $this->userWithRole(ContentRole::EditorInChief);
        $this->actingAs($editorInChief)
            ->put(
                route('content-studio.content.update', $contentNode),
                $this->updatePayload($contentNode, 'Un café, por favor'),
            )
            ->assertRedirect(route('content-studio.content.show', $contentNode));

        $contentNode->refresh()->load('localizations');
        $this->assertSame('Un café, por favor', $contentNode->defaultLocalization()?->title);
    }

    public function test_catalog_search_and_filters_limit_results(): void
    {
        $editor = $this->editor();
        $this->createContent($editor, 'la-panaderia', 'La panadería', ContentType::Location);
        $this->createContent($editor, 'buenos-dias', 'Buenos días', ContentType::Phrase);

        $this->actingAs($editor)
            ->get(route('content-studio.content.index', [
                'search' => 'panadería',
                'content_type' => ContentType::Location->value,
                'status' => ContentStatus::Draft->value,
            ]))
            ->assertOk()
            ->assertSee('La panadería')
            ->assertDontSee('Buenos días');
    }

    public function test_edit_creates_new_revision_without_changing_first_snapshot(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'por-favor', 'Por favor');

        $this->actingAs($editor)
            ->put(route('content-studio.content.update', $contentNode), [
                'expected_version' => 1,
                'slug' => 'por-favor',
                'locale' => 'es-ES',
                'title' => 'Por favor, señor',
                'summary' => 'Beleefde aanspreekvorm.',
                'body' => 'Gebruik dit in een formele situatie.',
            ])
            ->assertRedirect(route('content-studio.content.show', $contentNode));

        $contentNode->refresh()->load(['localizations', 'revisions']);

        $this->assertSame(2, $contentNode->current_version);
        $this->assertCount(2, $contentNode->revisions);
        $this->assertSame('Por favor', $contentNode->revisions[0]->snapshot['localizations'][0]['title']);
        $this->assertSame('Por favor, señor', $contentNode->revisions[1]->snapshot['localizations'][0]['title']);
        $this->assertSame('Por favor, señor', $contentNode->defaultLocalization()?->title);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.updated',
            'subject_id' => $contentNode->getKey(),
        ]);
    }

    public function test_stale_edit_is_rejected_without_partial_write(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'gracias', 'Gracias');

        $this->actingAs($editor)
            ->put(route('content-studio.content.update', $contentNode), $this->updatePayload($contentNode, 'Muchas gracias'))
            ->assertRedirect();

        $this->actingAs($editor)
            ->put(route('content-studio.content.update', $contentNode), $this->updatePayload($contentNode, 'Gracias de nuevo'))
            ->assertSessionHasErrors('expected_version');

        $contentNode->refresh()->load(['localizations', 'revisions']);
        $this->assertSame(2, $contentNode->current_version);
        $this->assertCount(2, $contentNode->revisions);
        $this->assertSame('Muchas gracias', $contentNode->defaultLocalization()?->title);
    }

    public function test_editor_archives_instead_of_deleting_content_history(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'hasta-luego', 'Hasta luego');

        $this->actingAs($editor)
            ->delete(route('content-studio.content.destroy', $contentNode), [
                'expected_version' => 1,
                'reason' => 'Dubbel concept',
            ])
            ->assertRedirect(route('content-studio.content.show', $contentNode));

        $contentNode->refresh()->load('revisions');
        $this->assertSame(ContentStatus::Archived, $contentNode->status);
        $this->assertSame(1, $contentNode->current_version);
        $this->assertCount(1, $contentNode->revisions);
        $this->assertNotNull(ContentNode::query()->find($contentNode->getKey()));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.archived',
            'subject_id' => $contentNode->getKey(),
        ]);

        $this->actingAs($editor)
            ->get(route('content-studio.content.edit', $contentNode))
            ->assertStatus(409);
    }

    public function test_published_content_cannot_be_edited_in_this_phase(): void
    {
        $editor = $this->editor();
        $contentNode = $this->createContent($editor, 'buenas-noches', 'Buenas noches');
        $contentNode->update([
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($editor)
            ->get(route('content-studio.content.edit', $contentNode))
            ->assertStatus(409);

        $this->actingAs($editor)
            ->put(
                route('content-studio.content.update', $contentNode),
                $this->updatePayload($contentNode, 'Gewijzigde publicatie'),
            )
            ->assertSessionHasErrors('status');

        $contentNode->refresh()->load(['localizations', 'revisions']);
        $this->assertSame(1, $contentNode->current_version);
        $this->assertCount(1, $contentNode->revisions);
        $this->assertSame('Buenas noches', $contentNode->defaultLocalization()?->title);
    }

    public function test_audit_log_cannot_be_changed_or_deleted(): void
    {
        $this->createContent($this->editor(), 'adios', 'Adiós');
        $auditLog = AuditLog::query()->firstOrFail();
        $auditLog->action = 'content.hidden';

        try {
            $auditLog->save();
            $this->fail('Een auditregel mocht niet worden gewijzigd.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', [
                'id' => $auditLog->getKey(),
                'action' => 'content.created',
            ]);
        }

        $this->expectException(LogicException::class);
        $auditLog->fresh()->delete();
    }

    private function createContent(
        User $actor,
        string $slug,
        string $title,
        ContentType $contentType = ContentType::Location,
    ): ContentNode {
        return app(CreateDraftContent::class)->handle(
            actor: $actor,
            contentType: $contentType,
            slug: $slug,
            locale: 'es-ES',
            title: $title,
        );
    }

    /** @return array<string, mixed> */
    private function updatePayload(ContentNode $contentNode, string $title = 'Bijgewerkte titel'): array
    {
        return [
            'expected_version' => $contentNode->current_version,
            'slug' => $contentNode->slug,
            'locale' => $contentNode->default_locale,
            'title' => $title,
            'summary' => null,
            'body' => null,
        ];
    }

    private function editor(): User
    {
        return $this->userWithRole(ContentRole::Editor);
    }

    private function userWithRole(ContentRole $role): User
    {
        return User::factory()->create(['content_role' => $role]);
    }
}
