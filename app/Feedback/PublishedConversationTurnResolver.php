<?php

namespace App\Feedback;

use App\ContentApi\PublishedContentRepository;
use App\Enums\ContentType;
use App\Feedback\Contracts\TurnContextResolver;
use App\Feedback\Exceptions\FeedbackAssessmentFailed;

final class PublishedConversationTurnResolver implements TurnContextResolver
{
    public function __construct(private readonly PublishedContentRepository $repository) {}

    public function resolve(string $scenarioSlug, string $stepId): TurnContext
    {
        $node = $this->repository->find(ContentType::ConversationScenario, $scenarioSlug);
        $releaseItem = $node === null ? null : $this->repository->latestProductionItem($node);
        $snapshot = $releaseItem?->contentRevision?->snapshot;
        $domainData = is_array($snapshot) ? ($snapshot['domain_data'] ?? null) : null;

        if (! is_array($domainData) || ($domainData['schema_version'] ?? null) !== '1.0.0') {
            throw new FeedbackAssessmentFailed(
                'feedback_context_unavailable',
                503,
                'De gepubliceerde gesprekcontext is tijdelijk niet beschikbaar.',
            );
        }

        $steps = $domainData['steps'] ?? null;
        $step = is_array($steps)
            ? collect($steps)->first(fn (mixed $candidate): bool => is_array($candidate) && ($candidate['id'] ?? null) === $stepId)
            : null;

        if (! is_array($step)) {
            throw new FeedbackAssessmentFailed(
                'feedback_step_unknown',
                422,
                'Deze dialoogstap hoort niet bij de actuele gepubliceerde conversatie.',
            );
        }

        $options = is_array($step['options'] ?? null) ? $step['options'] : [];
        $requirements = array_values(array_map(
            fn (array $option): array => is_array($option['requirements'] ?? null) ? $option['requirements'] : [],
            array_filter($options, 'is_array'),
        ));
        $repairTerms = is_array($domainData['repair']['terms'] ?? null)
            ? array_values(array_filter($domainData['repair']['terms'], 'is_string'))
            : [];

        return new TurnContext(
            scenario: $scenarioSlug,
            contentVersion: (int) ($releaseItem->version ?? 0),
            stepId: $stepId,
            turn: (int) ($step['turn'] ?? 0),
            npcLine: (string) ($step['npc_line']['es'] ?? ''),
            prompt: (string) ($step['prompt'] ?? ''),
            hint: (string) ($step['hint'] ?? ''),
            acceptedRequirements: $requirements,
            repairTerms: $repairTerms,
        );
    }
}
