<?php

namespace App\Feedback;

final readonly class TurnAssessment
{
    public function __construct(
        public int $taskExecution,
        public int $comprehensibility,
        public int $vocabulary,
        public int $grammar,
        public int $conversationStrategy,
        public string $strength,
        public string $focusDimension,
        public string $focusMessage,
        public ?string $exampleEs,
        public bool $retryRecommended,
        public string $assessorVersion,
    ) {}
}
