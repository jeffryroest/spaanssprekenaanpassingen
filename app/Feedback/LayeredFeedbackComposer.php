<?php

namespace App\Feedback;

final class LayeredFeedbackComposer
{
    /**
     * @return array<string, mixed>
     */
    public function compose(TurnAssessment $assessment, string $source): array
    {
        $scores = [
            'task_execution' => $assessment->taskExecution,
            'comprehensibility' => $assessment->comprehensibility,
            'vocabulary' => $assessment->vocabulary,
            'grammar' => $assessment->grammar,
            'conversation_strategy' => $assessment->conversationStrategy,
        ];
        $overall = round((
            ($scores['task_execution'] * .25)
            + ($scores['comprehensibility'] * .25)
            + ($scores['vocabulary'] * .125)
            + ($scores['grammar'] * .125)
            + ($scores['conversation_strategy'] * .125)
        ) / .875, 1);

        return [
            'assessor_version' => $assessment->assessorVersion,
            'feedback_version' => (string) config('feedback.feedback_version'),
            'rubric' => [
                'task_execution' => $this->scoredDimension($scores['task_execution']),
                'comprehensibility' => $this->scoredDimension($scores['comprehensibility']),
                'vocabulary' => $this->scoredDimension($scores['vocabulary']),
                'grammar' => $this->scoredDimension($scores['grammar']),
                'pronunciation' => [
                    'score' => null,
                    'maximum' => 4,
                    'status' => 'not_assessed',
                    'reason' => $source === 'speech'
                        ? 'Niet beoordeeld: de feedbackservice ontvangt alleen het transcript, niet de opname.'
                        : 'Niet beoordeeld: dit antwoord is als tekst beoordeeld.',
                ],
                'conversation_strategy' => $this->scoredDimension($scores['conversation_strategy']),
            ],
            'overall' => [
                'score' => $overall,
                'maximum' => 4,
                'band' => match (true) {
                    $overall < 1.5 => 'needs_support',
                    $overall < 2.5 => 'developing',
                    $overall < 3.5 => 'communicative',
                    default => 'confident',
                },
                'pronunciation_included' => false,
            ],
            'summary' => [
                'strength' => $assessment->strength,
                'focus' => [
                    'dimension' => $assessment->focusDimension,
                    'message' => $assessment->focusMessage,
                    'example_es' => $assessment->exampleEs,
                ],
                'retry_recommended' => $assessment->retryRecommended
                    || min($assessment->taskExecution, $assessment->comprehensibility) <= 1,
            ],
        ];
    }

    /** @return array{score: int, maximum: int, status: string} */
    private function scoredDimension(int $score): array
    {
        return [
            'score' => $score,
            'maximum' => 4,
            'status' => 'assessed',
        ];
    }
}
