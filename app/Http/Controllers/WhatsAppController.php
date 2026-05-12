<?php

namespace App\Http\Controllers;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\WhatsappLog;
use App\Services\Contracts\AiServiceInterface;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\OpenRouterService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppService   $whatsapp,
        private readonly GeminiService     $gemini,
        private readonly GroqService       $groq,
        private readonly OpenRouterService $openrouter,
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
            $this->endChat($chatId, $from, $contactName, $ipAddress);
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

    private function resolveService(string $provider): ?AiServiceInterface
    {
        return match ($provider) {
            'gemini'     => $this->gemini,
            'groq'       => $this->groq,
            'openrouter' => $this->openrouter,
            default      => null,
        };
    }

    private function providerHasKey(string $provider): bool
    {
        return match ($provider) {
            'gemini'     => !empty(BotConfig::get('gemini_api_key') ?: config('services.gemini.key', '')),
            'groq'       => !empty(BotConfig::get('groq_api_key') ?: config('services.groq.key', '')),
            'openrouter' => !empty(BotConfig::get('openrouter_api_key') ?: config('services.openrouter.key', '')),
            default      => false,
        };
    }

    private function endChat(string $chatId, string $from, ?string $contactName, ?string $ipAddress): void
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
                Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
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
                Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
            }
        }
    }

    private function aiReply(string $chatId, string $from, string $userMessage, ?string $contactName, ?string $ipAddress): void
    {
        $aiEnabled     = BotConfig::getBool('ai_enabled', true);
        $orderStr      = BotConfig::get('ai_provider_order', 'groq,gemini,openrouter');
        $providerOrder = array_filter(array_map('trim', explode(',', $orderStr)));
        $aiAvailable   = $aiEnabled && !empty(array_filter($providerOrder, fn($p) => $this->providerHasKey($p)));

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
            $reply  = BotConfig::get('fallback_message', "Hallo! 👋 Perintah tidak dikenali.\n\nKetik *0* untuk melihat menu lengkap atau *99* untuk chat dengan admin.");
            $mode   = 'error';
            $tokens = null;
        } else {
            // Per-user cooldown — one AI call per 5 seconds per user to prevent token burn
            $cooldownKey = 'ai_cooldown_' . md5($from);
            if (Cache::has($cooldownKey)) {
                return;
            }
            Cache::put($cooldownKey, true, 5);

            $systemPrompt = BotConfig::get('ai_system_prompt', '');
            $maxTokens    = BotConfig::getInt('ai_max_tokens', 400);
            $temperature  = BotConfig::getFloat('ai_temperature', 0.7);

            // Cap input message to ~150 tokens — prevents huge pastes from burning budget
            $aiInput = mb_substr($userMessage, 0, 600);

            // FAQ context — built once, cached 5 min, capped to ~250 tokens
            $faqContext = Cache::remember('faq_ai_context', 300, function () {
                $totalCap   = 1000;
                $perItemCap = 300;
                $built      = '';

                foreach (FaqMenu::active()
                    ->whereNotNull('content')
                    ->where('command', '!=', '0')
                    ->where('command', '!=', '99')
                    ->orderBy('sort_order')
                    ->get(['title', 'content']) as $m)
                {
                    $text = preg_replace('/Ketik \*[^*]+\*[^\n]*/iu', '', $m->content);
                    $text = preg_replace('/[─]+/u', '', $text);
                    $text = preg_replace('/\*([^*\n]+)\*/u', '$1', $text);
                    $text = preg_replace('/\n{3,}/', "\n\n", trim($text));
                    $item = "### {$m->title}\n" . mb_substr($text, 0, $perItemCap);

                    if (mb_strlen($built) + mb_strlen($item) > $totalCap) {
                        break;
                    }
                    $built .= ($built ? "\n\n" : '') . $item;
                }

                return $built;
            });

            // Keyword-based FAQ injection — only inject when message is about DlmF/kursus
            // Saves ~250 tokens for all non-business questions (greetings, general topics, etc.)
            if ($faqContext) {
                static $dlmfKeywords = [
                    'kursus','les','belajar','daftar','pendaftaran','harga','biaya','bayar',
                    'program','jadwal','jerman','german','deutsch','minfara','dlmf','fara',
                    'au pair','goethe','reguler','private','bandung','flexilearn','alumni',
                    'tutor','native','kelas','a1','a2','b1','b2','online','offline',
                    'sertifikat','ujian','garansi','bundling',
                ];
                $lowerInput = strtolower($aiInput);
                $hasDlmfIntent = !empty(array_filter($dlmfKeywords, fn($kw) => str_contains($lowerInput, $kw)));

                if ($hasDlmfIntent) {
                    $systemPrompt .= "\n\nINFO DlmF:\n" . $faqContext;
                }
            }

            // History — last 4 turns, trimmed to cap tokens
            $historyKey = 'chat_history_' . md5($from);
            $history    = Cache::get($historyKey, []);

            // Response cache — for stateless (no history) identical questions, skip AI entirely
            // Key includes prompt hash so it auto-invalidates when CMS changes system prompt
            $respCacheKey = 'ai_resp_' . md5(strtolower(trim($aiInput)) . '|' . substr(md5($systemPrompt), 0, 8));
            $cachedReply  = empty($history) ? Cache::get($respCacheKey) : null;

            if ($cachedReply !== null) {
                $footer = BotConfig::get('footer_ai', '');
                $reply  = $cachedReply . ($footer ? "\n" . $footer : '');
                $mode   = 'ai';
                $tokens = 0;
                Log::info('AI response served from cache', ['key' => substr($respCacheKey, -8)]);
            } else {
                $result       = null;
                $usedProvider = null;
                $failedErrors = [];

                foreach ($providerOrder as $provider) {
                    if (!$this->providerHasKey($provider)) {
                        continue;
                    }
                    $service = $this->resolveService($provider);
                    if (!$service) {
                        continue;
                    }
                    $result = $service->chat($aiInput, $systemPrompt, $maxTokens, $temperature, $history);
                    if ($result['success']) {
                        $usedProvider = $provider;
                        break;
                    }
                    $failedErrors[] = $result['error'] ?? 'unknown';
                    Log::warning("AI provider {$provider} failed, trying next", ['error' => $result['error'] ?? 'unknown']);
                }

                if ($result && $result['success']) {
                    $footer = BotConfig::get('footer_ai', '');
                    $reply  = $result['reply'] . ($footer ? "\n" . $footer : '');
                    $mode   = 'ai';
                    $tokens = $result['tokens'] ?? null;

                    if (!empty($failedErrors)) {
                        Log::info("AI fallback resolved via {$usedProvider}", [
                            'failed_providers' => count($failedErrors),
                            'errors'           => $failedErrors,
                        ]);
                    }

                    // Cache stateless responses 15 min — same question from different users = 0 tokens
                    if (empty($history)) {
                        Cache::put($respCacheKey, $result['reply'], 900);
                    }

                    // Save trimmed exchange — caps history tokens without losing conversational context
                    $history[] = ['role' => 'user',      'content' => mb_substr($userMessage, 0, 300)];
                    $history[] = ['role' => 'assistant',  'content' => mb_substr($result['reply'], 0, 250)];
                    if (count($history) > 4) {
                        $history = array_slice($history, -4);
                    }
                    Cache::put($historyKey, $history, 1800);
                } else {
                    $mode   = 'error';
                    $tokens = is_array($result) ? ($result['tokens'] ?? null) : null;

                    $allQuotaExceeded = !empty($failedErrors)
                        && count(array_filter($failedErrors, fn($e) => $e !== 'quota_exceeded')) === 0;

                    if ($allQuotaExceeded) {
                        $reply = "Maaf, semua layanan MinFara AI sedang overload saat ini 😅\n\nCoba lagi beberapa menit lagi ya! Atau ketik *99* untuk chat langsung dengan admin kami.";
                    } else {
                        $reply = BotConfig::get('fallback_message', "Entschuldigung! 🙏 Coba lagi nanti atau ketik *99*.");
                    }
                }
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
                    Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
