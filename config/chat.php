<?php

return [
    'completion' => [
        'endpoint' => env('CHAT_API_ENDPOINT'),
        'key' => env('CHAT_API_KEY'),
        'model' => env('CHAT_API_MODEL'),
        'temperature' => (float) env('CHAT_API_TEMP'),
        'maxTokens' => (int) env('CHAT_API_MAX_TOKENS'),
        'systemPrompt' => file_get_contents(resource_path('prompts/system_prompt.txt')),
        'timeout' => 10, // seconds
        'retries' => 2,
        'retryDelay' => 200 // ms
    ],
    'rag' => [
        'endpoint' => env('SEARCH_API_ENDPOINT'),
        'key' => env('SEARCH_API_KEY'),
        'top' => 3, // number of top results to return
        'cacheTtl' => 86400, // seconds
        'timeout'  => 5, // seconds
        'retries'  => 2,
        'retryDelay' => 100 // ms
    ]
];
