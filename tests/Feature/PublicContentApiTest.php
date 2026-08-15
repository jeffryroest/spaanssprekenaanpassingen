<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\ContentApi\PublicApiResponder;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_the_exact_published_revision_snapshot(): void
    {
        $mission = $this->publishProduction(
            contentType: ContentType::Mission,
            slug: 'bestel-een-cafe',
            title: 'Bestel een café',
            summary: 'Oefen een bestelling in Madrid.',
            body: 'Volg de stappen en rond het gesprek af.',
            domainData: [
                'difficulty' => 'starter',
                'objectives' => ['begroeten', 'bestellen'],
            ],
        );

        $mission->localizations()->firstOrFail()->update([
            'title' => 'Niet-gereviseerde wijziging',
        ]);

        $response = $this->getJson('/api/v1/missions/bestel-een-cafe');

        $response
            ->assertOk()
            ->assertHeader('X-Content-API-Version', PublicApiResponder::API_VERSION)
            ->assertHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=300')
            ->assertJsonPath('schema_version', PublicApiResponder::API_VERSION)
            ->assertJsonPath('data.type', ContentType::Mission->value)
            ->assertJsonPath('data.slug', 'bestel-een-cafe')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.locale', 'es-ES')
            ->assertJsonPath('data.content.title', 'Bestel een café')
            ->assertJsonPath('data.content.summary', 'Oefen een bestelling in Madrid.')
            ->assertJsonPath('data.content.domain_data.difficulty', 'starter')
            ->assertJsonPath('data.content.domain_data.objectives.1', 'bestellen');

        $payload = $response->json('data');
        $this->assertArrayNotHasKey('created_by', $payload);
        $this->assertArrayNotHasKey('updated_by', $payload);
        $this->assertArrayNotHasKey('reviews', $payload);
    }

    public function test_only_exact_current_production_publications_are_listed(): void
    {
        $published = $this->publishProduction(ContentType::Mission, 'zichtbare-missie', 'Zichtbare missie');
        $this->createDraft(ContentType::Mission, 'concept-missie', 'Conceptmissie');
        $this->createApproved(ContentType::Mission, 'goedgekeurde-missie', 'Goedgekeurde missie');
        $this->publishPreview(ContentType::Mission, 'preview-missie', 'Previewmissie');

        $manual = $this->createDraft(ContentType::Mission, 'handmatig-gepubliceerd', 'Handmatig gepubliceerd');
        $manual->update([
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $published->update(['current_version' => 2]);

        $this->getJson('/api/v1/missions')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_each_endpoint_is_isolated_to_its_public_content_type(): void
    {
        $this->publishProduction(ContentType::Region, 'madrid', 'Madrid');
        $this->publishProduction(ContentType::Location, 'panaderia-luz', 'Panadería Luz');
        $this->publishProduction(ContentType::Mission, 'koop-brood', 'Koop brood');
        $this->publishProduction(ContentType::ConversationScenario, 'bij-de-bakker', 'Bij de bakker');

        $this->getJson('/api/v1/worlds')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'madrid');
        $this->getJson('/api/v1/locations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'panaderia-luz');
        $this->getJson('/api/v1/missions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'koop-brood');
        $this->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'bij-de-bakker');

        $this->getJson('/api/v1/worlds/koop-brood')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'published_content_not_found');
    }

    public function test_locale_falls_back_to_the_published_default_locale(): void
    {
        $this->publishProduction(ContentType::Location, 'plaza-mayor', 'Plaza Mayor');

        $this->getJson('/api/v1/locations/plaza-mayor?locale=nl-NL')
            ->assertOk()
            ->assertJsonPath('data.requested_locale', 'nl-NL')
            ->assertJsonPath('data.locale', 'es-ES')
            ->assertJsonPath('data.available_locales.0', 'es-ES')
            ->assertJsonPath('data.content.title', 'Plaza Mayor');
    }

    public function test_detail_response_supports_conditional_gets(): void
    {
        $this->publishProduction(ContentType::ConversationScenario, 'hola-madrid', 'Hola Madrid');

        $first = $this->getJson('/api/v1/conversations/hola-madrid')
            ->assertOk()
            ->assertHeader('Last-Modified');
        $etag = $first->headers->get('ETag');

        $this->assertNotNull($etag);

        $this->withHeader('If-None-Match', $etag)
            ->get('/api/v1/conversations/hola-madrid')
            ->assertStatus(304)
            ->assertHeader('ETag', $etag)
            ->assertContent('');
    }

    public function test_invalid_queries_and_unknown_content_have_stable_errors(): void
    {
        $this->getJson('/api/v1/missions/onbekend')
            ->assertNotFound()
            ->assertJsonPath('schema_version', PublicApiResponder::API_VERSION)
            ->assertJsonPath('error.code', 'published_content_not_found');

        $this->getJson('/api/v1/missions?locale=spanish')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['locale']]]);

        $this->getJson('/api/v1/missions?per_page=100')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['per_page']]]);

        $this->getJson('/api/v1/onbekend')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'api_route_not_found');
    }

    public function test_collection_contract_contains_pagination_and_absolute_links(): void
    {
        $this->publishProduction(ContentType::Region, 'andalusie', 'Andalusië');

        $this->getJson('/api/v1/worlds?per_page=10')
            ->assertOk()
            ->assertJsonPath('schema_version', PublicApiResponder::API_VERSION)
            ->assertJsonPath('meta.resource', 'worlds')
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.links.self', url('/api/v1/worlds/andalusie'))
            ->assertJsonPath('links.first', url('/api/v1/worlds?per_page=10&page=1'));
    }

    private function publishProduction(
        ContentType $contentType,
        string $slug,
        string $title,
        ?string $summary = null,
        ?string $body = null,
        array $domainData = [],
    ): ContentNode {
        $publisher = $this->userWithRole(ContentRole::EditorInChief);
        $contentNode = $this->createApproved(
            $contentType,
            $slug,
            $title,
            $summary,
            $body,
            $domainData,
        );
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: "Productierelease {$slug}",
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Geautomatiseerde API-contracttest.',
            acknowledgeWarnings: true,
        );

        return $contentNode->refresh();
    }

    private function publishPreview(ContentType $contentType, string $slug, string $title): ContentNode
    {
        $publisher = $this->userWithRole(ContentRole::EditorInChief);
        $contentNode = $this->createApproved($contentType, $slug, $title);
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: "Previewrelease {$slug}",
            targetChannel: ContentReleaseChannel::Preview,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'UITVOEREN',
            reason: 'Geautomatiseerde previewtest.',
        );

        return $contentNode->refresh();
    }

    private function createApproved(
        ContentType $contentType,
        string $slug,
        string $title,
        ?string $summary = null,
        ?string $body = null,
        array $domainData = [],
    ): ContentNode {
        $editor = $this->userWithRole(ContentRole::Editor);
        $contentNode = $this->createDraft(
            $contentType,
            $slug,
            $title,
            $summary,
            $body,
            $domainData,
            $editor,
        );
        app(SubmitContentForReview::class)->handle($editor, $contentNode, 1);
        app(DecideContentReview::class)->handle(
            actor: $this->userWithRole(ContentRole::LanguageReviewer),
            contentNode: $contentNode,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Taal en runtime-inhoud zijn gecontroleerd.',
        );

        return $contentNode->refresh();
    }

    private function createDraft(
        ContentType $contentType,
        string $slug,
        string $title,
        ?string $summary = null,
        ?string $body = null,
        array $domainData = [],
        ?User $editor = null,
    ): ContentNode {
        return app(CreateDraftContent::class)->handle(
            actor: $editor ?? $this->userWithRole(ContentRole::Editor),
            contentType: $contentType,
            slug: $slug,
            locale: 'es-ES',
            title: $title,
            summary: $summary,
            body: $body,
            metadata: ['audience' => 'starter'],
            domainData: $domainData,
        );
    }

    private function userWithRole(ContentRole $role): User
    {
        return User::factory()->create(['content_role' => $role]);
    }
}
