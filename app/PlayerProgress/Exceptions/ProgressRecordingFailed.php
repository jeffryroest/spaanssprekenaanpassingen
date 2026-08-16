<?php

namespace App\PlayerProgress\Exceptions;

use RuntimeException;

class ProgressRecordingFailed extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }
}
