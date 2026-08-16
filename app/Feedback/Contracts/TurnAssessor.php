<?php

namespace App\Feedback\Contracts;

use App\Feedback\TurnAssessment;
use App\Feedback\TurnContext;

interface TurnAssessor
{
    /**
     * Assess language evidence without deciding dialogue progress or rewards.
     */
    public function assess(
        TurnContext $context,
        string $answer,
        string $source,
        ?string $transcriptConfidenceStatus,
        bool $transcriptCorrected,
        string $level,
    ): TurnAssessment;
}
