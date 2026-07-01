<?php

/**
 * Free AI models for each provider.
 * Used by KonfigurasiController to populate dropdowns in CMS.
 *
 * 'primary'  — selectable in CMS, used as the chatbot model
 * 'fallback' — NVIDIA only: tried automatically if primary fails (shown as FYI, not selectable)
 */
return [

    'groq' => [
        ['id' => 'qwen/qwen3-32b',          'label' => 'Qwen3 32B',               'desc' => 'Cepat, multilingual ID+DE — direkomendasikan'],
        ['id' => 'llama-3.3-70b-versatile', 'label' => 'Llama 3.3 70B Versatile', 'desc' => 'Meta, sangat capable, versatile'],
        ['id' => 'llama-3.1-8b-instant',    'label' => 'Llama 3.1 8B Instant',    'desc' => 'Meta, super cepat, ringan'],
        ['id' => 'qwen/qwen3-8b',           'label' => 'Qwen3 8B',                'desc' => 'Alibaba, ringan dan cepat'],
        ['id' => 'gemma2-9b-it',            'label' => 'Gemma 2 9B',              'desc' => 'Google, cepat dan ringan'],
        ['id' => 'mixtral-8x7b-32768',      'label' => 'Mixtral 8x7B 32K',        'desc' => 'Mistral MoE, konteks panjang 32K token'],
    ],

    'gemini' => [
        ['id' => 'gemini-2.5-flash',    'label' => 'Gemini 2.5 Flash',    'desc' => 'Terbaru & terbaik, gratis tier — direkomendasikan'],
        ['id' => 'gemini-2.0-flash',    'label' => 'Gemini 2.0 Flash',    'desc' => 'Stabil, cepat, gratis tier'],
        ['id' => 'gemini-1.5-flash',    'label' => 'Gemini 1.5 Flash',    'desc' => 'Mature, proven, gratis tier'],
        ['id' => 'gemini-1.5-flash-8b', 'label' => 'Gemini 1.5 Flash 8B', 'desc' => 'Super ringan & cepat, gratis tier'],
    ],

    'openrouter' => [
        ['id' => 'openrouter/free',                             'label' => 'Auto Router (Free)',  'desc' => 'Otomatis pilih dari puluhan model gratis yang sedang aktif — anti model deprecated, direkomendasikan'],
        ['id' => 'qwen/qwen3-14b:free',                         'label' => 'Qwen3 14B',           'desc' => 'Cepat, multilingual ID+DE'],
        ['id' => 'qwen/qwen3-30b-a3b:free',                     'label' => 'Qwen3 30B MoE',       'desc' => 'Lebih pintar dari 14B, sedikit lebih lambat'],
        ['id' => 'qwen/qwen3-8b:free',                          'label' => 'Qwen3 8B',            'desc' => 'Super cepat, ringan'],
        ['id' => 'qwen/qwen3-235b-a22b:free',                   'label' => 'Qwen3 235B MoE',      'desc' => 'Paling powerful, bisa lebih lambat'],
        ['id' => 'deepseek/deepseek-chat-v3-0324:free',         'label' => 'DeepSeek Chat V3',    'desc' => 'Sangat baik, multilingual'],
        ['id' => 'deepseek/deepseek-r1:free',                   'label' => 'DeepSeek R1',         'desc' => 'Reasoning model, lebih lambat tapi akurat'],
        ['id' => 'meta-llama/llama-3.3-70b-instruct:free',      'label' => 'Llama 3.3 70B',       'desc' => 'Meta, sangat capable'],
        ['id' => 'meta-llama/llama-3.1-8b-instruct:free',       'label' => 'Llama 3.1 8B',        'desc' => 'Meta, super cepat'],
        ['id' => 'google/gemma-3-27b-it:free',                  'label' => 'Gemma 3 27B',         'desc' => 'Google, capable'],
        ['id' => 'google/gemma-3-12b-it:free',                  'label' => 'Gemma 3 12B',         'desc' => 'Google, cepat'],
        ['id' => 'mistralai/mistral-7b-instruct:free',          'label' => 'Mistral 7B',          'desc' => 'Cepat dan ringan'],
        ['id' => 'microsoft/phi-3-mini-128k-instruct:free',     'label' => 'Phi-3 Mini 128K',     'desc' => 'Konteks panjang, ringan'],
    ],

    'nvidia' => [
        // Primary models — selectable in CMS
        'primary' => [
            ['id' => 'qwen/qwen3.5-397b-a17b',                  'label' => 'Qwen3.5 397B',             'desc' => 'Terbaik untuk analytics, ~4 detik — direkomendasikan'],
            ['id' => 'nvidia/llama-3.1-nemotron-ultra-253b-v1', 'label' => 'Nemotron Ultra 253B',       'desc' => 'NVIDIA flagship, akurasi sangat tinggi'],
            ['id' => 'openai/gpt-oss-120b',                     'label' => 'GPT OSS 120B',              'desc' => 'OpenAI open-weight, ~5 detik'],
            ['id' => 'openai/gpt-oss-20b',                      'label' => 'GPT OSS 20B',               'desc' => 'OpenAI open-weight, lebih cepat'],
            ['id' => 'meta/llama-4-maverick-17b-128e-instruct', 'label' => 'Llama 4 Maverick 17B MoE', 'desc' => 'Meta MoE, cepat dan efisien'],
            ['id' => 'meta/llama-3.3-70b-instruct',             'label' => 'Llama 3.3 70B',             'desc' => 'Meta, reliable dan stabil'],
            ['id' => 'mistralai/mistral-large-2-instruct',      'label' => 'Mistral Large 2',           'desc' => 'Mistral, multilingual'],
            ['id' => 'google/gemma-4-31b-it',                   'label' => 'Gemma 4 31B',               'desc' => 'Google, capable'],
            ['id' => 'deepseek-ai/deepseek-v4-flash',           'label' => 'DeepSeek V4 Flash',         'desc' => 'Paling cepat, cocok untuk high-traffic'],
        ],
        // Fallback chain — shown in CMS as FYI only, not selectable
        // These are tried automatically in order if the primary model fails.
        'fallback' => [
            ['id' => 'qwen/qwen3.5-122b-a10b',                  'label' => 'Qwen3.5 122B',        'desc' => 'Backup 1 — lebih kecil dari 397B, lebih cepat'],
            ['id' => 'nvidia/llama-3.3-nemotron-super-49b-v1.5','label' => 'Nemotron Super 49B',   'desc' => 'Backup 2 — NVIDIA, stabil'],
            ['id' => 'deepseek-ai/deepseek-v4-flash',           'label' => 'DeepSeek V4 Flash',    'desc' => 'Backup 3 — paling cepat, last resort'],
        ],
    ],
];
