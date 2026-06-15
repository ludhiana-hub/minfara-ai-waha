<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunAnalyticsJob;
use App\Models\ConversationAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Analytics', description: 'Customer behavior analytics dari percakapan WhatsApp')]
class AnalyticsController extends Controller
{
    // ── Summary ───────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/analytics/summary',
        operationId: 'analyticsSummary',
        summary: 'Ringkasan metrik analitik customer',
        description: 'Mengembalikan metrik agregat: total sesi, sentiment, purchase intent, bot effectiveness, top topik, dan distribusi segment customer untuk periode tertentu.',
        tags: ['Analytics'],
        parameters: [
            new OA\Parameter(name: 'period', in: 'query', required: false,
                description: 'Jumlah hari ke belakang (maks 90, default 7)',
                schema: new OA\Schema(type: 'integer', default: 7, maximum: 90)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan analitik berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'period', type: 'integer', example: 7),
                    new OA\Property(property: 'from', type: 'string', format: 'date', example: '2026-06-08'),
                    new OA\Property(property: 'to', type: 'string', format: 'date', example: '2026-06-15'),
                    new OA\Property(property: 'total_sessions', type: 'integer', example: 142),
                    new OA\Property(property: 'unique_customers', type: 'integer', example: 89),
                    new OA\Property(property: 'effectiveness', type: 'number', example: 78.5,
                        description: 'Persentase sesi yang resolved sempurna oleh bot'),
                    new OA\Property(property: 'avg_intent_score', type: 'number', example: 5.8,
                        description: 'Rata-rata purchase intent score (0-10)'),
                    new OA\Property(property: 'sentiment', type: 'object', properties: [
                        new OA\Property(property: 'positive', type: 'integer', example: 80),
                        new OA\Property(property: 'neutral', type: 'integer', example: 45),
                        new OA\Property(property: 'negative', type: 'integer', example: 17),
                    ]),
                    new OA\Property(property: 'segments', type: 'object',
                        description: 'Jumlah per segment customer',
                        example: ['hot_lead' => 23, 'window_shopper' => 61, 'objector' => 15]),
                    new OA\Property(property: 'top_topics', type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'topic', type: 'string', example: 'Harga kursus bahasa Jerman'),
                            new OA\Property(property: 'count', type: 'integer', example: 34),
                        ])
                    ),
                ])
            ),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        $period = min((int) $request->input('period', 7), 90);
        $from   = Carbon::today()->subDays($period - 1)->toDateString();
        $to     = Carbon::today()->toDateString();

        $analyses = ConversationAnalysis::forPeriod($from, $to)->get();
        $total    = $analyses->count();
        $resolved = $analyses->where('resolved', true)->count();

        return response()->json([
            'period'           => $period,
            'from'             => $from,
            'to'               => $to,
            'total_sessions'   => $total,
            'unique_customers' => $analyses->unique('phone_number')->count(),
            'effectiveness'    => $total > 0 ? round(($resolved / $total) * 100, 1) : 0,
            'avg_intent_score' => round(
                $analyses->whereNotNull('purchase_intent_score')->avg('purchase_intent_score') ?? 0, 1
            ),
            'sentiment' => [
                'positive' => $analyses->where('sentiment', 'positive')->count(),
                'neutral'  => $analyses->where('sentiment', 'neutral')->count(),
                'negative' => $analyses->where('sentiment', 'negative')->count(),
            ],
            'segments'   => $analyses->whereNotNull('customer_segment')
                ->groupBy('customer_segment')
                ->map(fn($g) => $g->count()),
            'top_topics' => $analyses->whereNotNull('topic')
                ->groupBy('topic')
                ->map(fn($g) => ['topic' => $g->first()->topic, 'count' => $g->count()])
                ->sortByDesc('count')
                ->values()
                ->take(10),
        ]);
    }

    // ── Sessions ──────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/analytics/sessions',
        operationId: 'analyticsSessions',
        summary: 'List sesi analisis percakapan customer (paginated)',
        description: 'Daftar hasil analisis AI per sesi percakapan customer, dengan filter berdasarkan tanggal, segment, sentiment, dan flag issue/FAQ gap.',
        tags: ['Analytics'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\Parameter(name: 'to', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-15')),
            new OA\Parameter(name: 'segment', in: 'query', required: false,
                description: 'Filter segment customer',
                schema: new OA\Schema(type: 'string',
                    enum: ['hot_lead', 'window_shopper', 'objector', 'loyalist', 'churner_signal', 'unknown'])),
            new OA\Parameter(name: 'sentiment', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: ['positive', 'neutral', 'negative'])),
            new OA\Parameter(name: 'issues', in: 'query', required: false,
                description: 'Hanya tampilkan sesi dengan issue terdeteksi',
                schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'faq_gaps', in: 'query', required: false,
                description: 'Hanya tampilkan sesi dengan FAQ gap',
                schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list sesi analisis',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'phone_number', type: 'string', example: '628123456789@c.us'),
                            new OA\Property(property: 'contact_name', type: 'string', nullable: true),
                            new OA\Property(property: 'session_date', type: 'string', format: 'date'),
                            new OA\Property(property: 'messages_count', type: 'integer'),
                            new OA\Property(property: 'topic', type: 'string', nullable: true),
                            new OA\Property(property: 'sentiment', type: 'string',
                                enum: ['positive', 'neutral', 'negative']),
                            new OA\Property(property: 'customer_segment', type: 'string'),
                            new OA\Property(property: 'purchase_intent_score', type: 'integer',
                                description: '0–10'),
                            new OA\Property(property: 'issue_detected', type: 'boolean'),
                            new OA\Property(property: 'issue_type', type: 'string', nullable: true),
                            new OA\Property(property: 'is_faq_gap', type: 'boolean'),
                            new OA\Property(property: 'faq_gap_question', type: 'string', nullable: true),
                            new OA\Property(property: 'channel_source', type: 'string', nullable: true),
                            new OA\Property(property: 'keywords', type: 'array',
                                items: new OA\Items(type: 'string')),
                            new OA\Property(property: 'summary', type: 'string', nullable: true),
                            new OA\Property(property: 'resolved', type: 'boolean'),
                            new OA\Property(property: 'analyzed_at', type: 'string', format: 'date-time'),
                        ])
                    ),
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                ])
            ),
        ]
    )]
    public function sessions(Request $request): JsonResponse
    {
        $query = ConversationAnalysis::query()->latest('session_date')->latest('id');

        if ($request->from)    $query->where('session_date', '>=', $request->from);
        if ($request->to)      $query->where('session_date', '<=', $request->to);
        if ($request->segment) $query->where('customer_segment', $request->segment);
        if ($request->sentiment) $query->where('sentiment', $request->sentiment);
        if ($request->boolean('issues'))   $query->where('issue_detected', true);
        if ($request->boolean('faq_gaps')) $query->where('is_faq_gap', true);

        $perPage = min((int) ($request->per_page ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    // ── FAQ Gaps ──────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/analytics/faq-gaps',
        operationId: 'analyticsFaqGaps',
        summary: 'Daftar FAQ gap — pertanyaan yang tidak terjawab bot',
        description: 'Agregasi pertanyaan customer yang tidak bisa dijawab oleh bot, diurutkan berdasarkan frekuensi kemunculan. Berguna untuk tim konten menambahkan FAQ baru.',
        tags: ['Analytics'],
        parameters: [
            new OA\Parameter(name: 'period', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer', default: 7, maximum: 90)),
            new OA\Parameter(name: 'limit', in: 'query', required: false,
                description: 'Jumlah FAQ gap yang dikembalikan (maks 50)',
                schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar FAQ gap berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'period', type: 'integer', example: 7),
                    new OA\Property(property: 'from', type: 'string', format: 'date'),
                    new OA\Property(property: 'to', type: 'string', format: 'date'),
                    new OA\Property(property: 'total', type: 'integer',
                        description: 'Jumlah pertanyaan unik yang tidak terjawab'),
                    new OA\Property(property: 'data', type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'question', type: 'string',
                                example: 'Apakah ada kelas akhir pekan?'),
                            new OA\Property(property: 'count', type: 'integer', example: 8,
                                description: 'Berapa kali pertanyaan ini muncul dalam periode'),
                        ])
                    ),
                ])
            ),
        ]
    )]
    public function faqGaps(Request $request): JsonResponse
    {
        $period = min((int) $request->input('period', 7), 90);
        $limit  = min((int) $request->input('limit', 10), 50);
        $from   = Carbon::today()->subDays($period - 1)->toDateString();
        $to     = Carbon::today()->toDateString();

        $gaps = ConversationAnalysis::forPeriod($from, $to)
            ->where('is_faq_gap', true)
            ->whereNotNull('faq_gap_question')
            ->get()
            ->groupBy('faq_gap_question')
            ->map(fn($g) => ['question' => $g->first()->faq_gap_question, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values()
            ->take($limit);

        return response()->json([
            'period' => $period,
            'from'   => $from,
            'to'     => $to,
            'total'  => $gaps->count(),
            'data'   => $gaps,
        ]);
    }

    // ── Run Analytics ─────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/analytics/run',
        operationId: 'analyticsRun',
        summary: 'Trigger analisis percakapan secara manual',
        description: 'Mengantrikan job analisis AI untuk tanggal tertentu. Membutuhkan X-Internal-Key. Job berjalan di background queue — tidak blocking.',
        security: [['InternalApiKey' => []]],
        tags: ['Analytics'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'date', type: 'string', format: 'date',
                    example: '2026-06-14',
                    description: 'Tanggal yang dianalisis (default: kemarin)'),
                new OA\Property(property: 'force', type: 'boolean', default: false,
                    description: 'Analisis ulang meski sudah ada datanya'),
            ])
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Job berhasil diantrekan',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'queued'),
                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                    new OA\Property(property: 'message', type: 'string'),
                ])
            ),
            new OA\Response(response: 401, description: 'X-Internal-Key tidak valid'),
        ]
    )]
    public function run(Request $request): JsonResponse
    {
        $date  = $request->input('date', Carbon::yesterday()->toDateString());
        $force = $request->boolean('force');

        dispatch(new RunAnalyticsJob($date, $force));

        return response()->json([
            'status'  => 'queued',
            'date'    => $date,
            'message' => "Analytics job untuk {$date} telah diantrekan.",
        ], 202);
    }
}
