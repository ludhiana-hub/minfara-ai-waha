<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    
    #[OA\Get(
        path: '/api/logs',
        operationId: 'logIndex',
        summary: 'List log percakapan dengan pagination',
        description: 'Mengembalikan log percakapan WhatsApp dengan filter berdasarkan mode, pencarian teks, dan rentang tanggal. Dipaginasi.',
        tags: ['Log'],
        parameters: [
            new OA\Parameter(name: 'mode', in: 'query', required: false,
                description: 'Filter berdasarkan mode respons bot',
                schema: new OA\Schema(type: 'string', enum: ['all', 'ai', 'faq', 'end_chat', 'error', 'human_takeover'])),
            new OA\Parameter(name: 'search', in: 'query', required: false,
                description: 'Cari berdasarkan nomor WA, nama kontak, atau pesan masuk',
                schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false,
                description: 'Filter tanggal mulai (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false,
                description: 'Filter tanggal akhir (Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false,
                description: 'Jumlah item per halaman (maks 100)',
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated log percakapan',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'from_number', type: 'string', example: '628123456789'),
                            new OA\Property(property: 'contact_name', type: 'string', nullable: true),
                            new OA\Property(property: 'message_in', type: 'string'),
                            new OA\Property(property: 'message_out', type: 'string', nullable: true),
                            new OA\Property(property: 'mode', type: 'string', example: 'ai'),
                            new OA\Property(property: 'ai_tokens_used', type: 'integer', nullable: true),
                            new OA\Property(property: 'responded_at', type: 'string',
                                format: 'date-time', nullable: true),
                        ])
                    ),
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                ])
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = WhatsappLog::query();

        if ($request->mode && $request->mode !== 'all') {
            $query->where('mode', $request->mode);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('from_number',  'like', "%{$s}%")
                  ->orWhere('message_in', 'like', "%{$s}%")
                  ->orWhere('contact_name', 'like', "%{$s}%");
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);

        return response()->json($query->latest()->paginate($perPage));
    }

    #[OA\Get(
        path: '/api/logs/stats',
        operationId: 'logStats',
        summary: 'Statistik percakapan hari ini',
        description: 'Mengembalikan jumlah percakapan dan token AI yang digunakan hari ini, dikelompokkan berdasarkan mode.',
        tags: ['Log'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistik hari ini',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'today_total', type: 'integer', example: 87),
                    new OA\Property(property: 'ai_count', type: 'integer', example: 52),
                    new OA\Property(property: 'faq_count', type: 'integer', example: 30),
                    new OA\Property(property: 'end_count', type: 'integer', example: 4),
                    new OA\Property(property: 'error_count', type: 'integer', example: 1),
                    new OA\Property(property: 'total_tokens', type: 'integer', example: 14200),
                ])
            ),
        ]
    )]
    public function stats(): JsonResponse
    {
        $today = today();

        $counts = WhatsappLog::whereDate('created_at', $today)
            ->selectRaw('mode, count(*) as total')
            ->groupBy('mode')
            ->pluck('total', 'mode');

        $tokens = (int) WhatsappLog::whereDate('created_at', $today)
            ->sum('ai_tokens_used');

        return response()->json([
            'today_total'  => (int) $counts->sum(),
            'ai_count'     => (int) ($counts['ai']       ?? 0),
            'faq_count'    => (int) ($counts['faq']      ?? 0),
            'end_count'    => (int) ($counts['end_chat'] ?? 0),
            'error_count'  => (int) ($counts['error']    ?? 0),
            'total_tokens' => $tokens,
        ]);
    }

    #[OA\Get(
        path: '/api/logs/stream',
        operationId: 'logStream',
        summary: 'Real-time log via Server-Sent Events (SSE)',
        description: "Endpoint SSE yang mendorong log percakapan baru setiap 2 detik. Koneksi tetap terbuka sampai client disconnect. Gunakan EventSource di sisi klien.",
        tags: ['Log'],
        responses: [
            new OA\Response(
                response: 200,
                description: "Stream SSE — setiap baris 'data: {...}' berisi objek log",
                content: new OA\MediaType(
                    mediaType: 'text/event-stream',
                    schema: new OA\Schema(type: 'string')
                )
            ),
        ]
    )]
    public function stream(): StreamedResponse
    {
        return response()->stream(function () {
            // Required: detect client disconnect inside the loop
            ignore_user_abort(true);

            // Disable output buffering
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $lastId = WhatsappLog::latest('id')->value('id') ?? 0;

            // Initial heartbeat
            echo ": connected\n\n";
            flush();

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $newLogs = WhatsappLog::where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit(10)
                    ->get();

                foreach ($newLogs as $log) {
                    echo 'data: ' . json_encode($log) . "\n\n";
                    $lastId = $log->id;
                }

                flush();
                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }

    #[OA\Get(
        path: '/api/logs/export',
        operationId: 'logExport',
        summary: 'Export log percakapan ke CSV',
        description: 'Mendownload log percakapan sebagai file CSV. Filter berdasarkan mode, pencarian, dan rentang tanggal. Data distream dalam chunks 500 baris.',
        tags: ['Log'],
        parameters: [
            new OA\Parameter(name: 'mode', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['all', 'ai', 'faq', 'end_chat', 'error', 'human_takeover'])),
            new OA\Parameter(name: 'search', in: 'query', required: false,
                schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File CSV log percakapan',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
        ]
    )]
    public function export(Request $request): StreamedResponse
    {
        $query = WhatsappLog::query();

        if ($request->mode && $request->mode !== 'all') {
            $query->where('mode', $request->mode);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('from_number',  'like', "%{$s}%")
                  ->orWhere('message_in', 'like', "%{$s}%");
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'log-percakapan-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Nomor WA', 'Nama Kontak', 'Pesan Masuk', 'Respons Bot', 'Mode', 'Tokens', 'Waktu']);

            $query->latest()->chunk(500, function ($logs) use ($out) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->from_number,
                        $log->contact_name ?? '',
                        $log->message_in,
                        $log->message_out  ?? '',
                        $log->mode,
                        $log->ai_tokens_used ?? 0,
                        $log->created_at,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
