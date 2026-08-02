<?php

return [

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

    'gemini' => [
        'key'   => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'groq' => [
        'key'   => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'qwen/qwen3-32b'),
    ],

    'openrouter' => [
        'key'   => env('OPENROUTER_API_KEY', ''),
        'model' => env('OPENROUTER_MODEL', 'openrouter/free'),
    ],

    'nvidia' => [
        'key' => env('NVIDIA_API_KEY', ''),
        // Two DELIBERATELY separate models — do not merge them:
        //   model            → 'chat' AiRouter profile (config/ai_profiles.php), customer chat
        //                      fallback provider. Must stay FAST — deliberately not overridable
        //                      via the BotConfig 'nvidia_model' CMS field (see below).
        //   analytics_model  → 'analytics' AiRouter profile, nightly batch analysis. Can be a
        //                      heavy reasoning model; BotConfig's CMS field 'nvidia_model'
        //                      overrides this at runtime and takes precedence when set.
        'model'           => env('NVIDIA_MODEL', 'meta/llama-3.1-8b-instruct'),
        'analytics_model' => env('NVIDIA_ANALYTICS_MODEL', 'qwen/qwen3.5-397b-a17b'),
    ],

];
