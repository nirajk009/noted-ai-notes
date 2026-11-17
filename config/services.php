<?php

return [

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-2'),
        'summary_models' => explode(',', env('GEMINI_SUMMARY_MODELS', 'gemini-4-hydrogen,gemini-3.8-flash,gemini-3.7-flash,gemini-3.6-flash,gemini-3.1-flash-lite')),
        'embedding_dimensions' => (int) env('GEMINI_EMBEDDING_DIMENSIONS', 768),
        'minimum_similarity' => (float) env('GEMINI_MINIMUM_SIMILARITY', 0.65),
        'query_cache_seconds' => (int) env('GEMINI_QUERY_CACHE_SECONDS', 3600),
        'model_cooldown_seconds' => (int) env('GEMINI_MODEL_COOLDOWN_SECONDS', 300),
        'summary_timeout_seconds' => (int) env('GEMINI_SUMMARY_TIMEOUT_SECONDS', 6),
        'ca_bundle' => env('GEMINI_CA_BUNDLE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
