<?php

namespace App\Feedback\Exceptions;

use RuntimeException;

class FeedbackAssessmentFailed extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }
}
