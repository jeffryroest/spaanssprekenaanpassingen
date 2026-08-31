<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\UpdateDraftContent;
use App\ContentStudio\DemoContentInstaller;
use App\ContentStudio\PlayableContentTemplates;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlayableDemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoContentInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_dry_run_reports_the_complete_package_without_writing(): void
    {
        $administrator = $this->administrator();

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_command_installs_every_example_as_an_audited_draft_and_is_idempotent(): void
    {
        $administrator = $this->administrator();

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 5);
        $this->assertDatabaseCount('content_revisions', 5);
        $this->assertDatabaseCount('media_assets', 3);
        $this->assertDatabaseCount('content_media', 3);
        $this->assertDatabaseCount('audit_logs', 8);
        $this->assertSame(5, ContentNode::query()->where('status', ContentStatus::Draft->value)->count());

        foreach (['madrid', 'la-espiga-lucia', 'taxi-diego', 'restaurant-el-reloj', 'consulta-elena'] as $slug) {
            $this->assertDatabaseHas('content_nodes', ['slug' => $slug]);
        }

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 5);
        $this->assertDatabaseCount('content_revisions', 5);
        $this->assertDatabaseCount('media_assets', 3);
        $this->assertDatabaseCount('content_media', 3);
        $this->assertDatabaseCount('audit_logs', 8);
    }

    public function test_installer_safely_upgrades_an_untouched_older_madrid_demo_with_visual_media(): void
    {
        $administrator = $this->administrator();
        $template = app(PlayableContentTemplates::class)->find('madrid-hub');
        app(CreateDraftContent::class)->handle(
            actor: $administrator,
            contentType: $template['content_type'],
            slug: $template['slug'],
            locale: $template['locale'],
            title: $template['title'],
            summary: $template['summary'],
            body: $template['body'],
            metadata: ['demo_content_package' => ['key' => 'madrid-hub', 'version' => '2026.08.1']],
            domainData: $template['domain_data'],
        );

        $this->artisan('game:install-demo-content', ['--actor' => $administrator->email])
            ->assertSuccessful();

        $madrid = ContentNode::query()->where('slug', 'madrid')->firstOrFail();
        $this->assertSame(2, $madrid->current_version);
        $this->assertSame(
            ['map_background'],
            $madrid->revisions()->where('version', 2)->firstOrFail()->mediaAssets()->get()->pluck('pivot.role')->all(),
        );
        $this->assertDatabaseCount('content_nodes', 5);
        $this->assertDatabaseCount('media_assets', 3);
    }

    public function test_installer_never_overwrites_existing_edited_content(): void
    {
        $administrator = $this->administrator();
        app(DemoContentInstaller::class)->install($administrator);
        $contentNode = ContentNode::query()->where('slug', 'la-espiga-lucia')->firstOrFail();
        $revision = $contentNode->revisions()->where('version', 1)->firstOrFail();

        app(UpdateDraftContent::class)->handle(
            actor: $administrator,
            contentNode: $contentNode,
            expectedVersion: 1,
            slug: $contentNode->slug,
            locale: $contentNode->default_locale,
            title: $contentNode->defaultLocalization()->title,
            summary: 'Handmatig aangepaste samenvatting.',
            body: null,
            domainData: data_get($revision->snapshot, 'domain_data', []),
        );

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
        ])->assertFailed();

        $this->assertSame('Handmatig aangepaste samenvatting.', $contentNode->fresh()->defaultLocalization()->summary);
        $this->assertSame(2, $contentNode->fresh()->current_version);
        $this->assertDatabaseCount('content_nodes', 5);
    }

    public function test_administrator_can_explicitly_replace_incomplete_unpublished_review_placeholders(): void
    {
        $administrator = $this->administrator();

        foreach (['madrid-hub', 'panaderia'] as $key) {
            $template = app(PlayableContentTemplates::class)->find($key);
            $contentNode = app(CreateDraftContent::class)->handle(
                actor: $administrator,
                contentType: $template['content_type'],
                slug: $template['slug'],
                locale: $template['locale'],
                title: 'Oude placeholder',
                summary: null,
                body: null,
                domainData: [],
            );
            $revision = $contentNode->revisions->firstWhere('version', 1);

            $contentNode->update(['status' => ContentStatus::InReview]);
            $contentNode->reviews()->create([
                'content_revision_id' => $revision->getKey(),
                'version' => 1,
                'action' => ContentReviewAction::Submitted,
                'from_status' => ContentStatus::Draft,
                'to_status' => ContentStatus::InReview,
                'note' => 'Oude reviewaanvraag',
                'actor_user_id' => $administrator->getKey(),
                'actor_role' => $administrator->content_role->value,
                'created_at' => now(),
            ]);
        }

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--dry-run' => true,
            '--replace-existing' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 2);
        $this->assertDatabaseCount('content_revisions', 2);
        $this->assertDatabaseCount('media_assets', 0);

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--replace-existing' => true,
        ])->assertFailed();

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--replace-existing' => true,
            '--confirm' => 'OVERSCHRIJVEN',
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 5);
        $this->assertDatabaseCount('content_revisions', 7);
        $this->assertDatabaseCount('media_assets', 3);
        $this->assertDatabaseCount('content_media', 3);

        foreach ([
            'madrid' => 'madrid_hub',
            'la-espiga-lucia' => 'panaderia_text_dialogue',
        ] as $slug => $scene) {
            $contentNode = ContentNode::query()->where('slug', $slug)->firstOrFail();
            $currentRevision = $contentNode->revisions()->where('version', 2)->firstOrFail();

            $this->assertSame(ContentStatus::Draft, $contentNode->status);
            $this->assertSame(2, $contentNode->current_version);
            $this->assertSame($scene, data_get($currentRevision->snapshot, 'domain_data.scene'));
            $this->assertSame(
                DemoContentInstaller::PACKAGE_VERSION,
                data_get($contentNode->defaultLocalization()->metadata, 'demo_content_package.version'),
            );
            $this->assertNotEmpty($currentRevision->mediaAssets()->get()->pluck('pivot.role')->all());
            $this->assertSame([], data_get(
                $contentNode->revisions()->where('version', 1)->firstOrFail()->snapshot,
                'domain_data',
            ));
            $this->assertDatabaseHas('content_reviews', [
                'content_node_id' => $contentNode->getKey(),
                'content_revision_id' => $contentNode->revisions()->where('version', 1)->value('id'),
                'action' => ContentReviewAction::Withdrawn->value,
            ]);
            $this->assertDatabaseHas('audit_logs', [
                'subject_id' => $contentNode->getKey(),
                'action' => 'content.demo_placeholder_replaced',
            ]);
        }
    }

    public function test_explicit_replace_still_refuses_real_edited_content(): void
    {
        $administrator = $this->administrator();
        app(DemoContentInstaller::class)->install($administrator);
        $contentNode = ContentNode::query()->where('slug', 'madrid')->firstOrFail();
        $originalVersion = $contentNode->current_version;

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--replace-existing' => true,
            '--confirm' => 'OVERSCHRIJVEN',
        ])->assertSuccessful();

        $this->assertSame($originalVersion, $contentNode->fresh()->current_version);

        $edited = ContentNode::query()->where('slug', 'la-espiga-lucia')->firstOrFail();
        $revision = $edited->revisions()->where('version', $edited->current_version)->firstOrFail();
        app(UpdateDraftContent::class)->handle(
            actor: $administrator,
            contentNode: $edited,
            expectedVersion: $edited->current_version,
            slug: $edited->slug,
            locale: $edited->default_locale,
            title: $edited->defaultLocalization()->title,
            summary: 'Bewuste eigen inhoud.',
            body: null,
            domainData: data_get($revision->snapshot, 'domain_data', []),
        );

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--replace-existing' => true,
            '--confirm' => 'OVERSCHRIJVEN',
        ])->assertFailed();

        $this->assertSame('Bewuste eigen inhoud.', $edited->fresh()->defaultLocalization()->summary);
    }

    public function test_explicit_replace_refuses_an_incomplete_placeholder_already_bound_to_a_release(): void
    {
        $administrator = $this->administrator();
        $template = app(PlayableContentTemplates::class)->find('madrid-hub');
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $administrator,
            contentType: $template['content_type'],
            slug: $template['slug'],
            locale: $template['locale'],
            title: 'Oude placeholder',
            summary: null,
            body: null,
            domainData: [],
        );
        $revision = $contentNode->revisions->firstWhere('version', 1);
        $release = ContentRelease::query()->create([
            'name' => 'Bestaande conceptrelease',
            'target_channel' => ContentReleaseChannel::Production,
            'status' => ContentReleaseStatus::Draft,
            'owner_user_id' => $administrator->getKey(),
        ]);
        $release->items()->create([
            'content_node_id' => $contentNode->getKey(),
            'content_revision_id' => $revision->getKey(),
            'version' => 1,
            'created_by' => $administrator->getKey(),
            'created_at' => now(),
        ]);

        $this->artisan('game:install-demo-content', [
            '--actor' => $administrator->email,
            '--replace-existing' => true,
            '--confirm' => 'OVERSCHRIJVEN',
        ])->assertFailed();

        $this->assertSame(1, $contentNode->fresh()->current_version);
        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_named_seeder_uses_an_existing_configured_administrator(): void
    {
        $administrator = $this->administrator();
        config()->set('content-studio.demo_actor_email', $administrator->email);

        $this->seed(PlayableDemoContentSeeder::class);

        $this->assertDatabaseCount('content_nodes', 5);
    }

    public function test_default_seeder_does_not_create_a_fixed_test_account_or_content_without_configuration(): void
    {
        config()->set('content-studio.demo_actor_email', null);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('content_nodes', 0);
    }

    private function administrator(): User
    {
        return User::factory()->create([
            'content_role' => ContentRole::Administrator,
        ]);
    }
}
