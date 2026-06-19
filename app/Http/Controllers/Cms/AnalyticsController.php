<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ConversationAnalysis;
use App\Models\WhatsappLog;
use App\Services\AnalyticsRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', '7');
        $from   = Carbon::today()->subDays((int) $period - 1)->toDateString();
        $to     = Carbon::today()->toDateString();

        // Summary cards
        $analyses = ConversationAnalysis::forPeriod($from, $to)->get();

        $totalSessions    = $analyses->count();
        $uniqueCustomers  = $analyses->unique('phone_number')->count();
        $resolved         = $analyses->where('resolved', true)->count();
        $effectiveness    = $totalSessions > 0 ? round(($resolved / $totalSessions) * 100) : 0;
        $avgIntent        = round($analyses->whereNotNull('purchase_intent_score')->avg('purchase_intent_score') ?? 0, 1);

        // Sentiment
        $sentiment = [
            'positive' => $analyses->where('sentiment', 'positive')->count(),
            'neutral'  => $analyses->where('sentiment', 'neutral')->count(),
            'negative' => $analyses->where('sentiment', 'negative')->count(),
        ];

        // Top topics
        $topTopics = $analyses->whereNotNull('topic')
            ->groupBy('topic')
            ->map(fn($g) => ['topic' => $g->first()->topic, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values()
            ->take(8);

        // FAQ gaps
        $faqGaps = $analyses->where('is_faq_gap', true)
            ->whereNotNull('faq_gap_question')
            ->groupBy('faq_gap_question')
            ->map(fn($g) => ['question' => $g->first()->faq_gap_question, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values()
            ->take(8);

        // Customer segments
        $segments = $analyses->whereNotNull('customer_segment')
            ->groupBy('customer_segment')
            ->map(fn($g) => $g->count())
            ->toArray();

        // Channel sources
        $channels = $analyses->whereNotNull('channel_source')
            ->groupBy('channel_source')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->take(6)
            ->toArray();

        // Trend data (daily) — sessions & sentiment per day
        $trendDays    = collect(range(0, (int) $period - 1))
            ->map(fn($i) => Carbon::today()->subDays($i)->toDateString())
            ->reverse()->values();

        // session_date is cast to Carbon — compare via toDateString() for correct string match
        $trendSessions  = $trendDays->map(fn($d) => $analyses->filter(fn($a) => $a->session_date->toDateString() === $d)->count());
        $trendPositive  = $trendDays->map(fn($d) => $analyses->filter(fn($a) => $a->session_date->toDateString() === $d && $a->sentiment === 'positive')->count());
        $trendNegative  = $trendDays->map(fn($d) => $analyses->filter(fn($a) => $a->session_date->toDateString() === $d && $a->sentiment === 'negative')->count());
        $trendIntent    = $trendDays->map(fn($d) => round(
            $analyses->filter(fn($a) => $a->session_date->toDateString() === $d && $a->purchase_intent_score !== null)
                     ->avg('purchase_intent_score') ?? 0, 1
        ));

        // Recent analyses (last 20)
        $recent = ConversationAnalysis::forPeriod($from, $to)
            ->latest('session_date')
            ->take(20)
            ->get();

        // Unanalysed count (today) — hanya percakapan yang BELUM dianalisis.
        // Setelah klik "Analisis Sekarang", nomor yang sudah masuk ConversationAnalysis
        // hari ini dikecualikan → angka turun ke 0 sampai ada percakapan baru.
        $analyzedToday = ConversationAnalysis::forDate($to)->pluck('phone_number');
        $unanalysed = WhatsappLog::whereDate('created_at', today())
            ->whereNotIn('from_number', $analyzedToday)
            ->distinct()
            ->count('from_number');

        return view('cms.analytics.dashboard', compact(
            'period', 'from', 'to',
            'totalSessions', 'uniqueCustomers', 'effectiveness', 'avgIntent',
            'sentiment', 'topTopics', 'faqGaps', 'segments', 'channels',
            'trendDays', 'trendSessions', 'trendPositive', 'trendNegative', 'trendIntent',
            'recent', 'unanalysed'
        ));
    }

    public function run(Request $request, AnalyticsRunner $runner): JsonResponse
    {
        $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        $date = $request->input('date') ?: Carbon::today()->toDateString();

        $r = $runner->runForDate($date, force: true);

        if ($r['empty']) {
            return response()->json([
                'status'   => 'empty',
                'analyzed' => 0,
                'message'  => "Belum ada percakapan pada {$date} untuk dianalisis.",
            ]);
        }

        $allFailed = $r['failed'] === $r['analyzed'];

        return response()->json([
            'status'   => $allFailed ? 'ai_failed' : 'done',
            'analyzed' => $r['analyzed'],
            'failed'   => $r['failed'],
            'message'  => $allFailed
                ? "Analisis gagal — AI (NVIDIA) tidak merespons. Cek API key NVIDIA."
                : "Berhasil menganalisis {$r['analyzed']} sesi" . ($r['failed'] ? " ({$r['failed']} gagal)" : "") . ".",
        ]);
    }
}
