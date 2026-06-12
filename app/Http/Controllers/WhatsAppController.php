<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Models\BotConfig;
use App\Models\FaqMenu;
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

        // Ignore messages older than 60 seconds — prevents replaying history on WAHA reconnect
        $msgTimestamp = $payload['timestamp'] ?? $payload['_data']['timestamp'] ?? null;
        if ($msgTimestamp !== null) {
            $ts = (int) $msgTimestamp;
            if ($ts > 1_000_000_000_000) $ts = (int) ($ts / 1000); // milliseconds → seconds
            if ((time() - $ts) > 60) {
                return response('OK', 200);
            }
        }

        $rawFrom   = $payload['from'] ?? null;
        $rawInput  = trim($payload['body'] ?? '');
        $messageId = $payload['id'] ?? null;

        if (empty($rawFrom) || !is_string($rawFrom) || strlen($rawFrom) > 100 || $rawInput === '') {
            return response('OK', 200);
        }

        // Only respond to direct messages — block groups (@g.us), broadcasts, newsletters
        $atPos  = strrpos($rawFrom, '@');
        $suffix = $atPos !== false ? substr($rawFrom, $atPos) : '@c.us';
        if (!in_array($suffix, ['@c.us', '@lid'], strict: true)) {
            return response('OK', 200);
        }

        $chatId = $rawFrom;
        $from   = preg_replace('/@.*$/', '', $rawFrom);

        $contactName = $payload['notifyName']
            ?? $payload['_data']['notifyName']
            ?? $payload['_data']['pushname']
            ?? null;
        if ($contactName) {
            $contactName = trim($contactName);
            $contactName = $contactName === '' ? null : $contactName;
        }

        $ipAddress = $request->ip();

        // Cache-based dedup using WAHA messageId — prevents double-dispatch when WAHA retries the webhook
        if (!empty($messageId)) {
            $dedupKey = 'wh_msg_' . md5((string) $messageId);
            if (Cache::has($dedupKey)) {
                return response('OK', 200);
            }
            Cache::put($dedupKey, true, 30);
        }

        $command       = strtolower($rawInput);
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
            $this->whatsapp->startTyping($chatId);
            $this->faqReply($chatId, $from, $menu, $rawInput, $contactName, $ipAddress);
        } else {
            ProcessAiReply::dispatch($chatId, $from, $rawInput, $contactName, $ipAddress);
        }

        return response('OK', 200);
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

        if ($this->whatsapp->sendMessage($chatId, $content)) {
            $this->whatsapp->stopTyping($chatId);
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
}
