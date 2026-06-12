<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class WahaController extends Controller
{
    private function cfg(): array
    {
        return [
            'url'     => BotConfig::get('waha_url')     ?: config('services.waha.url',     'http://localhost:3000'),
            'key'     => BotConfig::get('waha_api_key') ?: config('services.waha.api_key', ''),
            'session' => BotConfig::get('waha_session') ?: config('services.waha.session', 'default'),
        ];
    }

    #[OA\Get(
        path: '/api/waha/status',
        operationId: 'wahaStatus',
        summary: 'Status sesi WAHA',
        description: 'Mengecek apakah sesi WAHA aktif dan WhatsApp terkoneksi.',
        tags: ['WAHA'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status sesi WAHA',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'connected', type: 'boolean', example: true),
                    new OA\Property(property: 'status', type: 'string', example: 'WORKING',
                        enum: ['WORKING', 'DISCONNECTED', 'UNKNOWN']),
                ])
            ),
        ]
    )]
    public function status(): JsonResponse
    {
        $cfg = $this->cfg();

        try {
            $res = Http::timeout(5)
                ->withHeaders(['X-Api-Key' => $cfg['key']])
                ->get("{$cfg['url']}/api/sessions/{$cfg['session']}");

            if ($res->ok()) {
                $data = $res->json();
                return response()->json([
                    'connected' => ($data['status'] ?? '') === 'WORKING',
                    'status'    => $data['status'] ?? 'UNKNOWN',
                ]);
            }
        } catch (\Exception) {}

        return response()->json(['connected' => false, 'status' => 'DISCONNECTED']);
    }

    #[OA\Post(
        path: '/api/waha/send',
        operationId: 'wahaSend',
        summary: 'Kirim pesan WhatsApp manual',
        description: 'Kirim pesan teks ke nomor WhatsApp tertentu via WAHA. Berguna untuk testing pengiriman.',
        tags: ['WAHA'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'message'],
                properties: [
                    new OA\Property(property: 'phone', type: 'string', example: '0812345678',
                        description: 'Nomor WA (format 08xx / 628xx / +628xx)'),
                    new OA\Property(property: 'message', type: 'string', example: 'Halo dari MinFara!',
                        description: 'Pesan yang akan dikirim (maks 4096 karakter)'),
                    new OA\Property(property: 'mode', type: 'string',
                        enum: ['normal', 'faq', 'ai', 'direct'], description: 'Mode kirim (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hasil pengiriman pesan',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'reply', type: 'string', nullable: true,
                        example: 'Pesan berhasil dikirim ke 628123456789'),
                    new OA\Property(property: 'body', type: 'object'),
                    new OA\Property(property: 'elapsed', type: 'integer', example: 450),
                    new OA\Property(property: 'message', type: 'string', example: 'Pesan berhasil dikirim!'),
                ])
            ),
            new OA\Response(
                response: 422,
                description: 'Nomor WA tidak valid',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: false),
                    new OA\Property(property: 'message', type: 'string',
                        example: 'Nomor WA tidak valid (minimal 7 digit)'),
                ])
            ),
        ]
    )]
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'phone'   => ['required', 'string'],
            'message' => ['required', 'string', 'max:4096'],
            'mode'    => ['nullable', 'string', 'in:normal,faq,ai,direct'],
        ]);

        $cfg   = $this->cfg();
        $phone = preg_replace('/\D/', '', $request->phone);

        if (strlen($phone) < 7) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WA tidak valid (minimal 7 digit)',
            ], 422);
        }

        $chatId = $phone . '@c.us';
        $start  = microtime(true);

        try {
            $res = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $cfg['key']])
                ->post("{$cfg['url']}/api/sendText", [
                    'session' => $cfg['session'],
                    'chatId'  => $chatId,
                    'text'    => $request->message,
                ]);

            $elapsed = (int) round((microtime(true) - $start) * 1000);

            return response()->json([
                'success' => $res->successful(),
                'status'  => $res->status(),
                'reply'   => $res->successful() ? 'Pesan berhasil dikirim ke ' . $phone : null,
                'body'    => $res->json(),
                'elapsed' => $elapsed,
                'message' => $res->successful() ? 'Pesan berhasil dikirim!' : 'Gagal mengirim pesan.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status'  => 0,
                'reply'   => null,
                'body'    => ['error' => $e->getMessage()],
                'elapsed' => (int) round((microtime(true) - $start) * 1000),
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    #[OA\Post(
        path: '/api/waha/reconnect',
        operationId: 'wahaReconnect',
        summary: 'Reconnect sesi WAHA',
        description: 'Menghentikan lalu memulai ulang sesi WAHA WhatsApp (stop + start).',
        tags: ['WAHA'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hasil reconnect',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'WAHA reconnect berhasil'),
                ])
            ),
            new OA\Response(
                response: 500,
                description: 'Error saat reconnect',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: false),
                    new OA\Property(property: 'message', type: 'string'),
                ])
            ),
        ]
    )]
    public function reconnect(): JsonResponse
    {
        $cfg = $this->cfg();

        try {
            // Stop then restart session
            Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $cfg['key']])
                ->delete("{$cfg['url']}/api/sessions/{$cfg['session']}/stop");

            $res = Http::timeout(10)
                ->withHeaders(['X-Api-Key' => $cfg['key']])
                ->post("{$cfg['url']}/api/sessions/{$cfg['session']}/start");

            return response()->json([
                'success' => $res->successful(),
                'message' => $res->successful() ? 'WAHA reconnect berhasil' : 'Gagal reconnect WAHA',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
