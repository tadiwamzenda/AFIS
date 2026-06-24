<?php

return [
    'primary_engine'  => env('AI_PRIMARY_ENGINE', 'claude'),
    'fallback_engine' => env('AI_FALLBACK_ENGINE', 'openai'),

    'claude' => [
        'api_key'   => env('ANTHROPIC_API_KEY', ''),
        'model'     => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'max_tokens'=> 4096,
        'base_url'  => 'https://api.anthropic.com/v1',
    ],

    'openai' => [
        'api_key'   => env('OPENAI_API_KEY', ''),
        'model'     => env('OPENAI_MODEL', 'gpt-4o'),
        'max_tokens'=> 4096,
        'base_url'  => 'https://api.openai.com/v1',
    ],

    // Cache AI responses for this many hours before regenerating
    'cache_hours' => env('AI_CACHE_HOURS', 6),
];