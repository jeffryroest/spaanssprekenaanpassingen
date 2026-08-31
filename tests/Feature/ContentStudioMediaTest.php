<?php

namespace Tests\Feature;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\InspectContentRelease;
use App\ContentStudio\PlayableContentTemplates;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Enums\MediaKind;
use App\Enums\MediaRightsStatus;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class ContentStudioMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_editor_uploads_private_accessible_media_with_rights_metadata(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('content-studio.media.store'), [
                'file' => UploadedFile::fake()->image('madrid.png', 1200, 800),
                'kind' => MediaKind::Image->value,
                'title' => 'Madrid in de ochtend',
                'description' => 'Brede wereldillustratie.',
                'alt_text' => 'Een rustige Madrileense buurt in warm ochtendlicht.',
                'rights_status' => MediaRightsStatus::Owned->value,
                'creator_name' => 'Interne redactie',
            ])
            ->assertRedirect(route('content-studio.media.index'));

        $asset = MediaAsset::query()->sole();
        Storage::disk('local')->assertExists($asset->object_key);
        $this->assertSame(MediaKind::Image, $asset->kind);
        $this->assertTrue($asset->isPublishable());
        $this->assertNotNull($asset->width);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'media.created',
            'subject_id' => $asset->getKey(),
        ]);
    }

    public function test_upload_rejects_kind_mismatch_and_missing_accessibility_text(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('content-studio.media.store'), [
                'file' => UploadedFile::fake()->image('portrait.png'),
                'kind' => MediaKind::Audio->value,
                'title' => 'Verkeerd type',
                'transcript' => 'Niet van toepassing.',
                'rights_status' => MediaRightsStatus::Owned->value,
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($editor)
            ->post(route('content-studio.media.store'), [
                'file' => UploadedFile::fake()->image('portrait.png'),
                'kind' => MediaKind::Image->value,
                'title' => 'Zonder alt-tekst',
                'rights_status' => MediaRightsStatus::Owned->value,
            ])
            ->assertSessionHasErrors('alt_text');

        $this->assertDatabaseCount('media_assets', 0);
    }

    public function test_media_binding_is_frozen_into_content_revision(): void
    {
        $editor = $this->editor();
        $asset = $this->imageAsset($editor);

        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::Region,
            slug: 'media-world',
            locale: 'es-ES',
            title: 'Wereld met medium',
            media: ['map_background' => $asset->getKey()],
        );

        $revision = $contentNode->revisions->first();
        $this->assertSame('map_background', $revision->snapshot['media'][0]['role']);
        $this->assertSame($asset->uuid, $revision->snapshot['media'][0]['asset_uuid']);
        $this->assertDatabaseHas('content_media', [
            'content_node_id' => $contentNode->getKey(),
            'content_revision_id' => $revision->getKey(),
            'media_asset_id' => $asset->getKey(),
            'role' => 'map_background',
        ]);
    }

    public function test_wrong_media_kind_cannot_be_bound_to_role(): void
    {
        $editor = $this->editor();
        $image = $this->imageAsset($editor);

        $this->expectException(ValidationException::class);
        app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::Region,
            slug: 'wrong-media-role',
            locale: 'es-ES',
            title: 'Verkeerd medium',
            media: ['ambient_audio' => $image->getKey()],
        );
    }

    public function test_uploaded_media_metadata_is_immutable(): void
    {
        $asset = $this->imageAsset($this->editor());
        $asset->title = 'Stille wijziging';

        $this->expectException(LogicException::class);
        $asset->save();
    }

    public function test_media_stream_is_private_and_not_indexable(): void
    {
        $editor = $this->editor();
        $asset = $this->imageAsset($editor);

        Auth::logout();
        $this->get(route('content-studio.media.stream', $asset))->assertRedirect(route('login'));

        $this->actingAs($editor)
            ->get(route('content-studio.media.stream', $asset))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_release_preflight_blocks_media_without_publication_rights(): void
    {
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $reviewer = User::factory()->create(['content_role' => ContentRole::LanguageReviewer]);
        $template = app(PlayableContentTemplates::class)->find('madrid-hub');
        $asset = MediaAsset::query()->create([
            'uuid' => fake()->uuid(),
            'kind' => MediaKind::Image,
            'disk' => 'local',
            'object_key' => 'content-media/unlicensed/original.png',
            'original_name' => 'unlicensed.png',
            'mime_type' => 'image/png',
            'byte_size' => 100,
            'checksum_sha256' => str_repeat('a', 64),
            'title' => 'Onbekende bron',
            'alt_text' => 'Beschrijving is aanwezig.',
            'rights_status' => MediaRightsStatus::Unknown,
            'created_by' => $publisher->getKey(),
        ]);
        $contentNode = app(CreateDraftContent::class)->handle(
            actor: $publisher,
            contentType: ContentType::Region,
            slug: 'world-with-unlicensed-media',
            locale: 'es-ES',
            title: 'Wereld met onbekende rechten',
            domainData: $template['domain_data'],
            media: ['map_background' => $asset->getKey()],
        );

        $this->actingAs($publisher)->post(route('content-studio.content.submit-review', $contentNode), [
            'expected_version' => 1,
        ])->assertRedirect();
        $this->actingAs($reviewer)->post(route('content-studio.reviews.decide', $contentNode), [
            'expected_version' => 1,
            'action' => ContentReviewAction::Approved->value,
            'note' => 'Inhoudelijk gecontroleerd; rechten worden door preflight gecontroleerd.',
        ])->assertRedirect();

        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Rechtencontrole',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $contentNode->fresh(), 1);

        $inspection = app(InspectContentRelease::class)->handle($release);
        $this->assertNotEmpty($inspection['blockers']);
        $this->assertStringContainsString(
            'map_background heeft geen aantoonbaar publicatierecht',
            implode(' | ', $inspection['blockers']),
        );
    }

    private function imageAsset(User $editor): MediaAsset
    {
        $this->actingAs($editor)->post(route('content-studio.media.store'), [
            'file' => UploadedFile::fake()->image('scene.png', 800, 600),
            'kind' => MediaKind::Image->value,
            'title' => 'Scène',
            'alt_text' => 'Een toegankelijke scènebeschrijving.',
            'rights_status' => MediaRightsStatus::Owned->value,
        ])->assertRedirect();

        return MediaAsset::query()->latest('id')->firstOrFail();
    }

    private function editor(): User
    {
        return User::factory()->create(['content_role' => ContentRole::Editor]);
    }
}
