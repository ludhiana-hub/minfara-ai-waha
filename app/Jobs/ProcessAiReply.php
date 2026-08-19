<?php

namespace App\Jobs;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\PausedContact;
use App\Models\WhatsappLog;
use App\Services\Ai\AiRequest;
use App\Services\Ai\AiRouter;
use App\Services\Ai\KnowledgeRetrievalService;
use App\Services\Ai\Support\ErrorNormalizer;
use App\Services\Ai\Support\MessageComplexity;
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
    public int $timeout = 175;

    private const SHORT_REPLY_MAX_TOKENS = 500;
    private const SHORT_REPLY_HINT = 'CATATAN UNTUK PESAN INI: pertanyaan singkat/faktual — balas SANGAT ringkas (1-3 baris), langsung ke inti, tanpa basa-basi atau penjelasan tambahan yang tidak diminta.';
    private const LONG_REPLY_HINT  = 'CATATAN UNTUK PESAN INI: pertanyaan ini butuh penjelasan lebih detail — boleh sedikit lebih lengkap dari batas normal (tetap dalam batas token yang tersedia), tapi tetap terstruktur dan jangan bertele-tele.';

    public function __construct(
        private readonly string  $chatId,
        private readonly string  $from,
        private readonly string  $userMessage,
        private readonly ?string $contactName,
        private readonly ?string $ipAddress,
    ) {}

    public function handle(WhatsAppService $whatsapp, AiRouter $router): void
    {
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

        // Wraps everything from here to send in try/finally so stopTyping is guaranteed even
        // if the AI call throws — it used to only be guaranteed around the send block, leaving
        // the chat stuck showing "typing…" for up to the full job timeout on an exception.
        try {
            $this->handleInner($whatsapp, $router, $fromHash);
        } finally {
            $whatsapp->stopTyping($this->chatId);
        }
    }

    private function handleInner(WhatsAppService $whatsapp, AiRouter $router, string $fromHash): void
    {
        $aiEnabled   = BotConfig::getBool('ai_enabled', true);
        $aiAvailable = $aiEnabled && $router->hasAnyUsableProvider('chat');

        if (!$aiAvailable) {
            $lowerMessage = strtolower(trim($this->userMessage));
            if (strlen($lowerMessage) <= 3 && !is_numeric($lowerMessage)) {
                $mainMenu = FaqMenu::active()->where('command', '0')->first();
                if ($mainMenu) {
                    $content = $mainMenu->content ?? $whatsapp->buildMainMenu();
                    $whatsapp->sendMessage($this->chatId, $content);
                } else {
                    $whatsapp->sendMessage($this->chatId, "Hallo! 👋 Ketik *0* untuk melihat menu atau *99* untuk cara checkout.");
                }

                return;
            }
            $reply  = BotConfig::get('fallback_message', "Hallo! 👋 Perintah tidak dikenali.\n\nKetik *0* untuk melihat menu lengkap atau *99* untuk cara checkout di website.");
            $mode   = 'error';
            $tokens = null;
        } else {
            $cooldownKey = 'ai_cooldown_' . md5($this->from);
            if (!Cache::add($cooldownKey, true, 5)) {
                $whatsapp->sendMessage($this->chatId, BotConfig::get('rate_limit_message', 'Mohon tunggu sebentar sebelum mengirim pesan lagi 🙏'));
                return;
            }

            $systemPrompt = BotConfig::get('ai_system_prompt', '');
            // Defense-in-depth clamp — CMS validates on save, but this also protects values
            // that were already out of range in the DB before validation existed.
            $maxTokens    = max(100, min(2000, BotConfig::getInt('ai_max_tokens', 500)));
            $temperature  = max(0.0, min(2.0, BotConfig::getFloat('ai_temperature', 0.7)));

            $aiInput = mb_substr($this->userMessage, 0, 600);

            // RAG: inject only the top-K knowledge chunks relevant to THIS question,
            // instead of the full static digest — saves tokens for the actual answer and
            // scales as the knowledge base grows. Falls back to the full faq_digest
            // (pre-computed by BuildFaqDigestJob) whenever embeddings are unavailable or
            // the index is empty, so the bot never regresses below today's behavior.
            $factsBlock = app(KnowledgeRetrievalService::class)->retrieve($aiInput);
            if ($factsBlock === '') {
                $factsBlock = BotConfig::get('faq_digest', '');
            }
            if ($factsBlock) {
                $systemPrompt .= "\n\n" . $factsBlock;
            }

            // Sales coaching notes apply to every reply regardless of topic — always
            // appended in full (small, style/technique guidance, not a retrievable fact).
            $coachingNotes = BotConfig::get('sales_coaching_notes', '');
            if ($coachingNotes) {
                $systemPrompt .= "\n\n=== CATATAN GAYA & TEKNIK SALES (internal, jangan disebut ke customer) ===\n" . $coachingNotes;
            }

            // Per-message complexity heuristic (cheap, no extra AI call) — right-sizes
            // max_tokens + adds a one-off instruction for THIS turn only. Ambiguous/default
            // messages fall through unchanged, so this can only make a reply cheaper, never
            // worse, than before.
            $complexity = MessageComplexity::classify($aiInput);
            if ($complexity === MessageComplexity::SHORT) {
                $maxTokens = min($maxTokens, self::SHORT_REPLY_MAX_TOKENS);
                $systemPrompt .= "\n\n" . self::SHORT_REPLY_HINT;
            } elseif ($complexity === MessageComplexity::LONG) {
                $systemPrompt .= "\n\n" . self::LONG_REPLY_HINT;
            }

            $historyKey = 'chat_history_' . md5($this->from);
            $history    = Cache::get($historyKey, []);

            $respCacheKey = 'ai_resp_' . md5($this->from . '|' . strtolower(trim($aiInput)) . '|' . substr(md5($systemPrompt), 0, 8));
            $cachedReply  = empty($history) ? Cache::get($respCacheKey) : null;

            if ($cachedReply !== null) {
                $footer = BotConfig::get('footer_ai', '');
                $reply  = $cachedReply . ($footer ? "\n" . $footer : '');
                $mode   = 'ai';
                $tokens = 0;
                Log::info('AI response served from cache', ['key' => substr($respCacheKey, -8)]);
            } else {
                $result = $router->run(
                    AiRequest::make('chat', $aiInput)
                        ->withSystem($systemPrompt)
                        ->withHistory($history)
                        ->withMaxTokens($maxTokens)
                        ->withTemperature($temperature)
                );

                if ($result->success) {
                    $footer = BotConfig::get('footer_ai', '');
                    $reply  = $result->reply . ($footer ? "\n" . $footer : '');
                    $mode   = 'ai';
                    $tokens = $result->tokens;

                    if ($result->attempts > 1) {
                        Log::info("AI fallback resolved via {$result->provider}/{$result->model}", [
                            'attempts'    => $result->attempts,
                            'attempt_log' => $result->attemptLog,
                        ]);
                    }

                    if (empty($history)) {
                        Cache::put($respCacheKey, $result->reply, 900);
                    }

                    // ai_history_turns: jumlah TOTAL entry (user+assistant) yang disimpan —
                    // default 4 (= 2 pertukaran) mempertahankan perilaku sebelum AiRouter persis.
                    $historyTurns = BotConfig::getInt('ai_history_turns', 4);

                    // Lock + re-read the LATEST history right before writing (not the snapshot
                    // taken before the AI call, which can be many seconds stale) — otherwise two
                    // messages from the same user in quick succession can race: both read the
                    // same starting history, and whichever writes last silently drops the other's
                    // turn. Losing this write only means the next turn has one message less of
                    // context — not worth failing (and retrying/losing) the whole reply over, so
                    // a lock timeout is caught and swallowed rather than left to bubble up.
                    try {
                        Cache::lock('lock_' . $historyKey, 10)->block(5, function () use ($historyKey, $historyTurns, $result) {
                            $latestHistory   = Cache::get($historyKey, []);
                            $latestHistory[] = ['role' => 'user',      'content' => mb_substr($this->userMessage, 0, 300)];
                            $latestHistory[] = ['role' => 'assistant', 'content' => mb_substr($result->reply, 0, 250)];
                            if (count($latestHistory) > $historyTurns) {
                                $latestHistory = array_slice($latestHistory, -$historyTurns);
                            }
                            Cache::put($historyKey, $latestHistory, 1800);
                        });
                    } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                        Log::warning('ProcessAiReply: history lock timed out, skipping history write', ['from' => $this->from]);
                    }
                } else {
                    $mode   = 'error';
                    $tokens = null;

                    // Perpanjang cooldown ke 60 detik agar fallback tidak spam
                    Cache::put($cooldownKey, true, 60);

                    $reply = $result->allErrorsAreQuotaExceeded()
                        ? "Maaf, semua layanan MinFara AI sedang overload saat ini 😅\n\nCoba lagi beberapa menit lagi ya! Atau ketik *99* untuk lihat cara checkout, atau hubungi admin di https://wa.me/6289647897616 kalau mendesak."
                        : BotConfig::get('fallback_message', "Entschuldigung! 🙏 Coba lagi nanti atau ketik *99*.");

                    Log::error('AI reply failed — all providers/models exhausted', [
                        'from'           => $this->from,
                        'chatId'         => $this->chatId,
                        'total_attempts' => $result->attempts,
                        'attempt_log'    => $result->attemptLog,
                        'correlation_id' => $result->correlationId,
                    ]);
                }
            }
        }

        if (empty($reply)) {
            return;
        }

        // Last-resort net: catches a leaked chain-of-thought reply regardless of which path
        // produced it (fresh AI call, stale response cache from before this filter existed,
        // or a code path that bypasses the provider-level check) — never relay raw reasoning
        // to the customer.
        if ($mode === 'ai' && (ErrorNormalizer::looksLikeRawReasoning($reply) || ErrorNormalizer::looksLikeDisclosureLeak($reply))) {
            Log::warning('Blocked reply leak at final egress', [
                'from' => $this->from,
                'kind' => ErrorNormalizer::looksLikeDisclosureLeak($reply) ? ErrorNormalizer::DISCLOSURE_LEAK : ErrorNormalizer::REASONING_LEAK,
            ]);
            Cache::forget($respCacheKey ?? '');
            $reply = BotConfig::get('fallback_message', "Maaf kak, boleh diulang pertanyaannya? 🙏 Atau ketik *0* untuk lihat menu FAQ.");
            $mode  = 'error';
        }

        $reply = $this->sanitizeWhatsappText($reply);

        $recentDuplicate = WhatsappLog::where('from_number', $this->from)
            ->where('message_in', substr($this->userMessage, 0, 1000))
            ->where('mode', $mode)
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        if (Cache::has('human_takeover_' . $fromHash) || PausedContact::isPaused($this->from)) {
            Log::info('ProcessAiReply: aborted before send — human takeover active', ['from' => $this->from]);
            return;
        }

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
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAiReply failed permanently', [
            'chatId'  => $this->chatId,
            'from'    => $this->from,
            'message' => $exception->getMessage(),
        ]);

        try {
            if (Cache::has('human_takeover_' . md5($this->from)) || PausedContact::isPaused($this->from)) {
                Log::info('ProcessAiReply::failed — skip fallback, human takeover active', ['from' => $this->from]);
                return;
            }

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

        // Tanda pisah panjang (—) gaya artikel/esai → koma, biar kerasa natural kayak chat
        // WA beneran. Safety net di luar instruksi system prompt — model kadang tetap kepakai
        // gaya lama walau udah dilarang.
        $text = str_replace([' — ', '—'], [', ', ','], $text);

        return $text;
    }
}
