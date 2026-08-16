<?php

namespace App\Speech;

final readonly class TranscriptionResult
{
    public function __construct(
        public string $transcript,
        public ?float $confidence,
        public string $confidenceStatus,
        public string $provider,
        public string $model,
    ) {}

    /**
     * @return array<string, float|string|null>
     */
    public function toArray(): array
    {
        return [
            'transcript' => $this->transcript,
            'language' => 'es',
            'confidence' => $this->confidence,
            'confidence_status' => $this->confidenceStatus,
            'provider' => $this->provider,
            'model' => $this->model,
        ];
    }
}
