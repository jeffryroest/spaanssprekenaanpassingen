<?php

namespace App\Feedback;

final readonly class TurnContext
{
    /**
     * @param  array<int, array<int, array<int, string>>>  $acceptedRequirements
     * @param  array<int, string>  $repairTerms
     */
    public function __construct(
        public string $scenario,
        public int $contentVersion,
        public string $stepId,
        public int $turn,
        public string $npcLine,
        public string $prompt,
        public string $hint,
        public array $acceptedRequirements,
        public array $repairTerms,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function assessmentData(
        string $answer,
        string $source,
        ?string $transcriptConfidenceStatus,
        bool $transcriptCorrected,
        string $level,
    ): array {
        return [
            'scenario' => $this->scenario,
            'content_version' => $this->contentVersion,
            'step' => [
                'id' => $this->stepId,
                'turn' => $this->turn,
                'npc_line_es' => $this->npcLine,
                'learner_goal_nl' => $this->prompt,
                'hint_nl' => $this->hint,
                'accepted_intent_terms' => $this->acceptedRequirements,
                'repair_terms' => $this->repairTerms,
            ],
            'learner' => [
                'level' => $level,
                'answer_es' => $answer,
                'source' => $source,
                'transcript_confidence_status' => $transcriptConfidenceStatus,
                'transcript_corrected' => $transcriptCorrected,
            ],
        ];
    }
}
