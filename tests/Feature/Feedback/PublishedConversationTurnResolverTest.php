<?php

namespace Tests\Feature\Feedback;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\CreateDraftContent;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\ContentStudio\PlayableContentTemplates;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentRole;
use App\Enums\ContentType;
use App\Feedback\Exceptions\FeedbackAssessmentFailed;
use App\Feedback\PublishedConversationTurnResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishedConversationTurnResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_exact_production_release_supplies_assessment_context(): void
    {
        $editor = User::factory()->create(['content_role' => ContentRole::Editor]);
        $domainData = app(PlayableContentTemplates::class)->find('panaderia')['domain_data'];
        $domainData['repair']['terms'] = ['¿Puede repetir?'];
        $domainData['mission']['start_step'] = 'greeting';
        $domainData['steps'][0]['id'] = 'greeting';
        $domainData['steps'][0]['npc_line'] = ['es' => 'Hola, buenos días.'];
        $domainData['steps'][0]['prompt'] = 'Begroet Lucía.';
        $domainData['steps'][0]['hint'] = 'Zeg buenos días.';
        $domainData['steps'][0]['options'][0]['requirements'] = [['hola', 'buenos días']];
        $node = app(CreateDraftContent::class)->handle(
            actor: $editor,
            contentType: ContentType::ConversationScenario,
            slug: 'la-espiga-lucia',
            locale: 'es-ES',
            title: 'La Espiga',
            domainData: $domainData,
        );
        app(SubmitContentForReview::class)->handle($editor, $node, 1);
        app(DecideContentReview::class)->handle(
            actor: User::factory()->create(['content_role' => ContentRole::LanguageReviewer]),
            contentNode: $node,
            expectedVersion: 1,
            action: ContentReviewAction::Approved,
            note: 'Context gecontroleerd.',
        );
        $publisher = User::factory()->create(['content_role' => ContentRole::EditorInChief]);
        $release = app(CreateContentRelease::class)->handle(
            actor: $publisher,
            name: 'Productiecontext',
            targetChannel: ContentReleaseChannel::Production,
        );
        app(AddContentToRelease::class)->handle($publisher, $release, $node, 1);
        app(PublishContentRelease::class)->handle(
            actor: $publisher,
            release: $release,
            confirmation: 'PUBLICEREN',
            reason: 'Feedbackcontext beschikbaar maken.',
            acknowledgeWarnings: true,
        );

        $context = app(PublishedConversationTurnResolver::class)->resolve('la-espiga-lucia', 'greeting');

        $this->assertSame('la-espiga-lucia', $context->scenario);
        $this->assertSame(1, $context->contentVersion);
        $this->assertSame('Hola, buenos días.', $context->npcLine);
        $this->assertSame(['¿Puede repetir?'], $context->repairTerms);
    }

    public function test_unknown_step_is_rejected_against_published_content(): void
    {
        $this->expectException(FeedbackAssessmentFailed::class);
        $this->expectExceptionMessage('De gepubliceerde gesprekcontext is tijdelijk niet beschikbaar.');

        app(PublishedConversationTurnResolver::class)->resolve('la-espiga-lucia', 'concept-only-step');
    }
}
