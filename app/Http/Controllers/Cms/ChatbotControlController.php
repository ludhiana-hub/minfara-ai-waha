<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use Illuminate\Http\Request;

/**
 * Dashboard quick switch for the two things that answer inbound chats (AI and FAQ). Outbound
 * notifications don't depend on either, so turning both off leaves "notification only".
 */
class ChatbotControlController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'ai_enabled'  => ['sometimes', 'boolean'],
            'faq_enabled' => ['sometimes', 'boolean'],
        ]);

        // Only the switches actually sent are changed, so one toggle never flips the other.
        foreach (['ai_enabled', 'faq_enabled'] as $key) {
            if (array_key_exists($key, $data)) {
                BotConfig::set($key, $data[$key] ? 'true' : 'false');
            }
        }

        $mode = BotConfig::chatbotMode();

        if ($request->expectsJson()) {
            return response()->json([
                'ai_enabled'  => BotConfig::getBool('ai_enabled', true),
                'faq_enabled' => BotConfig::getBool('faq_enabled', true),
                'mode'        => $mode,
            ]);
        }

        return back()->with('success', $mode === 'notification_only'
            ? 'Chatbot dimatikan. Hanya notifikasi yang berjalan.'
            : 'Pengaturan chatbot disimpan.');
    }
}
