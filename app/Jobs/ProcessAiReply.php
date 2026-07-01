<?php

namespace App\Jobs;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\PausedContact;
use App\Models\WhatsappLog;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\NvidiaService;
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
    public int $timeout = 150;

    // Fallback models tried on the SAME provider (same API key) before moving to the next provider —
    // handles model-specific outages (blocked/deprecated model) that switching providers wouldn't fix.
    private const FALLBACK_MODELS = [
        'groq'       => ['gemma2-9b-it', 'llama-3.1-8b-instant'],
        'gemini'     => ['gemini-1.5-flash-8b'],
        'openrouter' => [], // openrouter/free already routes across ~24+ free models internally
        'nvidia'     => [],
    ];

    // Hard cap on total HTTP attempts across all providers/models/retries so a chain of timeouts can't
    // blow past the job timeout (150s). See ProcessAiReply plan notes for the worst-case math.
    private const MAX_TOTAL_ATTEMPTS = 6;

    // How long a provider that exhausted all its models is skipped before being retried again ("rebound").
    private const PROVIDER_COOLDOWN_SECONDS = 300;

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
        NvidiaService     $nvidia,
    ): void {
        // Cek human takeover — cek cache dulu (instan) lalu DB sebagai fallback.
        // Cache di-set oleh handleOwnerReply() sebelum DB write untuk eliminasi race condition.
        $fromHash = md5($this->from);
        if (Cache::has('human_takeover_' . $fromHash) || PausedContact::isPaused($this->from)) {
            Log::info('ProcessAiReply: skip — human takeover active', ['from' => $this->from]);
            return;
        }

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

            // Inject pre-computed FAQ digest — built by BuildFaqDigestJob, always up-to-date
            $faqDigest = BotConfig::get('faq_digest', '');
            if ($faqDigest) {
                $systemPrompt .= "\n\n" . $faqDigest;
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
                $result        = null;
                $usedProvider  = null;
                $usedModel     = null;
                $failedErrors  = [];
                $attemptLog    = [];
                $totalAttempts = 0;

                $services = [
                    'gemini'     => $gemini,
                    'groq'       => $groq,
                    'openrouter' => $openrouter,
                    'nvidia'     => $nvidia,
                ];

                $primaryModelFor = [
                    'groq'       => fn() => BotConfig::get('groq_model')       ?: config('services.groq.model', 'llama-3.3-70b-versatile'),
                    'gemini'     => fn() => BotConfig::get('gemini_model')     ?: config('services.gemini.model', 'gemini-2.0-flash'),
                    'openrouter' => fn() => BotConfig::get('openrouter_model') ?: config('services.openrouter.model', 'openrouter/free'),
                    // Deliberately NOT BotConfig::get('nvidia_model') — that key is the analytics model
                    // (ConversationAnalysisService). Customer chat always uses the fast config default.
                    'nvidia'     => fn() => config('services.nvidia.model', 'meta/llama-3.1-8b-instruct'),
                ];

                // Providers that recently failed on every model get skipped for a while — unless EVERY
                // configured provider is currently marked unhealthy, in which case ignore the marks
                // entirely and try the normal order anyway (never skip straight to the fallback message).
                $unhealthyProviders = array_values(array_filter($providerOrder, fn($p) =>
                    Cache::has('ai_provider_unhealthy_' . $p)
                ));
                $allUnhealthy = count($providerOrder) > 0 && count($unhealthyProviders) === count($providerOrder);

                foreach ($providerOrder as $provider) {
                    if ($totalAttempts >= self::MAX_TOTAL_ATTEMPTS) {
                        break;
                    }
                    if (!$this->providerHasKey($provider)) {
                        continue;
                    }
                    $service = $services[$provider] ?? null;
                    if (!$service) {
                        continue;
                    }
                    if (!$allUnhealthy && Cache::has('ai_provider_unhealthy_' . $provider)) {
                        Log::info("AI provider {$provider} skipped — cooldown active");
                        continue;
                    }

                    $models = array_values(array_unique(array_merge(
                        [$primaryModelFor[$provider]()],
                        self::FALLBACK_MODELS[$provider] ?? []
                    )));

                    // Stays true only if every model in this provider's list actually got a fair try —
                    // false if the total-attempt cap cut it short, so we don't unfairly mark it unhealthy.
                    $providerFullyTried = true;

                    foreach ($models as $model) {
                        if ($totalAttempts >= self::MAX_TOTAL_ATTEMPTS) {
                            $providerFullyTried = false;
                            break 2;
                        }

                        $attempt = $service->withModel($model)->chat($aiInput, $systemPrompt, $maxTokens, $temperature, $history);
                        $totalAttempts++;

                        if ($attempt['success']) {
                            $result       = $attempt;
                            $usedProvider = $provider;
                            $usedModel    = $model;
                            $attemptLog[$provider][$model] = 'success';
                            Cache::forget('ai_provider_unhealthy_' . $provider);
                            break 2;
                        }

                        $error = $attempt['error'] ?? 'unknown';

                        // Only a connection timeout is worth retrying — quota/model/key errors won't
                        // change on an immediate retry.
                        if ($error === 'Connection timeout' && $totalAttempts < self::MAX_TOTAL_ATTEMPTS) {
                            usleep(1_000_000);
                            $retry = $service->withModel($model)->chat($aiInput, $systemPrompt, $maxTokens, $temperature, $history);
                            $totalAttempts++;

                            if ($retry['success']) {
                                $result       = $retry;
                                $usedProvider = $provider;
                                $usedModel    = $model;
                                $attemptLog[$provider][$model] = 'success (after retry)';
                                Cache::forget('ai_provider_unhealthy_' . $provider);
                                break 2;
                            }
                            $attemptLog[$provider][$model] = "{$error} (retried, still failed: " . ($retry['error'] ?? 'unknown') . ')';
                        } else {
                            $attemptLog[$provider][$model] = $error;
                        }

                        $failedErrors[] = $error;
                        Log::warning("AI provider {$provider} model {$model} failed, trying next", ['error' => $error]);
                    }

                    if (!$result && $providerFullyTried) {
                        Cache::put('ai_provider_unhealthy_' . $provider, true, self::PROVIDER_COOLDOWN_SECONDS);
                        Log::info("AI provider {$provider} marked unhealthy for " . self::PROVIDER_COOLDOWN_SECONDS . 's — all models exhausted');
                    }

                    if ($result) {
                        break;
                    }
                }

                if ($result && $result['success']) {
                    $footer = BotConfig::get('footer_ai', '');
                    $reply  = $result['reply'] . ($footer ? "\n" . $footer : '');
                    $mode   = 'ai';
                    $tokens = $result['tokens'] ?? null;

                    if (!empty($failedErrors)) {
                        Log::info("AI fallback resolved via {$usedProvider}/{$usedModel}", [
                            'failed_attempts' => count($failedErrors),
                            'attempt_log'     => $attemptLog,
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

                    Log::error('AI reply failed — all providers/models exhausted', [
                        'from'              => $this->from,
                        'chatId'            => $this->chatId,
                        'total_attempts'    => $totalAttempts,
                        'attempt_log'       => $attemptLog,
                        'skipped_unhealthy' => $unhealthyProviders,
                        'fail_open'         => $allUnhealthy,
                    ]);
                }
            }
        }

        if (empty($reply)) {
            $whatsapp->stopTyping($this->chatId);
            return;
        }

        $reply = $this->sanitizeWhatsappText($reply);

        $recentDuplicate = WhatsappLog::where('from_number', $this->from)
            ->where('message_in', substr($this->userMessage, 0, 1000))
            ->where('mode', $mode)
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            $whatsapp->stopTyping($this->chatId);
            return;
        }

        if (Cache::has('human_takeover_' . $fromHash) || PausedContact::isPaused($this->from)) {
            Log::info('ProcessAiReply: aborted before send — human takeover active', ['from' => $this->from]);
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

    private function sanitizeWhatsappText(string $text): string
    {
        // [teks](url) → url saja (WhatsApp tidak render markdown link)
        $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', '$2', $text);

        // **bold** → *bold* (WhatsApp pakai single asterisk)
        $text = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $text);

        // __italic__ → _italic_
        $text = preg_replace('/__(.+?)__/s', '_$1_', $text);

        return $text;
    }

    private function providerHasKey(string $provider): bool
    {
        return match ($provider) {
            'gemini'     => !empty(BotConfig::get('gemini_api_key') ?: config('services.gemini.key', '')),
            'groq'       => !empty(BotConfig::get('groq_api_key') ?: config('services.groq.key', '')),
            'openrouter' => !empty(BotConfig::get('openrouter_api_key') ?: config('services.openrouter.key', '')),
            'nvidia'     => !empty(BotConfig::get('nvidia_api_key') ?: config('services.nvidia.key', '')),
            default      => false,
        };
    }
}
