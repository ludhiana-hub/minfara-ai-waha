<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KonfigurasiController extends Controller
{
    public function index()
    {
        $configs = BotConfig::all()->keyBy('key');

        // Env fallbacks for API credentials — shown in the form when DB value is empty
        $envFallbacks = [
            'waha_url'       => config('services.waha.url', ''),
            'waha_api_key'   => config('services.waha.api_key', ''),
            'waha_session'   => config('services.waha.session', 'default'),
            'gemini_api_key' => config('services.gemini.key', ''),
            'gemini_model'   => config('services.gemini.model', 'gemini-2.0-flash'),
        ];

        return view('cms.konfigurasi.index', compact('configs', 'envFallbacks'));
    }

    public function update(Request $request)
    {
        $keys = [
            'bot_name', 'bot_greeting',
            'ai_enabled', 'ai_max_tokens', 'ai_temperature', 'ai_system_prompt',
            'footer_faq', 'footer_ai', 'fallback_message',
            'admin_wa', 'admin_wa_label', 'office_hours',
            'waha_url', 'waha_session', 'gemini_model',
        ];

        // API key fields: only save if not empty (empty = keep existing value)
        $sensitiveKeys = ['waha_api_key', 'gemini_api_key'];

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

        Cache::flush();

        return redirect()->route('cms.konfigurasi.index')
            ->with('success', 'Konfigurasi berhasil disimpan.');
    }
}
