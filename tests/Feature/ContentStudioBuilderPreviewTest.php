<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\UpdateDraftContent;
use App\ContentStudio\PlayableContentInspector;
use App\ContentStudio\PlayableContentTemplates;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ContentStudioBuilderPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_sees_structured_builder_for_playable_template(): void
    {
        $this->actingAs($this->editor())
            ->get(route('content-studio.content.create', ['template' => 'panaderia']))
            ->assertOk()
            ->assertSee('Speelcontentbouwer')
            ->assertSee('data-content-builder-root', false)
            ->assertSee('data-content-builder-source', false)
            ->assertSee('Scène- en personagemedia')
            ->assertSee('Geavanceerde JSON bekijken of herstellen');
    }

    public function test_deep_validation_rejects_missing_route_reference_without_writes(): void
    {
        $template = app(PlayableContentTemplates::class)->find('taxi');
        $domainData = $template['domain_data'];
        $domainData['steps'][0]['options'][0]['next'] = 'turn.does_not_exist';

        $this->actingAs($this->editor())
            ->post(route('content-studio.content.store'), [
                'content_type' => ContentType::ConversationScenario->value,
                'slug' => 'broken-route',
                'locale' => 'es-ES',
                'title' => 'Broken route',
                'domain_data' => json_encode($domainData, JSON_THROW_ON_ERROR),
            ])
            ->assertSessionHasErrors('domain_data');

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('content_revisions', 0);
    }

    public function test_all_demo_contracts_pass_deep_route_inspection(): void
    {
        $inspector = app(PlayableContentInspector::class);

        foreach (app(PlayableContentTemplates::class)->all() as $template) {
            $result = $inspector->inspect($template['content_type'], $template['domain_data']);
            $this->assertSame([], $result['errors'], $template['key'].' bevat routefouten: '.implode(' | ', $result['errors']));
        }
    }

    public function test_authenticated_preview_uses_current_revision_and_writes_no_player_data(): void
    {
        $editor = $this->editor();
        $template = app(PlayableContentTemplates::class)->find('panaderia');
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'preview-conversation',
            locale: 'es-ES',
            title: 'Previewgesprek',
            domainData: $template['domain_data'],
        );

        $previewUrl = URL::temporarySignedRoute(
            'content-studio.content.preview',
            now()->addHour(),
            ['contentNode' => $contentNode, 'version' => 1],
        );
        app(UpdateDraftContent::class)->handle(
            actor: $editor,
            contentNode: $contentNode,
            expectedVersion: 1,
            slug: $contentNode->slug,
            locale: 'es-ES',
            title: 'Nieuwere titel die niet in versie 1 hoort',
            domainData: $template['domain_data'],
        );

        $response = $this->actingAs($editor)
            ->get($previewUrl)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Niet-productiepreview')
            ->assertSee('schrijft geen voortgang')
            ->assertSee('data-preview-level', false)
            ->assertSee('data-preview-submit', false)
            ->assertSee('Lucía Martín')
            ->assertSee('Previewgesprek · revisie 1')
            ->assertDontSee('Nieuwere titel die niet in versie 1 hoort');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);

        $this->assertDatabaseCount('mission_attempts', 0);
        $this->assertDatabaseCount('game_ledger', 0);
        $this->assertDatabaseCount('user_rewards', 0);
    }

    public function test_preview_requires_authentication_and_content_studio_access(): void
    {
        $editor = $this->editor();
        $template = app(PlayableContentTemplates::class)->find('madrid-hub');
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::Region,
            slug: 'preview-world',
            locale: 'es-ES',
            title: 'Previewwereld',
            domainData: $template['domain_data'],
        );

        $previewUrl = URL::temporarySignedRoute(
            'content-studio.content.preview',
            now()->addHour(),
            ['contentNode' => $contentNode, 'version' => 1],
        );

        $this->get($previewUrl)
            ->assertRedirect(route('login'));

        $this->actingAs($editor)
            ->get(route('content-studio.content.preview', $contentNode))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get($previewUrl)
            ->assertForbidden();
    }

    private function editor(): User
    {
        return User::factory()->create(['content_role' => ContentRole::Editor]);
    }
}
