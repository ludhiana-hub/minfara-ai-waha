<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function index()
    {
        $faqs = FaqMenu::active()->orderBy('sort_order')->get();
        return view('cms.test.index', compact('faqs'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'phone'   => ['required', 'string', 'regex:/^628[0-9]{7,13}$/'],
            'message' => ['required', 'string', 'max:4096'],
        ], [
            'phone.regex' => 'Format nomor harus 628xxx (tanpa + atau spasi).',
        ]);

        $chatId  = $request->phone . '@c.us';
        $wahaUrl = BotConfig::get('waha_url') ?: config('services.waha.url', 'http://localhost:3000');
        $wahaKey = BotConfig::get('waha_api_key') ?: config('services.waha.api_key', '');
        $session = BotConfig::get('waha_session') ?: config('services.waha.session', 'default');
        $start   = microtime(true);

        try {
            $response = Http::timeout(60)
                ->withHeaders(['X-Api-Key' => $wahaKey])
                ->post("{$wahaUrl}/api/sendText", [
                    'session' => $session,
                    'chatId'  => $chatId,
                    'text'    => $request->message,
                ]);

            $elapsed = round((microtime(true) - $start) * 1000);

            return response()->json([
                'success'  => $response->successful(),
                'status'   => $response->status(),
                'body'     => $response->json(),
                'elapsed'  => $elapsed,
                'message'  => $response->successful() ? 'Pesan berhasil dikirim!' : 'Gagal mengirim pesan.',
            ]);
        } catch (\Exception $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            return response()->json([
                'success' => false,
                'status'  => 0,
                'body'    => ['error' => $e->getMessage()],
                'elapsed' => $elapsed,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}
