<?php

namespace App\Jobs;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\WhatsappLog;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\OpenRouterService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAiReply implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $backoff = 10;
    public int $timeout = 120;

    public function __construct(
        private readonly string  $chatId,
        private readonly string  $from,
        private readonly string  $userMessage,
        private readonly ?string $contactName,
        private readonly ?string $ipAddress,
    ) {}

    public function handle(
        WhatsAppService   $whatsapp,
        GeminiService     $gemini,
        GroqService       $groq,
        OpenRouterService $openrouter,
    ): void {
        if (!$whatsapp->isSessionWorking()) {
            Log::warning('ProcessAiReply: WAHA session not WORKING, releasing for retry', [
                'chatId' => $this->chatId,
                'from'   => $this->from,
            ]);
            $this->release(30);
            return;
        }

        $whatsapp->startTyping($this->chatId);

        $aiEnabled     = BotConfig::getBool('ai_enabled', true);
        $orderStr      = BotConfig::get('ai_provider_order', 'groq,gemini,openrouter');
        $providerOrder = array_filter(array_map('trim', explode(',', $orderStr)));
        $aiAvailable   = $aiEnabled && !empty(array_filter($providerOrder, fn($p) => $this->providerHasKey($p)));

        if (!$aiAvailable) {
            $lowerMessage = strtolower(trim($this->userMessage));
            if (strlen($lowerMessage) <= 3 && !is_numeric($lowerMessage)) {
                $mainMenu = FaqMenu::active()->where('command', '0')->first();
                if ($mainMenu) {
                    $content = $mainMenu->content ?? $whatsapp->buildMainMenu();
                    $whatsapp->sendMessage($this->chatId, $content);
                } else {
                    $whatsapp->sendMessage($this->chatId, "Hallo! 👋 Ketik *0* untuk melihat menu atau *99* untuk hubungi admin.");
                }

                $whatsapp->stopTyping($this->chatId);
                return;
            }
            $reply  = BotConfig::get('fallback_message', "Hallo! 👋 Perintah tidak dikenali.\n\nKetik *0* untuk melihat menu lengkap atau *99* untuk chat dengan admin.");
            $mode   = 'error';
            $tokens = null;
        } else {
            $cooldownKey = 'ai_cooldown_' . md5($this->from);
            if (!Cache::add($cooldownKey, true, 5)) {
                $whatsapp->stopTyping($this->chatId);
                $whatsapp->sendMessage($this->chatId, BotConfig::get('rate_limit_message', 'Mohon tunggu sebentar sebelum mengirim pesan lagi 🙏'));
                return;
            }

            $systemPrompt = BotConfig::get('ai_system_prompt', '');
            $maxTokens    = BotConfig::getInt('ai_max_tokens', 1024);
            $temperature  = BotConfig::getFloat('ai_temperature', 0.7);

            $aiInput = mb_substr($this->userMessage, 0, 600);

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

            if ($faqContext) {
                static $dlmfKeywords = [
                    'kursus','les','belajar','daftar','pendaftaran','harga','biaya','bayar',
                    'program','jadwal','jerman','german','deutsch','minfara','dlmf','fara',
                    'au pair','goethe','reguler','private','bandung','flexilearn','alumni',
                    'tutor','native','kelas','a1','a2','b1','b2','online','offline',
                    'sertifikat','ujian','garansi','bundling',
                ];
                $lowerInput    = strtolower($aiInput);
                $hasDlmfIntent = !empty(array_filter($dlmfKeywords, fn($kw) => str_contains($lowerInput, $kw)));

                if ($hasDlmfIntent) {
                    $systemPrompt .= "\n\nINFO DlmF:\n" . $faqContext;
                }
            }

            $historyKey = 'chat_history_' . md5($this->from);
            $history    = Cache::get($historyKey, []);

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

                $services = [
                    'gemini'     => $gemini,
                    'groq'       => $groq,
                    'openrouter' => $openrouter,
                ];

                foreach ($providerOrder as $provider) {
                    if (!$this->providerHasKey($provider)) {
                        continue;
                    }
                    $service = $services[$provider] ?? null;
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

                    if (empty($history)) {
                        Cache::put($respCacheKey, $result['reply'], 900);
                    }

                    $history[] = ['role' => 'user',      'content' => mb_substr($this->userMessage, 0, 300)];
                    $history[] = ['role' => 'assistant',  'content' => mb_substr($result['reply'], 0, 250)];
                    if (count($history) > 4) {
                        $history = array_slice($history, -4);
                    }
                    Cache::put($historyKey, $history, 1800);
                } else {
                    $mode   = 'error';
                    $tokens = is_array($result) ? ($result['tokens'] ?? null) : null;

                    // Perpanjang cooldown ke 60 detik agar fallback tidak spam
                    Cache::put($cooldownKey, true, 60);

                    $allQuotaExceeded = !empty($failedErrors)
                        && count(array_filter($failedErrors, fn($e) => $e !== 'quota_exceeded')) === 0;

                    $reply = $allQuotaExceeded
                        ? "Maaf, semua layanan MinFara AI sedang overload saat ini 😅\n\nCoba lagi beberapa menit lagi ya! Atau ketik *99* untuk chat langsung dengan admin kami."
                        : BotConfig::get('fallback_message', "Entschuldigung! 🙏 Coba lagi nanti atau ketik *99*.");
                }
            }
        }

        if (empty($reply)) {
            $whatsapp->stopTyping($this->chatId);
            return;
        }

        $recentDuplicate = WhatsappLog::where('from_number', $this->from)
            ->where('message_in', substr($this->userMessage, 0, 1000))
            ->where('mode', $mode)
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            $whatsapp->stopTyping($this->chatId);
            return;
        }

        try {
            if ($whatsapp->sendMessage($this->chatId, $reply)) {
                try {
                    WhatsappLog::create([
                        'from_number'    => $this->from,
                        'contact_name'   => $this->contactName,
                        'ip_address'     => $this->ipAddress,
                        'message_in'     => substr($this->userMessage, 0, 1000),
                        'message_out'    => substr($reply, 0, 60000),
                        'mode'           => $mode,
                        'ai_tokens_used' => $tokens ?? null,
                        'responded_at'   => now(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
                }
            }
        } finally {
            $whatsapp->stopTyping($this->chatId);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAiReply failed permanently', [
            'chatId'  => $this->chatId,
            'from'    => $this->from,
            'message' => $exception->getMessage(),
        ]);

        try {
            $whatsapp = app(WhatsAppService::class);
            $fallback = BotConfig::get('fallback_message', "Entschuldigung! 🙏 Coba lagi nanti atau ketik *99*.");
            $whatsapp->sendMessage($this->chatId, $fallback);
            $whatsapp->stopTyping($this->chatId);
        } catch (\Exception) {}
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
}
