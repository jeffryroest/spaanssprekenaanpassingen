<?php

namespace App\ContentStudio;

use App\Actions\ContentStudio\CreateDraftContent;
use App\Enums\ContentPermission;
use App\Models\ContentNode;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class DemoContentInstaller
{
    public const PACKAGE_VERSION = '2026.08.1';

    public function __construct(
        private readonly PlayableContentTemplates $templates,
        private readonly CreateDraftContent $createDraftContent,
    ) {}

    /**
     * @return array{package_version: string, applied: bool, conflicts: bool, items: list<array{key: string, slug: string, result: string, message: string}>}
     */
    public function install(User $actor, bool $dryRun = false): array
    {
        if (! $actor->hasContentPermission(ContentPermission::Edit)) {
            throw new AuthorizationException('De gekozen actor mag geen Content Studio-concepten aanmaken.');
        }

        $plan = $this->plan();
        $hasConflicts = collect($plan)->contains(fn (array $item): bool => $item['result'] === 'conflict');

        if ($hasConflicts || $dryRun) {
            return [
                'package_version' => self::PACKAGE_VERSION,
                'applied' => false,
                'conflicts' => $hasConflicts,
                'items' => $plan,
            ];
        }

        DB::transaction(function () use ($actor, $plan): void {
            foreach ($this->templates->all() as $key => $template) {
                $planned = collect($plan)->firstWhere('key', $key);

                if (($planned['result'] ?? null) !== 'create') {
                    continue;
                }

                $this->createDraftContent->handle(
                    actor: $actor,
                    contentType: $template['content_type'],
                    slug: $template['slug'],
                    locale: $template['locale'],
                    title: $template['title'],
                    summary: $template['summary'],
                    body: $template['body'],
                    metadata: [
                        'demo_content_package' => [
                            'key' => $key,
                            'version' => self::PACKAGE_VERSION,
                        ],
                    ],
                    domainData: $template['domain_data'],
                );
            }
        });

        return [
            'package_version' => self::PACKAGE_VERSION,
            'applied' => true,
            'conflicts' => false,
            'items' => array_map(
                static fn (array $item): array => $item['result'] === 'create'
                    ? array_replace($item, ['result' => 'created', 'message' => 'Concept aangemaakt.'])
                    : $item,
                $plan,
            ),
        ];
    }

    /** @return list<array{key: string, slug: string, result: string, message: string}> */
    private function plan(): array
    {
        $items = [];

        foreach ($this->templates->all() as $key => $template) {
            $contentNode = ContentNode::withTrashed()
                ->with(['localizations', 'revisions'])
                ->where('content_type', $template['content_type']->value)
                ->where('slug', $template['slug'])
                ->first();

            if ($contentNode === null) {
                $items[] = [
                    'key' => $key,
                    'slug' => $template['slug'],
                    'result' => 'create',
                    'message' => 'Nieuw veilig concept staat klaar om aan te maken.',
                ];

                continue;
            }

            if ($this->matchesTemplate($contentNode, $template)) {
                $items[] = [
                    'key' => $key,
                    'slug' => $template['slug'],
                    'result' => 'unchanged',
                    'message' => 'Bestaande inhoud is al gelijk aan dit pakket.',
                ];

                continue;
            }

            $items[] = [
                'key' => $key,
                'slug' => $template['slug'],
                'result' => 'conflict',
                'message' => $contentNode->trashed()
                    ? 'Een gearchiveerd record gebruikt deze vaste sleutel; herstel of hernoem het eerst.'
                    : 'Bestaande inhoud wijkt af en wordt nooit door het demopakket overschreven.',
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $template */
    private function matchesTemplate(ContentNode $contentNode, array $template): bool
    {
        if ($contentNode->trashed() || $contentNode->default_locale !== $template['locale']) {
            return false;
        }

        $localization = $contentNode->defaultLocalization();
        $revision = $contentNode->revisions->firstWhere('version', $contentNode->current_version);

        return $localization !== null
            && $revision !== null
            && $localization->title === $template['title']
            && $localization->summary === $template['summary']
            && $localization->body === $template['body']
            && data_get($revision->snapshot, 'domain_data', []) == $template['domain_data'];
    }
}
