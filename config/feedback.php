<?php

return [
    'assessor_version' => env('FEEDBACK_ASSESSOR_VERSION', 'turn-rubric-v1'),
    'feedback_version' => env('FEEDBACK_FORMATTER_VERSION', 'layered-feedback-v1'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_FEEDBACK_MODEL', 'gpt-4o-mini'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('OPENAI_FEEDBACK_TIMEOUT', 15),
    ],
];
