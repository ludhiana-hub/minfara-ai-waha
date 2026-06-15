<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class ConfigController extends Controller
{
    #[OA\Get(
        path: '/api/config',
        operationId: 'configIndex',
        summary: 'Ambil semua konfigurasi bot',
        description: "Mengembalikan semua BotConfig yang dikelompokkan berdasarkan field 'group'.",
        tags: ['Config'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Konfigurasi berhasil diambil, dikelompokkan per group',
                content: new OA\JsonContent(
                    type: 'object',
                    example: ['general' => [['id' => 1, 'key' => 'bot_name', 'value' => 'MinFara AI', 'group' => 'general']]]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $grouped = BotConfig::all()
            ->groupBy('group')
            ->map(fn ($items) => $items->values());

        return response()->json($grouped);
    }

    #[OA\Put(
        path: '/api/config',
        operationId: 'configUpdate',
        summary: 'Update konfigurasi bot',
        description: 'Menyimpan key-value pairs ke BotConfig. Kunci sensitif (ai_api_key, waha_api_key, gemini_api_key, groq_api_key, openrouter_api_key) tidak akan diupdate jika value kosong. Cache di-flush setelah update.',
        tags: ['Config'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                description: 'Map dari config key ke value-nya',
                example: ['bot_name' => 'MinFara AI', 'ai_mode' => 'gemini', 'bot_greeting' => 'halo,hai,hi']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Konfigurasi berhasil disimpan',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Konfigurasi berhasil disimpan'),
                ])
            ),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $data = $request->all();

        // Keys that should never be saved as empty string (keep existing value)
        $sensitiveKeys = [
            'ai_api_key', 'waha_api_key',
            'gemini_api_key', 'groq_api_key', 'openrouter_api_key', 'nvidia_api_key',
        ];

        foreach ($data as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            // Skip non-scalar values (arrays, objects) to prevent type errors
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            if (in_array($key, $sensitiveKeys, true) && ($value === null || $value === '')) {
                continue;
            }

            BotConfig::set($key, (string) ($value ?? ''));
        }

        Cache::flush();

        return response()->json(['message' => 'Konfigurasi berhasil disimpan']);
    }
}
