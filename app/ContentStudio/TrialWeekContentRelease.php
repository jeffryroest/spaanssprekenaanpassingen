<?php

namespace App\ContentStudio;

use App\Actions\ContentStudio\AddContentToRelease;
use App\Actions\ContentStudio\CreateContentRelease;
use App\Actions\ContentStudio\DecideContentReview;
use App\Actions\ContentStudio\PublishContentRelease;
use App\Actions\ContentStudio\SubmitContentForReview;
use App\Enums\ContentPermission;
use App\Enums\ContentReleaseChannel;
use App\Enums\ContentReviewAction;
use App\Enums\ContentStatus;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class TrialWeekContentRelease
{
    public function __construct(
        private readonly DemoContentInstaller $installer,
        private readonly PlayableContentTemplates $templates,
        private readonly PreparePublishedDemoMediaUpgrade $preparePublishedDemoMediaUpgrade,
        private readonly SubmitContentForReview $submitContentForReview,
        private readonly DecideContentReview $decideContentReview,
        private readonly CreateContentRelease $createContentRelease,
        private readonly AddContentToRelease $addContentToRelease,
        private readonly PublishContentRelease $publishContentRelease,
    ) {}

    /**
     * @return array{package_version: string, ready: bool, items: list<array{key: string, slug: string, status: string, action: string, blocker: ?string}>}
     */
    public function plan(User $publisher, User $reviewer): array
    {
        $this->authorizeActors($publisher, $reviewer);
        $installation = $this->installer->install($publisher, dryRun: true);
        $installationByKey = collect($installation['items'])->keyBy('key');
        $items = [];

        foreach ($this->templates->all() as $key => $template) {
            $installationItem = $installationByKey->get($key);
            $contentNode = ContentNode::query()
                ->where('content_type', $template['content_type']->value)
                ->where('slug', $template['slug'])
                ->first();
            $blocker = null;
            $status = $contentNode?->status?->label() ?? 'Ontbreekt';
            $action = match ($installationItem['result'] ?? null) {
                'create' => 'Installeren, indienen, goedkeuren en publiceren',
                'upgrade' => 'Mediarevisie maken, indienen, goedkeuren en publiceren',
                'conflict' => 'Handmatige inhoudelijke vergelijking nodig',
                default => $this->workflowAction($contentNode),
            };

            if (($installationItem['result'] ?? null) === 'conflict') {
                if ($contentNode !== null && $this->preparePublishedDemoMediaUpgrade->canHandle($contentNode, $key, $template)) {
                    $action = 'Atomaire mediarevisie maken, onafhankelijk goedkeuren en publiceren';
                } else {
                    $blocker = $installationItem['message'];
                }
            } elseif ($contentNode !== null && ! in_array($contentNode->status, [
                ContentStatus::Draft,
                ContentStatus::InReview,
                ContentStatus::Approved,
                ContentStatus::Published,
            ], true)) {
                $blocker = 'Deze workflowstatus kan niet veilig door het proefweekcommando worden overgenomen.';
            }

            $items[] = [
                'key' => $key,
                'slug' => $template['slug'],
                'status' => $status,
                'action' => $action,
                'blocker' => $blocker,
            ];
        }

        return [
            'package_version' => DemoContentInstaller::PACKAGE_VERSION,
            'ready' => ! collect($items)->contains(fn (array $item): bool => $item['blocker'] !== null),
            'items' => $items,
        ];
    }

    /**
     * @return array{package_version: string, release: ?ContentRelease, published_count: int, already_published_count: int}
     */
    public function publish(User $publisher, User $reviewer): array
    {
        $plan = $this->plan($publisher, $reviewer);

        if (! $plan['ready']) {
            throw new RuntimeException('Het proefweekpakket bevat blokkades. Voer eerst de droge controle uit.');
        }

        return DB::transaction(function () use ($publisher, $reviewer): array {
            foreach ($this->templates->all() as $key => $template) {
                $contentNode = ContentNode::query()
                    ->where('content_type', $template['content_type']->value)
                    ->where('slug', $template['slug'])
                    ->first();

                if ($contentNode !== null && $this->preparePublishedDemoMediaUpgrade->canHandle($contentNode, $key, $template)) {
                    $this->preparePublishedDemoMediaUpgrade->handle($publisher, $contentNode, $key, $template);
                }
            }

            $installation = $this->installer->install($publisher);

            if ($installation['conflicts']) {
                throw new RuntimeException('Het proefweekpakket veranderde tijdens de controle en is niet gepubliceerd.');
            }

            $approved = [];
            $alreadyPublishedCount = 0;

            foreach ($this->templates->all() as $template) {
                $contentNode = ContentNode::query()
                    ->where('content_type', $template['content_type']->value)
                    ->where('slug', $template['slug'])
                    ->firstOrFail();

                if ($contentNode->status === ContentStatus::Published) {
                    $alreadyPublishedCount++;

                    continue;
                }

                if ($contentNode->status === ContentStatus::Draft) {
                    $contentNode = $this->submitContentForReview->handle(
                        actor: $publisher,
                        contentNode: $contentNode,
                        expectedVersion: $contentNode->current_version,
                        note: 'Proefweekpakket gecontroleerd op scene-contract, route, toegankelijkheid en gekoppelde media.',
                    );
                }

                if ($contentNode->status === ContentStatus::InReview) {
                    $contentNode = $this->decideContentReview->handle(
                        actor: $reviewer,
                        contentNode: $contentNode,
                        expectedVersion: $contentNode->current_version,
                        action: ContentReviewAction::Approved,
                        note: 'Vier-ogencontrole uitgevoerd op taal, didactiek, fictieve context, beeldrollen en speelbaarheid van de proefweek.',
                    );
                }

                if ($contentNode->status !== ContentStatus::Approved) {
                    throw new RuntimeException("{$contentNode->slug} kon niet veilig worden goedgekeurd.");
                }

                $approved[] = $contentNode;
            }

            if ($approved === []) {
                return [
                    'package_version' => DemoContentInstaller::PACKAGE_VERSION,
                    'release' => null,
                    'published_count' => 0,
                    'already_published_count' => $alreadyPublishedCount,
                ];
            }

            $release = $this->createContentRelease->handle(
                actor: $publisher,
                name: 'Proefweek compleet · '.now()->timezone('Europe/Madrid')->format('d-m-Y H:i'),
                targetChannel: ContentReleaseChannel::Production,
                description: 'Versiegebonden bèta-release met Madrid en de volledige zevendaagse proefweekcontent en media.',
            );

            foreach ($approved as $contentNode) {
                $release = $this->addContentToRelease->handle(
                    actor: $publisher,
                    release: $release,
                    contentNode: $contentNode,
                    expectedVersion: $contentNode->current_version,
                );
            }

            $release = $this->publishContentRelease->handle(
                actor: $publisher,
                release: $release,
                confirmation: 'PUBLICEREN',
                reason: 'Volledige proefweekset gecontroleerd en vrijgegeven voor de gesloten bèta.',
                acknowledgeWarnings: true,
            );

            return [
                'package_version' => DemoContentInstaller::PACKAGE_VERSION,
                'release' => $release,
                'published_count' => count($approved),
                'already_published_count' => $alreadyPublishedCount,
            ];
        });
    }

    private function authorizeActors(User $publisher, User $reviewer): void
    {
        if (! $publisher->hasContentPermission(ContentPermission::Edit)
            || ! $publisher->hasContentPermission(ContentPermission::Publish)) {
            throw new AuthorizationException('De uitgever moet content mogen bewerken en publiceren.');
        }

        if (! $reviewer->hasContentPermission(ContentPermission::Approve)) {
            throw new AuthorizationException('De reviewer moet content mogen goedkeuren.');
        }

        if ($publisher->is($reviewer)) {
            throw new AuthorizationException('Voor het complete proefweekpakket is bewust een onafhankelijke reviewer vereist.');
        }
    }

    private function workflowAction(?ContentNode $contentNode): string
    {
        return match ($contentNode?->status) {
            ContentStatus::Draft => 'Indienen, goedkeuren en publiceren',
            ContentStatus::InReview => 'Goedkeuren en publiceren',
            ContentStatus::Approved => 'Aan productierelease toevoegen en publiceren',
            ContentStatus::Published => 'Geen actie; exacte pakketversie staat live',
            default => 'Handmatige workflowcontrole nodig',
        };
    }
}
