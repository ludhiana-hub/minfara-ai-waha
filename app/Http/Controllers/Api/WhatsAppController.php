<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiReply;
use App\Jobs\ReConfigureWahaWebhookJob;
use App\Models\BotConfig;
use App\Models\FaqMenu;
use App\Models\PausedContact;
use App\Models\WhatsappLog;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppService $whatsapp,
    ) {}

    #[OA\Post(
        path: '/api/whatsapp/webhook',
        operationId: 'wahaWebhook',
        summary: 'Terima webhook dari WAHA',
        description: 'Endpoint publik yang dipanggil oleh WAHA saat ada pesan masuk WhatsApp. Pesan diproses dan dijawab oleh bot AI atau FAQ. Tidak memerlukan autentikasi.',
        tags: ['Webhook'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['event', 'payload'],
                properties: [
                    new OA\Property(property: 'event', type: 'string', example: 'message'),
                    new OA\Property(property: 'payload', type: 'object', properties: [
                        new OA\Property(property: 'from', type: 'string', example: '628123456789@c.us'),
                        new OA\Property(property: 'body', type: 'string', example: 'Halo, info pendaftaran dong'),
                        new OA\Property(property: 'fromMe', type: 'boolean', example: false),
                        new OA\Property(property: 'id', type: 'string', example: 'ABCDEF123456'),
                        new OA\Property(property: 'timestamp', type: 'integer', example: 1718000000),
                        new OA\Property(property: 'notifyName', type: 'string', example: 'Budi', nullable: true),
                    ]),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook diterima',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string', example: 'OK')
                )
            ),
        ]
    )]
    public function webhook(Request $request): Response
    {
        $event   = $request->input('event');
        $payload = $request->input('payload', []);

        Log::info('webhook:in', [
            'event'    => $event,
            'type'     => $payload['_data']['type'] ?? $payload['type'] ?? 'MISSING',
            'from'     => $payload['from'] ?? 'MISSING',
            'id_type'  => gettype($payload['id'] ?? null),
            'has_body' => isset($payload['body']),
        ]);

        // Ketika WAHA reconnect, ia kirim session.status: WORKING.
        // Kita queue ulang waha:ensure-webhook agar message.any selalu aktif setelah reconnect.
        if ($event === 'session.status') {
            if (($payload['status'] ?? '') === 'WORKING') {
                ReConfigureWahaWebhookJob::dispatch()->delay(now()->addSeconds(3));
                Log::info('webhook: WAHA WORKING — queued webhook re-configuration');
            }
            return response('OK', 200);
        }

        // message.any diperlukan untuk NOWEB/Baileys agar menangkap pesan manual dari HP owner
        if (!in_array($event, ['message', 'message.any'], true)) {
            return response('OK', 200);
        }

        // Deteksi owner reply: cek fromMe field ATAU cocokkan dengan nomor WA bot sendiri.
        // NOWEB/Baileys kadang kirim fromMe: false meski pesan dari akun sendiri (known bug).
        $isFromMe = ($payload['fromMe'] ?? false) === true;
        if (!$isFromMe) {
            $ownNumber = BotConfig::get('waha_own_number', '');
            $sender    = $payload['from'] ?? '';
            if ($ownNumber && (
                $sender === $ownNumber ||
                (str_contains($ownNumber, '@') && str_starts_with($sender, explode('@', $ownNumber)[0]))
            )) {
                $isFromMe = true;
                Log::info('webhook: fromMe=false but sender matches own number — treating as owner reply', [
                    'from'      => $sender,
                    'ownNumber' => $ownNumber,
                ]);
            }
        }
        if ($isFromMe) {
            $this->handleOwnerReply($payload);
            return response('OK', 200);
        }

        // Skip pesan media (gambar, stiker, audio) — bot hanya proses teks.
        // Pakai `hasMedia` (field WAHA yang stabil di semua engine), BUKAN `_data.type`
        // yang hanya ada di WEBJS. NOWEB tidak punya `_data.type` → filter lama
        // men-drop SEMUA pesan NOWEB.
        if (($payload['hasMedia'] ?? false) === true) {
            Log::info('webhook:drop — has media');
            return response('OK', 200);
        }

        // Ignore messages older than 60 seconds — prevents replaying history on WAHA reconnect
        $msgTimestamp = $payload['timestamp'] ?? $payload['_data']['timestamp'] ?? null;
        if ($msgTimestamp !== null) {
            $ts = (int) $msgTimestamp;
            if ($ts > 1_000_000_000_000) $ts = (int) ($ts / 1000); // milliseconds → seconds
            if ((time() - $ts) > 60) {
                Log::info('webhook:drop — message too old', ['age_seconds' => time() - $ts]);
                return response('OK', 200);
            }
        }

        $rawFrom      = $payload['from'] ?? null;
        $rawInput     = trim($payload['body'] ?? '');
        // NOWEB (Baileys) may send id as an object {id, _serialized, fromMe, remote} — extract string
        $messageIdRaw = $payload['id'] ?? null;
        $messageId    = is_string($messageIdRaw)
            ? $messageIdRaw
            : ($messageIdRaw['_serialized'] ?? $messageIdRaw['id'] ?? null);

        if (empty($rawFrom) || !is_string($rawFrom) || strlen($rawFrom) > 100 || $rawInput === '') {
            return response('OK', 200);
        }

        // Only respond to direct messages — block groups (@g.us), broadcasts, newsletters
        // @s.whatsapp.net is Baileys/NOWEB format for individual chats (same as @c.us)
        $atPos  = strrpos($rawFrom, '@');
        $suffix = $atPos !== false ? substr($rawFrom, $atPos) : '@c.us';
        if (!in_array($suffix, ['@c.us', '@lid', '@s.whatsapp.net'], strict: true)) {
            Log::info('webhook:drop — from suffix not allowed', ['suffix' => $suffix]);
            return response('OK', 200);
        }

        $chatId = $rawFrom;
        $from   = preg_replace('/@.*$/', '', $rawFrom);

        // NOWEB (Baileys) simpan nama di `_data.pushName` (kapital N) — beda dari
        // WEBJS yang pakai `_data.pushname` (lowercase). Cek keduanya.
        $contactName = $payload['notifyName']
            ?? $payload['_data']['notifyName']
            ?? $payload['_data']['pushName']
            ?? $payload['_data']['pushname']
            ?? $payload['_data']['verifiedName']
            ?? null;
        if ($contactName) {
            $contactName = trim($contactName);
            $contactName = $contactName === '' ? null : $contactName;
        }

        $ipAddress = $request->ip();

        // Cache-based dedup using WAHA messageId — prevents double-dispatch when WAHA retries the webhook
        if (!empty($messageId)) {
            $dedupKey = 'wh_msg_' . md5($messageId);
            if (!Cache::add($dedupKey, true, 30)) {
                Log::info('webhook:drop — dedup hit', ['id_suffix' => substr($messageId, -8)]);
                return response('OK', 200);
            }
        }

        // Cek cache dulu (instan) lalu DB — cache di-set oleh handleOwnerReply sebelum DB write
        // untuk menangkap race condition di FAQ/endChat path yang dieksekusi synchronously.
        $fromHash = md5($from);
        if (Cache::has('human_takeover_' . $fromHash) || PausedContact::isPaused($from)) {
            Log::info('webhook:drop — human takeover active', ['from' => $from]);
            return response('OK', 200);
        }

        Log::info('webhook:processing', ['from' => $from, 'input' => substr($rawInput, 0, 40)]);

        $command       = strtolower($rawInput);
        $greetingWords = array_map('trim', explode(',', BotConfig::get('bot_greeting', 'halo,hai,hi,hello,hallo,mulai,start,menu,help')));
        if (in_array($command, $greetingWords, strict: true)) {
            $command = '0';
        }

        if ($command === '99') {
            $this->endChat($chatId, $from, $contactName, $ipAddress);
            return response('OK', 200);
        }

        // Simpan timestamp pesan customer untuk baseline di hasOwnerRepliedRecently
        Cache::put('last_customer_ts_' . md5($chatId), time(), now()->addHours(2));

        // Simpan last customer message — dipakai proactive resume job setelah takeover berakhir
        Cache::put('pending_resume_chatid_' . md5($from), $chatId, now()->addHours(2));
        Cache::put('pending_resume_body_' . md5($from), $rawInput, now()->addHours(2));
        Cache::put('pending_resume_name_' . md5($from), $contactName, now()->addHours(2));

        $menu = FaqMenu::active()->where('command', $command)->first();

        if ($menu) {
            $this->whatsapp->startTyping($chatId);
            $this->faqReply($chatId, $from, $menu, $rawInput, $contactName, $ipAddress);
        } else {
            ProcessAiReply::dispatch($chatId, $from, $rawInput, $contactName, $ipAddress);
        }

        return response('OK', 200);
    }

    private function handleOwnerReply(array $payload): void
    {
        // Skip echo-back: bot API-sent messages also arrive as fromMe:true via message.any.
        // NOWEB: sendText response id (plain string) ≠ message.any id._serialized (full JID key).
        // Check ALL available id variants, then fall back to body fingerprint.
        $rawId      = $payload['id'] ?? null;
        $idsToCheck = [];
        if (is_array($rawId)) {
            if (isset($rawId['_serialized']) && $rawId['_serialized'] !== '') {
                $idsToCheck[] = (string) $rawId['_serialized'];
            }
            if (isset($rawId['id']) && $rawId['id'] !== '') {
                $idsToCheck[] = (string) $rawId['id'];
            }
        } elseif (is_string($rawId) && $rawId !== '') {
            $idsToCheck[] = $rawId;
        }
        foreach ($idsToCheck as $echoId) {
            if (Cache::has('bot_sent_' . md5($echoId))) {
                Log::info('handleOwnerReply: skip — bot-sent echo (id match)', ['id' => $echoId]);
                return;
            }
        }
        // Body fingerprint fallback: handles total ID format mismatch between sendText and message.any
        $echoBody   = $payload['body'] ?? '';
        $echoChatId = $payload['chatId'] ?? ($payload['to'] ?? ($payload['from'] ?? ''));
        if ($echoBody !== '' && $echoChatId !== '') {
            if (Cache::has('bot_sent_body_' . md5($echoChatId . mb_substr($echoBody, 0, 100)))) {
                Log::info('handleOwnerReply: skip — bot-sent echo (body match)', [
                    'body_prefix' => mb_substr($echoBody, 0, 40),
                ]);
                return;
            }
        }

        // Ambil nomor penerima (customer) dari payload
        // WEBJS: customer ada di payload.to (payload.from = own number)
        // NOWEB/Baileys: customer ada di payload.from (key.remoteJid = chat partner), payload.to = null
        $ownNumber  = BotConfig::get('waha_own_number', '');
        $ownPhone   = $ownNumber ? preg_replace('/@.*$/', '', $ownNumber) : '';

        $rawTo = $payload['to']
            ?? $payload['_data']['to']
            ?? $payload['_data']['key']['remoteJid']
            ?? null;

        // NOWEB/Baileys fallback: jika to tidak ada, pakai from jika bukan nomor bot sendiri
        if ((empty($rawTo) || !is_string($rawTo)) && isset($payload['from'])) {
            $rawFrom   = $payload['from'];
            $fromPhone = preg_replace('/@.*$/', '', $rawFrom);
            if (!$ownPhone || $fromPhone !== $ownPhone) {
                $rawTo = $rawFrom;
                Log::info('handleOwnerReply: NOWEB mode — using payload.from as customer JID', [
                    'customer' => $rawTo,
                ]);
            }
        }

        if (empty($rawTo) || !is_string($rawTo) || strlen($rawTo) > 100) {
            Log::warning('handleOwnerReply: could not extract recipient — pause NOT set', [
                'has_to' => isset($payload['to']),
                'from'   => $payload['from'] ?? null,
                'own'    => $ownNumber,
            ]);
            return;
        }

        $atPos  = strrpos($rawTo, '@');
        $suffix = $atPos !== false ? substr($rawTo, $atPos) : '';

        // Hanya proses direct message, skip group
        if (!in_array($suffix, ['@c.us', '@lid', '@s.whatsapp.net'], strict: true)) {
            return;
        }

        $from = preg_replace('/@.*$/', '', $rawTo);

        $contactName = $payload['notifyName']
            ?? $payload['_data']['notifyName']
            ?? $payload['_data']['pushName']
            ?? $payload['_data']['pushname']
            ?? null;
        if ($contactName) {
            $contactName = trim($contactName) ?: null;
        }

        $pauseMinutes = (int) BotConfig::get('human_takeover_minutes', '10');

        // Cek sebelum set cache — dispatch resume job hanya saat takeover PERTAMA kali di-set.
        // Jika owner kirim beberapa pesan, hanya 1 proactive resume job yang terdaftar.
        $fromHash      = md5($from);
        $alreadyPaused = Cache::has('human_takeover_' . $fromHash) || PausedContact::isPaused($from);

        // Set cache flag INSTAN sebelum DB write — job CHECK #1 baca cache lebih cepat dari DB.
        Cache::put('human_takeover_' . $fromHash, true, now()->addMinutes($pauseMinutes));

        PausedContact::pauseContact($from, $contactName, $pauseMinutes);

        Log::info('webhook:human-takeover', [
            'from'           => $from,
            'paused_minutes' => $pauseMinutes,
        ]);

        // Dispatch proactive AI resume setelah pause berakhir (hanya pada takeover pertama)
        if (!$alreadyPaused) {
            $resumeChatId = Cache::get('pending_resume_chatid_' . $fromHash);
            $resumeBody   = Cache::get('pending_resume_body_' . $fromHash);
            $resumeName   = Cache::get('pending_resume_name_' . $fromHash);

            if ($resumeChatId && $resumeBody) {
                ProcessAiReply::dispatch($resumeChatId, $from, $resumeBody, $resumeName ?? $contactName, null)
                    ->delay(now()->addMinutes($pauseMinutes)->addSeconds(5));
                Log::info('handleOwnerReply: scheduled proactive AI resume', [
                    'from'          => $from,
                    'delay_minutes' => $pauseMinutes,
                ]);
            }
        }

        try {
            WhatsappLog::create([
                'from_number'  => $from,
                'contact_name' => $contactName,
                'ip_address'   => null,
                'message_in'   => $payload['body'] ?? null,
                'message_out'  => null,
                'mode'         => 'human_takeover',
                'responded_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('handleOwnerReply: failed to log', ['error' => $e->getMessage()]);
        }
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

        $this->whatsapp->startTyping($chatId);
        if ($this->whatsapp->sendMessage($chatId, $reply)) {
            $this->whatsapp->stopTyping($chatId);
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

        try {
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
        } finally {
            $this->whatsapp->stopTyping($chatId);
        }
    }
}
