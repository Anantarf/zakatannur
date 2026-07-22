<?php

return [
    /*
     * Chatbot provider: 'openai' or 'mock'
     * Uses CHATBOT_PROVIDER env var, defaults to 'openai' if API key available, else 'mock'
     */
    'provider' => env('CHATBOT_PROVIDER', 'openai'),

    'openai' => [
        'model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'fast_model' => env('OPENAI_FAST_MODEL', 'gpt-4o-mini'),
        'premium_model' => env('OPENAI_PREMIUM_MODEL', env('OPENAI_CHAT_MODEL', 'gpt-4o-mini')),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    /*
     * Rate limiting: requests per minute per session
     * ponytail: remove if not needed, add when throughput becomes issue
     */
    // 'rate_limit' => env('CHATBOT_RATE_LIMIT', 30),

    /*
     * Max context length (characters) to send to AI
     * Prevents accidentally sending too much data
     */
    'max_context_length' => 5000,
];
