<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\WhatsappLog;
use App\Services\GeminiService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly GeminiService   $gemini,
    ) {}

    public function handle(Request $request): Response
    {
        $event   = $request->input('event');
        $payload = $request->input('payload');

        if ($event !== 'message') {
            return response('OK', 200);
        }

        if (($payload['fromMe'] ?? false) === true) {
            return response('OK', 200);
        }

        $type = $payload['_data']['type'] ?? $payload['type'] ?? '';
        if ($type !== 'chat') {
            return response('OK', 200);
        }

        $rawFrom   = $payload['from'] ?? null;
        $rawInput  = trim($payload['body'] ?? '');
        $messageId = $payload['id'] ?? null;

        if (empty($rawFrom) || !is_string($rawFrom) || strlen($rawFrom) > 100 || $rawInput === '') {
            return response('OK', 200);
        }

        // $chatId = full original ID for WAHA sendText (preserves @c.us / @lid / @g.us)
        $chatId = $rawFrom;
        // $from = numeric part only for DB storage and duplicate checks
        $from = preg_replace('/@.*$/', '', $rawFrom);

        // Contact display name from WhatsApp (notifyName / pushname)
        $contactName = $payload['notifyName']
            ?? $payload['_data']['notifyName']
            ?? $payload['_data']['pushname']
            ?? null;
        if ($contactName) {
            $contactName = trim($contactName);
            $contactName = $contactName === '' ? null : $contactName;
        }

        // Webhook source IP (WAHA server)
        $ipAddress = $request->ip();

        if (!empty($messageId)) {
            $isDuplicate = WhatsappLog::where('from_number', $from)
                ->where('message_in', $rawInput)
                ->where('responded_at', '>=', now()->subSeconds(5))
                ->exists();

            if ($isDuplicate) {
                return response('OK', 200);
            }
        }

        $command = strtolower($rawInput);

        $greetingWords = array_map('trim', explode(',', BotConfig::get('bot_greeting', 'halo,hai,hi,hello,hallo,mulai,start,menu,help')));
        if (in_array($command, $greetingWords, strict: true)) {
            $command = '0';
        }

        if ($command === '99') {
            $this->endChat($chatId, $from, $rawInput, $contactName, $ipAddress);
            return response('OK', 200);
        }

        $menu = FaqMenu::active()->where('command', $command)->first();

        if ($menu) {
            $this->faqReply($chatId, $from, $menu, $rawInput, $contactName, $ipAddress);
        } else {
            $this->aiReply($chatId, $from, $rawInput, $contactName, $ipAddress);
        }

        return response('OK', 200);
    }

    private function endChat(string $chatId, string $from, string $rawInput, ?string $contactName, ?string $ipAddress): void
    {
        $recentDuplicate = WhatsappLog::where('from_number', $from)
            ->where('message_in', '99')
            ->where('mode', 'end_chat')
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        $adminWa     = BotConfig::get('admin_wa', '6289647897616');
        $officeHours = BotConfig::get('office_hours', 'Senin–Sabtu, 08.00–20.00 WIB');

        $reply = "📞 *Chat Berakhir*\n\n"
            . "Terima kasih telah menghubungi MinFara! 🙏\n"
            . "Tim admin kami siap membantu Anda lebih lanjut.\n\n"
            . "WA Admin: *+$adminWa*\n"
            . "Jam: $officeHours\n\n"
            . "Ketik *0* untuk kembali ke menu utama.";

        if ($this->whatsapp->sendMessage($chatId, $reply)) {
            try {
                WhatsappLog::create([
                    'from_number'  => $from,
                    'contact_name' => $contactName,
                    'ip_address'   => $ipAddress,
                    'message_in'   => '99',
                    'message_out'  => $reply,
                    'mode'         => 'end_chat',
                    'responded_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
            }
        }
    }

    private function faqReply(string $chatId, string $from, FaqMenu $menu, string $rawInput, ?string $contactName, ?string $ipAddress): void
    {
        $content = $menu->content ?? $this->whatsapp->buildMainMenu();

        $recentDuplicate = WhatsappLog::where('from_number', $from)
            ->where('message_in', $rawInput)
            ->where('message_out', $content)
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        if ($this->whatsapp->sendMessage($chatId, $content)) {
            try {
                WhatsappLog::create([
                    'from_number'  => $from,
                    'contact_name' => $contactName,
                    'ip_address'   => $ipAddress,
                    'message_in'   => substr($rawInput, 0, 1000),
                    'message_out'  => $content,
                    'mode'         => 'faq',
                    'responded_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
            }
        }
    }

    private function aiReply(string $chatId, string $from, string $userMessage, ?string $contactName, ?string $ipAddress): void
    {
        $aiEnabled       = BotConfig::getBool('ai_enabled', true);
        $effectiveApiKey = BotConfig::get('gemini_api_key') ?: config('services.gemini.key', '');
        $aiAvailable     = $aiEnabled && !empty($effectiveApiKey);

        // When AI is off: redirect short noise messages to main menu instead of error spam
        if (!$aiAvailable) {
            $lowerMessage = strtolower(trim($userMessage));
            if (strlen($lowerMessage) <= 3 && !is_numeric($lowerMessage)) {
                $mainMenu = FaqMenu::active()->where('command', '0')->first();
                if ($mainMenu) {
                    $this->faqReply($chatId, $from, $mainMenu, $userMessage, $contactName, $ipAddress);
                } else {
                    $this->whatsapp->sendMessage($chatId, "Hallo! 👋 Ketik *0* untuk melihat menu atau *99* untuk hubungi admin.");
                }
                return;
            }
        }

        $tokens = null;

        if (!$aiAvailable) {
            $reply = BotConfig::get('fallback_message', "Hallo! 👋 Perintah tidak dikenali.\n\nKetik *0* untuk melihat menu lengkap atau *99* untuk chat dengan admin.");
            $mode  = 'error';
        } else {
            $systemPrompt = BotConfig::get('ai_system_prompt', '');
            $maxTokens    = BotConfig::getInt('ai_max_tokens', 500);
            $temperature  = BotConfig::getFloat('ai_temperature', 0.7);

            // Inject active FAQ content as knowledge base (cached 5 min)
            $faqContext = Cache::remember('faq_ai_context', 300, function () {
                return FaqMenu::active()
                    ->whereNotNull('content')
                    ->where('command', '!=', '0')
                    ->orderBy('sort_order')
                    ->get(['title', 'content'])
                    ->map(fn($m) => "### {$m->title}\n" . mb_substr(trim($m->content), 0, 800))
                    ->implode("\n\n");
            });

            if ($faqContext) {
                $systemPrompt .= "\n\n---\n"
                    . "KONTEN FAQ & INFORMASI LAYANAN (gunakan sebagai referensi utama. "
                    . "Jawab secara natural — JANGAN minta user mengetik perintah angka kecuali benar-benar perlu):\n\n"
                    . $faqContext;
            }

            $result = $this->gemini->chat($userMessage, $systemPrompt, $maxTokens, $temperature);

            if ($result['success']) {
                $reply  = $result['reply'] . "\n" . BotConfig::get('footer_ai', '');
                $mode   = 'ai';
                $tokens = $result['tokens'] ?? null;
            } else {
                $reply  = BotConfig::get('fallback_message', "Entschuldigung! 🙏 Coba lagi nanti atau ketik *99*.");
                $mode   = 'error';
                $tokens = $result['tokens'] ?? null;
            }
        }

        if (!empty($reply)) {
            $recentDuplicate = WhatsappLog::where('from_number', $from)
                ->where('message_in', substr($userMessage, 0, 1000))
                ->where('mode', $mode)
                ->where('responded_at', '>=', now()->subSeconds(3))
                ->exists();

            if ($recentDuplicate) {
                return;
            }

            if ($this->whatsapp->sendMessage($chatId, $reply)) {
                try {
                    WhatsappLog::create([
                        'from_number'    => $from,
                        'contact_name'   => $contactName,
                        'ip_address'     => $ipAddress,
                        'message_in'     => substr($userMessage, 0, 1000),
                        'message_out'    => $reply,
                        'mode'           => $mode,
                        'ai_tokens_used' => $tokens ?? null,
                        'responded_at'   => now(),
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
