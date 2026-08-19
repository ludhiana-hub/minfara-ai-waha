<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use Illuminate\Http\Request;

class KonfigurasiController extends Controller
{
    private const VALID_AI_PROVIDERS = ['groq', 'gemini', 'openrouter', 'nvidia'];

    public function index()
    {
        $configs = BotConfig::all()->keyBy('key');

        // Env fallbacks for API credentials — shown in the form when DB value is empty
        $envFallbacks = [
            'waha_url'            => config('services.waha.url', ''),
            'waha_api_key'        => config('services.waha.api_key', ''),
            'waha_session'        => config('services.waha.session', 'default'),
            'gemini_api_key'      => config('services.gemini.key', ''),
            'gemini_model'        => config('services.gemini.model', 'gemini-2.0-flash'),
            'groq_api_key'        => config('services.groq.key', ''),
            'groq_model'          => config('services.groq.model', 'openai/gpt-oss-120b'),
            'openrouter_api_key'  => config('services.openrouter.key', ''),
            'openrouter_model'    => config('services.openrouter.model', 'deepseek/deepseek-chat-v3-0324:free'),
            'nvidia_api_key'      => config('services.nvidia.key', ''),
            // NOT config('services.nvidia.model') — that config key holds the fast CUSTOMER-CHAT
            // default (see NvidiaService), while this CMS field is the ANALYTICS model, matching
            // ConversationAnalysisService's own fallback via services.nvidia.analytics_model.
            'nvidia_model'        => config('services.nvidia.analytics_model', 'qwen/qwen3.5-397b-a17b'),
        ];

        $aiModels = config('ai_models') ?? [];

        return view('cms.konfigurasi.index', compact('configs', 'envFallbacks', 'aiModels'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'ai_max_tokens'     => ['nullable', 'integer', 'min:100', 'max:2000'],
            'ai_temperature'    => ['nullable', 'numeric', 'min:0', 'max:2'],
            'ai_provider_order' => ['nullable', 'string'],
            'ai_rag_top_k'      => ['nullable', 'integer', 'min:0', 'max:15'],
            'ai_rag_min_score'  => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        if ($request->has('ai_provider_order')) {
            $order = array_filter(array_map('trim', explode(',', (string) $request->input('ai_provider_order'))));
            $unknown = array_diff($order, self::VALID_AI_PROVIDERS);
            if (!empty($unknown) || empty($order)) {
                return redirect()->route('cms.konfigurasi.index')
                    ->withErrors(['ai_provider_order' => 'Urutan provider tidak valid: ' . implode(', ', $unknown ?: ['(kosong)']) . '. Provider yang tersedia: ' . implode(', ', self::VALID_AI_PROVIDERS) . '.'])
                    ->withInput();
            }
        }

        $keys = [
            'bot_name', 'bot_greeting',
            'ai_enabled', 'ai_provider_order', 'ai_max_tokens', 'ai_temperature', 'ai_system_prompt',
            'ai_rag_top_k', 'ai_rag_min_score',
            'footer_faq', 'footer_ai', 'fallback_message',
            'admin_wa', 'admin_wa_label', 'office_hours',
            'waha_url', 'waha_session', 'gemini_model', 'groq_model', 'openrouter_model', 'nvidia_model',
        ];

        // API key fields: only save if not empty (empty = keep existing value)
        $sensitiveKeys = ['waha_api_key', 'gemini_api_key', 'groq_api_key', 'openrouter_api_key', 'nvidia_api_key'];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $value = $key === 'ai_enabled'
                    ? ($request->boolean('ai_enabled') ? 'true' : 'false')
                    : $request->input($key);

                BotConfig::set($key, $value);
            } else if ($key === 'ai_enabled') {
                BotConfig::set('ai_enabled', 'false');
            }
        }

        foreach ($sensitiveKeys as $key) {
            $value = $request->input($key, '');
            if ($value !== '') {
                BotConfig::set($key, $value);
            }
        }

        // BotConfig::set() already forgets each changed key's own cache entry
        // (bot_config_{key}) — a blanket Cache::flush() here used to wipe every
        // OTHER customer's chat history, AI circuit breakers, and response cache
        // app-wide just because an admin saved one config field.

        return redirect()->route('cms.konfigurasi.index')
            ->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
