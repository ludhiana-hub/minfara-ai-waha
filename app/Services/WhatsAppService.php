<?php

namespace App\Services;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $url;
    private string $apiKey;
    private string $session;

    public function __construct()
    {
        $this->url     = BotConfig::get('waha_url')     ?: config('whatsapp.base_url', 'http://localhost:3000');
        $this->apiKey  = BotConfig::get('waha_api_key') ?: config('whatsapp.api_key', '');
        $this->session = BotConfig::get('waha_session') ?: config('whatsapp.session', 'default');
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        if (!str_contains($chatId, '@')) {
            $chatId .= '@c.us';
        }

        // Throttle sekali per upaya kirim logis — retry @lid tidak kena jeda ganda.
        app(WhatsAppSendThrottle::class)->waitBeforeSend();

        $sent = $this->doSend($chatId, $text);

        // @lid accounts: WAHA WEBJS sends `from` without suffix so we default to @c.us,
        // but @lid accounts can't receive via @c.us — retry with @lid suffix.
        if (!$sent && str_ends_with($chatId, '@c.us')) {
            $lidId = substr($chatId, 0, -4) . '@lid';
            $sent  = $this->doSend($lidId, $text);
        }

        return $sent;
    }

    public function startTyping(string $chatId): void
    {
        $this->doPresence($chatId, 'startTyping');
    }

    public function stopTyping(string $chatId): void
    {
        $this->doPresence($chatId, 'stopTyping');
    }

    private function doPresence(string $chatId, string $action): void
    {
        try {
            Http::timeout(3)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/{$action}", [
                    'session' => $this->session,
                    'chatId'  => $chatId,
                ]);
        } catch (\Exception) {}
    }

    private function doSend(string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sendText", [
                    'session' => $this->session,
                    'chatId'  => $chatId,
                    'text'    => $text,
                ]);

            if ($response->failed()) {
                Log::error('WAHA sendText failed', [
                    'chatId' => $chatId,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return false;
            }

            // Track message ID so handleOwnerReply can skip echo-back events (fromMe:true via API).
            // NOWEB: sendText returns plain id string but message.any event has {_serialized, id} object.
            // Store ALL variants so handleOwnerReply catches the mismatch regardless of format.
            $raw    = $response->json('id');
            $botIds = [];
            if (is_array($raw)) {
                if (isset($raw['_serialized']) && $raw['_serialized'] !== '') {
                    $botIds[] = (string) $raw['_serialized'];
                }
                if (isset($raw['id']) && $raw['id'] !== '') {
                    $botIds[] = (string) $raw['id'];
                }
            } elseif (is_string($raw) && $raw !== '') {
                $botIds[] = $raw;
            }
            foreach (array_unique($botIds) as $botId) {
                Cache::put('bot_sent_' . md5($botId), true, now()->addMinutes(20));
            }
            // Body fingerprint fallback: catches total ID format mismatch (NOWEB edge case)
            Cache::put('bot_sent_body_' . md5($chatId . mb_substr($text, 0, 100)), true, now()->addSeconds(60));

            // Track last bot reply timestamp per chatId — used as accurate baseline in hasOwnerRepliedRecently
            Cache::put('last_bot_ts_' . md5($chatId), time(), now()->addHours(1));

            return true;

        } catch (\Exception $e) {
            Log::error('WAHA sendMessage exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function hasOwnerRepliedRecently(string $chatId, int $withinSeconds = 300): bool
    {
        try {
            // chatId contains @ which must be percent-encoded in URL path (@→%40)
            $response = Http::timeout(4)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->url}/api/{$this->session}/chats/" . urlencode($chatId) . "/messages", [
                    'limit'         => 15,
                    'downloadMedia' => false,
                ]);

            // Fallback to alternative WAHA endpoint format (older versions / different builds)
            if (!$response->ok()) {
                Log::debug('hasOwnerRepliedRecently: primary endpoint failed, trying fallback', [
                    'status' => $response->status(),
                ]);
                $response = Http::timeout(4)
                    ->withHeaders(['X-Api-Key' => $this->apiKey])
                    ->get("{$this->url}/api/messages", [
                        'session'       => $this->session,
                        'chatId'        => $chatId,
                        'limit'         => 15,
                        'downloadMedia' => false,
                    ]);
            }

            // WAHA bisa return plain array [...] ATAU object {messages: [...], total: N}
            $rawData  = $response->json() ?? [];
            $messages = (is_array($rawData) && array_is_list($rawData))
                ? $rawData
                : ($rawData['messages'] ?? array_values($rawData));

            Log::debug('hasOwnerRepliedRecently: WAHA response', [
                'chatId'     => $chatId,
                'httpStatus' => $response->status(),
                'msgCount'   => is_array($messages) ? count($messages) : '(not array)',
                'sample'     => is_array($messages) ? array_slice($messages, 0, 1) : null,
            ]);

            if (!$response->ok()) {
                return false;
            }

            // Baseline akurat: "apakah owner reply SETELAH customer message terakhir datang?"
            // last_customer_ts_ di-set saat webhook menerima pesan customer (TTL 2 jam).
            // Fallback ke last_bot_ts_ (kapan bot terakhir kirim), lalu window default.
            $lastCustomerTs = Cache::get('last_customer_ts_' . md5($chatId));
            $lastBotTs      = Cache::get('last_bot_ts_' . md5($chatId));
            $cutoffTime     = $lastCustomerTs ?? $lastBotTs ?? (time() - $withinSeconds);

            foreach ($messages as $msg) {
                if (!($msg['fromMe'] ?? false)) {
                    continue;
                }

                // Exclude messages sent by the bot via API (tracked in cache by doSend)
                $rawId = $msg['id'] ?? null;
                $msgId = is_array($rawId)
                    ? ($rawId['_serialized'] ?? $rawId['id'] ?? null)
                    : $rawId;
                if ($msgId && Cache::has('bot_sent_' . md5((string) $msgId))) {
                    continue;
                }

                // Normalize timestamp (WAHA may return seconds or milliseconds)
                $ts = (int) ($msg['timestamp'] ?? 0);
                if ($ts > 1_000_000_000_000) {
                    $ts = (int) ($ts / 1000);
                }

                if ($ts >= $cutoffTime) {
                    Log::info('hasOwnerRepliedRecently: owner manual reply detected', [
                        'chatId' => $chatId,
                        'msgId'  => $msgId,
                        'age'    => time() - $ts,
                    ]);
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::warning('hasOwnerRepliedRecently: WAHA fetch failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public function isSessionWorking(): bool
    {
        return $this->sessionStatus() === 'WORKING';
    }

    public function sessionStatus(): string
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->get("{$this->url}/api/sessions/{$this->session}");

            return $response->ok() ? ($response->json('status') ?? 'UNKNOWN') : 'UNREACHABLE';
        } catch (\Exception) {
            return 'UNREACHABLE';
        }
    }

    public function startSession(): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sessions/{$this->session}/start");

            if ($response->successful()) {
                return true;
            }

            // Session sudah STARTING/WORKING — /start ditolak 422, tapi bukan kegagalan nyata.
            if ($response->status() === 422 && str_contains($response->body(), 'already started')) {
                $status = $this->sessionStatus();
                Log::info('WAHA startSession: session already active', ['status' => $status]);

                return in_array($status, ['WORKING', 'STARTING'], true);
            }

            Log::error('WAHA startSession failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WAHA startSession exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function restartSession(): bool
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sessions/{$this->session}/restart");

            if (!$response->successful()) {
                Log::error('WAHA restartSession failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WAHA restartSession exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function logoutSession(): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->post("{$this->url}/api/sessions/{$this->session}/logout");

            if (!$response->successful()) {
                Log::error('WAHA logoutSession failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WAHA logoutSession exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Pulihkan sesi berdasarkan status saat ini.
     * FAILED/STARTING → restart; STOPPED → start; streak tinggi + FAILED → logout lalu start.
     */
    public function recoverSession(bool $forceLogout = false): bool
    {
        $status = $this->sessionStatus();

        if ($status === 'WORKING') {
            Cache::forget('waha_starting_since');

            return true;
        }

        if ($status === 'SCAN_QR_CODE') {
            return false;
        }

        if ($forceLogout) {
            $this->logoutSession();
            sleep(3);

            return $this->startSession();
        }

        if ($status === 'STOPPED') {
            return $this->startSession();
        }

        // STARTING adalah status transisi normal — beri masa tenggang 45 detik sebelum
        // dianggap macet, supaya sesi yang memang sedang benar-benar starting tidak
        // diinterupsi restart di tengah jalan (lihat WhatsAppController::webhook()).
        if ($status === 'STARTING') {
            $since = Cache::get('waha_starting_since');

            if (!$since) {
                Cache::put('waha_starting_since', now(), now()->addMinutes(5));

                return true;
            }

            if (now()->diffInSeconds($since) < 45) {
                return true;
            }
        }

        return $this->restartSession();
    }

    public function buildMainMenu(): string
    {
        return FaqMenu::active()->where('command', '0')->value('content')
            ?? "Hallo! Ketik *1* untuk Program Kursus atau *99* untuk hubungi admin.";
    }
}
