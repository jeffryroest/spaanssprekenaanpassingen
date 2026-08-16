<?php

namespace App\Speech\Exceptions;

use RuntimeException;

class TranscriptionFailed extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }
}
