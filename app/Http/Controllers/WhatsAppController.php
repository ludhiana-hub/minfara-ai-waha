<?php

namespace App\Http\Controllers;

use App\Models\FaqMenu;
use App\Models\WhatsappLog;
use App\Services\GeminiService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppController extends Controller
{
    private const GREETING_ALIASES = ['halo', 'hai', 'hi', 'hello', 'hallo', 'mulai', 'start', 'menu', 'help'];

    public function __construct(
        private readonly WhatsAppService $whatsapp,
        private readonly GeminiService   $gemini,
    ) {}

    /**
     * Handle incoming WAHA webhook (POST).
     * Always returns 200 so WAHA does not retry.
     */
    public function handle(Request $request): Response
    {
        $event   = $request->input('event');
        $payload = $request->input('payload');

        // Only process incoming text messages
        if ($event !== 'message') {
            return response('OK', 200);
        }

        if (($payload['fromMe'] ?? false) === true) {
            return response('OK', 200);
        }

        // type ada di _data.type pada WAHA versi baru, fallback ke payload.type
        $type = $payload['_data']['type'] ?? $payload['type'] ?? '';
        if ($type !== 'chat') {
            return response('OK', 200);
        }

        $from     = $payload['from'] ?? null;           // e.g. "628xxx@c.us"
        $rawInput = trim($payload['body'] ?? '');
        $messageId = $payload['id'] ?? null;            // WAHA message ID untuk deduplication

        // Validate input
        if (empty($from) || !is_string($from) || strlen($from) > 50 || $rawInput === '') {
            return response('OK', 200);
        }

        // Deduplication by message ID (most reliable) + timestamp window
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

        if (in_array($command, self::GREETING_ALIASES, strict: true)) {
            $command = '0';
        }

        // Special handling untuk end chat (command 99)
        if ($command === '99') {
            $this->endChat($from, $rawInput);
            return response('OK', 200);
        }

        $menu = FaqMenu::active()->where('command', $command)->first();

        if ($menu) {
            $this->faqReply($from, $menu, $rawInput);
        } else {
            $this->aiReply($from, $rawInput);
        }

        return response('OK', 200);
    }

    private function endChat(string $from, string $rawInput): void
    {
        // Double-check: jangan kirim kalau sudah pernah dalam 3 detik terakhir
        $recentDuplicate = WhatsappLog::where('from_number', $from)
            ->where('message_in', '99')
            ->where('mode', 'end_chat')
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        $reply = "📞 *Chat Berakhir*\n\n"
            . "Terima kasih telah menghubungi MinFara! 🙏\n"
            . "Tim admin kami siap membantu Anda lebih lanjut.\n\n"
            . "WA Admin: *+62 896-4789-7616*\n"
            . "Email: hello@mitfara.com\n"
            . "Website: https://mitfara.com\n\n"
            . "Ketik *0* untuk kembali ke menu utama.";

        if ($this->whatsapp->sendMessage($from, $reply)) {
            try {
                WhatsappLog::create([
                    'from_number'  => $from,
                    'message_in'   => '99',
                    'message_out'  => $reply,
                    'mode'         => 'end_chat',
                    'responded_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
            }
        } else {
            \Illuminate\Support\Facades\Log::error('Failed to send WhatsApp message', ['from' => $from, 'mode' => 'end_chat']);
        }
    }

    private function faqReply(string $from, FaqMenu $menu, string $rawInput): void
    {
        $content = $menu->content ?? $this->whatsapp->buildMainMenu();

        // Double-check: jangan kirim kalau sudah pernah dalam 3 detik terakhir (catch webhook duplicate)
        $recentDuplicate = WhatsappLog::where('from_number', $from)
            ->where('message_in', $rawInput)
            ->where('message_out', $content)
            ->where('responded_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($recentDuplicate) {
            return;
        }

        if ($this->whatsapp->sendMessage($from, $content)) {
            try {
                WhatsappLog::create([
                    'from_number'  => $from,
                    'message_in'   => substr($rawInput, 0, 1000),
                    'message_out'  => $content,
                    'mode'         => 'faq',
                    'responded_at' => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
            }
        } else {
            \Illuminate\Support\Facades\Log::error('Failed to send WhatsApp message', ['from' => $from, 'mode' => 'faq']);
        }
    }

    private function aiReply(string $from, string $userMessage): void
    {
        $reply = null;
        $mode  = 'error';
        $tokens = null;

        // Check if user has timeout (30 seconds idle) - reset to menu
        $lastMessage = WhatsappLog::where('from_number', $from)
            ->where('responded_at', '!=', null)
            ->latest('responded_at')
            ->first();

        $isSessionTimeout = $lastMessage && $lastMessage->responded_at->diffInSeconds(now()) > 30;

        // Jika command tidak valid / tidak dikenal, suggest menu daripada AI
        $lowerMessage = strtolower(trim($userMessage));
        $isLikelyInvalidCommand = strlen($lowerMessage) <= 3 && !is_numeric($lowerMessage);

        if ($isSessionTimeout) {
            // Session timeout - reset ke menu utama
            $reply = "Sesi Anda telah berakhir. Silakan mulai dari awal dengan mengetik *0* untuk menu utama.";
            $mode  = 'error';
        } elseif ($isLikelyInvalidCommand) {
            // Likely salah command, jangan kirim ke AI
            $reply = "Hallo! 👋 Perintah tidak dikenali.\n\n"
                . "Ketik *0* untuk melihat menu lengkap DlmF,\n"
                . "atau ketik *99* untuk chat langsung dengan admin kami.";
            $mode  = 'error';
        } elseif (empty(config('services.gemini.key'))) {
            $reply = "Hallo! 👋 Perintah tidak dikenali.\n\n"
                . "Ketik *0* untuk melihat menu lengkap DlmF,\n"
                . "atau ketik *99* untuk chat langsung dengan admin kami.";
            $mode  = 'error';
        } else {
            $result = $this->gemini->chat($userMessage, $this->buildSystemPrompt());

            if ($result['success']) {
                $reply = $result['reply'] . $this->whatsapp->aiFooter();
                $mode  = 'ai';
                $tokens = $result['tokens'] ?? null;
            } else {
                $reply = "Entschuldigung! 🙏 MinFara AI sedang tidak dapat memproses pertanyaanmu saat ini.\n\n"
                    . "Silakan coba beberapa saat lagi, atau ketik *99* untuk langsung terhubung\n"
                    . "dengan admin MinFara kami. Danke! 😊\n"
                    . "─────────────────\n"
                    . "_MinFara AI_ 🤖 _· Deutsch Lernen mit Fara_\n"
                    . "Ketik *0* menu utama | *99* hubungi admin";
                $tokens = $result['tokens'] ?? null;
            }
        }

        if (!empty($reply)) {
            // Double-check: jangan kirim kalau sudah pernah dalam 3 detik terakhir (catch webhook duplicate)
            $recentDuplicate = WhatsappLog::where('from_number', $from)
                ->where('message_in', substr($userMessage, 0, 1000))
                ->where('mode', $mode)
                ->where('responded_at', '>=', now()->subSeconds(3))
                ->exists();

            if ($recentDuplicate) {
                return;
            }

            if ($this->whatsapp->sendMessage($from, $reply)) {
                try {
                    WhatsappLog::create([
                        'from_number'    => $from,
                        'message_in'     => substr($userMessage, 0, 1000),
                        'message_out'    => $reply,
                        'mode'           => $mode,
                        'ai_tokens_used' => $tokens,
                        'responded_at'   => now(),
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to create WhatsappLog', ['error' => $e->getMessage()]);
                }
            } else {
                \Illuminate\Support\Facades\Log::error('Failed to send WhatsApp message', ['from' => $from, 'mode' => $mode]);
            }
        }
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Kamu adalah MinFara AI, asisten virtual berbasis kecerdasan buatan milik
Deutsch Lernen mit Fara (DlmF) — platform kursus Bahasa Jerman online & offline
terpercaya di Bandung, Indonesia.
Website: https://mitfara.com | WA Admin: +62 896-4789-7616

IDENTITAS:
- Nama: MinFara AI
- Peran: AI assistant resmi DlmF, siap menjawab pertanyaan 24/7
- Karakter: ramah, antusias, suportif — seperti teman yang tahu segalanya tentang DlmF
- Kamu BUKAN pengganti admin manusia. Untuk keputusan pendaftaran, pembayaran,
  atau jadwal spesifik, selalu arahkan ke admin via *99*.

INFORMASI BISNIS:
- Program: Kelas Reguler A1-B1 (online & offline), Private Grammatik,
  Private Persiapan Goethe, Sprachkurs mit Muttersprachler (native speaker),
  Private Kinder (anak), Deutsch FlexiLearn (asinkronus), Program Au Pair
- Harga Online: mulai Rp1.499.000 (reguler), mulai Rp895.000 (private), mulai Rp149.000 (FlexiLearn)
- Harga Offline: mulai Rp2.099.000 (reguler), mulai Rp1.400.000 (private)
- Lokasi offline: Jl. Terusan Sari Asih No. 76, Sarijadi, Bandung
- Platform online: Microsoft Teams
- Garansi: free class jika belum lulus ujian (S&K berlaku)
- Tutor bersertifikasi, ada native speaker, 5.000+ alumni
- Bundling A1+B1 hemat hingga Rp1.000.000

ATURAN MENJAWAB:
1. Perkenalkan diri sebagai "MinFara AI" jika user baru pertama kali
2. Jawab dalam Bahasa Indonesia yang ramah, hangat, dan profesional
3. Maksimal 3 paragraf, singkat dan langsung ke inti
4. Jika tidak yakin info spesifik → sarankan hubungi admin
5. Jika di luar topik DlmF/bahasa Jerman → tolak sopan, arahkan ke menu
6. Selalu akhiri: "Ketik *0* untuk menu utama atau *99* untuk chat langsung dengan admin."
7. Panggil user dengan "Kamu"
8. Boleh sisipkan kata Jerman sederhana sesekali (contoh: "Sehr gut! 👍")
PROMPT;
    }
}
