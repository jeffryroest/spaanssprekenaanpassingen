<?php

return [
    'driver' => env('TRANSCRIPTION_DRIVER', 'openai'),

    'low_confidence_threshold' => (float) env('TRANSCRIPTION_LOW_CONFIDENCE_THRESHOLD', 0.65),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('OPENAI_TRANSCRIPTION_TIMEOUT', 20),
    ],
];
