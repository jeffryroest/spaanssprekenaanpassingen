<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\ContentStudio\DemoContentInstaller;
use App\ContentStudio\PlayableContentTemplates;
use App\ContentStudio\RuntimeReadiness;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReleaseStatus;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentStatus;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrialWeekContentReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_dry_run_checks_the_complete_package_without_writing(): void
    {
        [$publisher, $reviewer] = $this->actors();

        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $reviewer->email,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('content_reviews', 0);
        $this->assertDatabaseCount('content_releases', 0);
        $this->assertDatabaseCount('media_assets', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_explicit_command_installs_reviews_and_publishes_the_complete_trial_week(): void
    {
        [$publisher, $reviewer] = $this->actors();

        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $reviewer->email,
            '--confirm' => 'PUBLICEREN',
        ])->assertSuccessful();

        $this->assertDatabaseCount('content_nodes', 7);
        $this->assertDatabaseCount('media_assets', 11);
        $this->assertDatabaseCount('content_media', 13);
        $this->assertDatabaseCount('content_reviews', 14);
        $this->assertSame(7, ContentNode::query()->where('status', ContentStatus::Published->value)->count());
        $this->assertDatabaseHas('content_releases', [
            'target_channel' => 'production',
            'status' => ContentReleaseStatus::Published->value,
            'published_by' => $publisher->getKey(),
        ]);
        $this->assertCount(8, app(RuntimeReadiness::class)->items());
        $this->assertTrue(collect(app(RuntimeReadiness::class)->items())->every(
            fn (array $item): bool => $item['ready'],
        ));

        $releaseCount = ContentRelease::query()->count();
        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $reviewer->email,
            '--confirm' => 'PUBLICEREN',
        ])->assertSuccessful();
        $this->assertSame($releaseCount, ContentRelease::query()->count());
    }

    public function test_publication_requires_an_independent_reviewer_and_explicit_confirmation(): void
    {
        [$publisher] = $this->actors();

        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $publisher->email,
            '--dry-run' => true,
        ])->assertFailed();

        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $reviewer->email,
        ])->assertFailed();

        $this->assertDatabaseCount('content_nodes', 0);
        $this->assertDatabaseCount('content_releases', 0);
    }

    public function test_an_untouched_published_demo_without_media_is_upgraded_atomically(): void
    {
        [$publisher, $reviewer] = $this->actors();
        $template = app(PlayableContentTemplates::class)->find('taxi');
        $taxi = app(CreateDraftContent::class)->handle(
            actor: $publisher,
            contentType: $template['content_type'],
            slug: $template['slug'],
            locale: $template['locale'],
            title: $template['title'],
            summary: $template['summary'],
            body: $template['body'],
            metadata: ['demo_content_package' => ['key' => 'taxi', 'version' => '2026.09.1']],
            domainData: $template['domain_data'],
        );
        $taxi = app(SubmitContentForReview::class)->handle($publisher, $taxi, 1, 'Eerste pakketcontrole.');
        $taxi = app(DecideContentReview::class)->handle(
            $reviewer,
            $taxi,
            1,
            ContentReviewAction::Approved,
            'Onafhankelijk gecontroleerd.',
        );
        $release = app(CreateContentRelease::class)->handle(
            $publisher,
            'Oud taxipakket',
            ContentReleaseChannel::Production,
        );
        $release = app(AddContentToRelease::class)->handle($publisher, $release, $taxi, 1);
        app(PublishContentRelease::class)->handle(
            $publisher,
            $release,
            'PUBLICEREN',
            'Oude pakketversie publiceren.',
            true,
        );

        $this->artisan('game:publish-trial-week-content', [
            '--actor' => $publisher->email,
            '--reviewer' => $reviewer->email,
            '--confirm' => 'PUBLICEREN',
        ])->assertSuccessful();

        $taxi = $taxi->fresh()->load('revisions.mediaAssets');
        $this->assertSame(ContentStatus::Published, $taxi->status);
        $this->assertSame(2, $taxi->current_version);
        $this->assertSame(
            DemoContentInstaller::PACKAGE_VERSION,
            data_get($taxi->defaultLocalization()->metadata, 'demo_content_package.version'),
        );
        $this->assertSame(
            ['scene_background', 'npc_expression_sheet'],
            $taxi->revisions->firstWhere('version', 2)->mediaAssets->pluck('pivot.role')->all(),
        );
        $this->assertDatabaseHas('audit_logs', [
            'subject_id' => $taxi->getKey(),
            'action' => 'content.published_demo_media_upgrade_prepared',
        ]);
        $this->assertDatabaseCount('content_releases', 2);
    }

    /** @return array{User, User} */
    private function actors(): array
    {
        return [
            User::factory()->create(['content_role' => ContentRole::Administrator]),
            User::factory()->create(['content_role' => ContentRole::LanguageReviewer]),
        ];
    }
}
