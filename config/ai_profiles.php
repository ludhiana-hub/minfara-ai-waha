<?php

/**
 * AiRouter orchestration profiles. Every consumer of AI (customer chat, nightly
 * analytics, weekly synthesis, eval harness) selects a profile by name — the profile
 * controls provider order, model fallback chains, timeouts, JSON mode, and — critically —
 * which circuit-breaker "scope" it shares.
 *
 * `circuit_scope` is the single most important safety property here: the 'chat' profile
 * uses '' (empty), which produces the EXACT SAME cache key the app has always used
 * (`ai_provider_unhealthy_{provider}`) — customer-chat behavior does not change one bit.
 * Every other profile uses a distinct scope + `mark_unhealthy=false`, so a batch job or
 * eval run hammering a provider can NEVER mark it unhealthy for customers.
 */

return [

    'defaults' => [
        'max_total_attempts' => 6,
        'cooldown_seconds'   => 300,
        'timeout'            => 20,
        'retry_on'           => ['Connection timeout'],
        'retry_delay_ms'     => 1000,
        'mark_unhealthy'     => true,
        'circuit_scope'      => '',
        'trace'              => 'on_retry', // never | on_retry | always
    ],

    'profiles' => [

        // Customer-facing chat — ProcessAiReply. Orchestration values below are copied
        // VERBATIM from the pre-AiRouter ProcessAiReply constants; do not change them
        // without re-reading app/Jobs/ProcessAiReply.php's history for why they're set
        // this way.
        'chat' => [
            'order_config_key' => 'ai_provider_order',
            'default_order'    => ['groq', 'gemini', 'openrouter'],
            'primary_model'    => [
                'groq'       => ['bot_config' => 'groq_model',       'config' => 'services.groq.model',       'default' => 'llama-3.3-70b-versatile'],
                'gemini'     => ['bot_config' => 'gemini_model',     'config' => 'services.gemini.model',     'default' => 'gemini-2.0-flash'],
                'openrouter' => ['bot_config' => 'openrouter_model', 'config' => 'services.openrouter.model', 'default' => 'openrouter/free'],
                // Deliberately NOT BotConfig 'nvidia_model' — that key is reserved for the
                // analytics profile below. Customer chat always uses the fast config default.
                'nvidia'     => ['bot_config' => null,               'config' => 'services.nvidia.model',     'default' => 'meta/llama-3.1-8b-instruct'],
            ],
            'fallback_models' => [
                'groq'       => ['gemma2-9b-it', 'llama-3.1-8b-instant'],
                'gemini'     => ['gemini-1.5-flash-8b'],
                'openrouter' => [], // openrouter/free already routes across ~24+ free models internally
                'nvidia'     => [],
            ],
            // everything else (timeout, circuit_scope, mark_unhealthy, trace) inherits defaults
        ],

        'analytics' => [
            'order_config_key'  => 'ai_provider_order_analytics',
            'default_order'     => ['nvidia'],
            'primary_model'     => [
                'nvidia' => ['bot_config' => 'nvidia_model', 'config' => 'services.nvidia.analytics_model', 'default' => 'qwen/qwen3.5-397b-a17b'],
            ],
            'fallback_models'   => [
                'nvidia' => ['qwen/qwen3.5-122b-a10b', 'nvidia/llama-3.3-nemotron-super-49b-v1.5', 'deepseek-ai/deepseek-v4-flash'],
            ],
            'timeout'            => 120,
            'max_total_attempts' => 4,
            'circuit_scope'      => 'batch',
            'mark_unhealthy'     => false,
            'trace'              => 'always',
        ],

        'synthesis' => [
            'order_config_key' => 'ai_provider_order_synthesis',
            'default_order'    => ['groq', 'gemini', 'openrouter'],
            'primary_model'    => [
                'groq'       => ['bot_config' => 'groq_model',       'config' => 'services.groq.model',       'default' => 'llama-3.3-70b-versatile'],
                'gemini'     => ['bot_config' => 'gemini_model',     'config' => 'services.gemini.model',     'default' => 'gemini-2.0-flash'],
                'openrouter' => ['bot_config' => 'openrouter_model', 'config' => 'services.openrouter.model', 'default' => 'openrouter/free'],
            ],
            'fallback_models' => [
                'groq'       => ['gemma2-9b-it', 'llama-3.1-8b-instant'],
                'gemini'     => ['gemini-1.5-flash-8b'],
                'openrouter' => [],
            ],
            'timeout'        => 60,
            'circuit_scope'  => 'batch',
            'mark_unhealthy' => false,
            'trace'          => 'always',
        ],

        // Not wired to a consumer until Fase 4 (eval harness), defined now so Fase 1's
        // circuit_scope isolation is already correct and testable.
        'judge' => [
            'default_order'  => ['gemini', 'nvidia', 'openrouter'], // deliberately NOT groq-first (chat's primary)
            'primary_model'  => [
                'gemini'     => ['bot_config' => null, 'config' => 'services.gemini.model',     'default' => 'gemini-2.0-flash'],
                'nvidia'     => ['bot_config' => null, 'config' => 'services.nvidia.model',      'default' => 'meta/llama-3.1-8b-instruct'],
                'openrouter' => ['bot_config' => null, 'config' => 'services.openrouter.model',  'default' => 'openrouter/free'],
            ],
            'fallback_models' => [],
            'timeout'         => 60,
            'circuit_scope'   => 'eval',
            'mark_unhealthy'  => false,
            'trace'           => 'always',
        ],

        'eval' => [
            'order_config_key' => 'ai_provider_order',
            'default_order'    => ['groq', 'gemini', 'openrouter'],
            'primary_model'    => [
                'groq'       => ['bot_config' => 'groq_model',       'config' => 'services.groq.model',       'default' => 'llama-3.3-70b-versatile'],
                'gemini'     => ['bot_config' => 'gemini_model',     'config' => 'services.gemini.model',     'default' => 'gemini-2.0-flash'],
                'openrouter' => ['bot_config' => 'openrouter_model', 'config' => 'services.openrouter.model', 'default' => 'openrouter/free'],
            ],
            'fallback_models' => [],
            'circuit_scope'   => 'eval',
            'mark_unhealthy'  => false,
            'trace'           => 'always',
        ],
    ],
];
