<?php

namespace App\Services;

use App\Models\AnalyticsDailySummary;
use App\Models\BotConfig;
use App\Models\ConversationAnalysis;
use App\Models\WhatsappLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ConversationAnalysisService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Kamu adalah seorang analis level dunia yang sangat professional dalam menganalisis perilaku customer untuk bisnis pendidikan online Indonesia.
Tugasmu menganalisis percakapan WhatsApp antara customer dengan AI bot.

PENTING: Kembalikan HANYA JSON valid, tanpa teks lain, tanpa markdown code block (```).

Definisi segment:
- hot_lead: langsung tanya harga, cara daftar, jadwal mulai, niat beli jelas
- window_shopper: banyak tanya tapi tidak ada intent beli yang jelas
- objector: ada penolakan/keberatan ("mahal", "belum siap", "nanti dulu")
- loyalist: sudah pernah beli, tanya produk lain atau fitur lanjutan
- churner_signal: kecewa, tanya refund, cancel, atau tidak responsif
- unknown: tidak cukup informasi
PROMPT;

    public function analyseSession(Collection $logs): ?array
    {
        $transcript = $this->buildTranscript($logs);

        $prompt = <<<PROMPT
Analisis percakapan WhatsApp berikut:

[PERCAKAPAN]
{$transcript}
[/PERCAKAPAN]

Kembalikan JSON dengan format persis ini:
{
  "topic": "topik utama percakapan (max 60 karakter, bahasa Indonesia)",
  "sentiment": "positive|neutral|negative",
  "issue_detected": true|false,
  "issue_type": "jenis masalah jika ada, atau null",
  "is_faq_gap": true|false,
  "faq_gap_question": "pertanyaan customer yang tidak terjawab oleh bot, atau null",
  "purchase_intent_score": 0,
  "customer_segment": "hot_lead|window_shopper|objector|loyalist|churner_signal|unknown",
  "channel_source": "channel yang customer sebutkan (TikTok/Instagram/Referral/Google/dll) atau null",
  "keywords": ["keyword1", "keyword2", "keyword3"],
  "summary": "ringkasan percakapan dalam 1 kalimat bahasa Indonesia",
  "resolved": true|false
}

purchase_intent_score: angka 0-10 (0=tidak ada niat beli, 10=hampir pasti beli)
PROMPT;

        // Lazy-resolve here (NOT in constructor) to avoid DB queries during Laravel boot.
        // Analytics uses NVIDIA NIM only — fallback between models, never to other providers.
        $nvidia = app(NvidiaService::class);

        // Primary model is configurable from CMS (Konfigurasi → NVIDIA).
        // Fallback chain is fixed: tried in order if primary fails.
        $primary = BotConfig::get('nvidia_model') ?: 'qwen/qwen3.5-397b-a17b';
        $fallbacks = [
            'qwen/qwen3.5-122b-a10b',
            'nvidia/llama-3.3-nemotron-super-49b-v1.5',
            'deepseek-ai/deepseek-v4-flash',
        ];
        // Exclude primary from fallback list to avoid trying the same model twice
        $models = array_values(array_merge(
            [$primary],
            array_filter($fallbacks, fn ($m) => $m !== $primary)
        ));

        foreach ($models as $model) {
            $result = $nvidia->withModel($model)->chat($prompt, self::SYSTEM_PROMPT, 600, 0.3);

            if (!$result['success']) {
                Log::warning('[Analytics] NVIDIA model gagal, coba berikutnya', [
                    'model' => $model,
                    'error' => $result['error'] ?? 'unknown',
                ]);
                continue;
            }

            $parsed = $this->parseJson($result['reply']);
            if ($parsed !== null) {
                return $parsed;
            }

            Log::warning('[Analytics] JSON parse gagal', ['model' => $model]);
        }

        Log::warning('[Analytics] Semua model NVIDIA gagal untuk sesi', [
            'messages' => $logs->count(),
        ]);

        return null;
    }

    private function buildTranscript(Collection $logs): string
    {
        return $logs->map(function (WhatsappLog $log) {
            $lines = [];
            if ($log->message_in) {
                $lines[] = "Customer: {$log->message_in}";
            }
            if ($log->message_out) {
                $lines[] = "Bot: {$log->message_out}";
            }
            return implode("\n", $lines);
        })->implode("\n\n");
    }

    private function parseJson(string $raw): ?array
    {
        // Strip markdown code blocks if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        // Extract first {...} block
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $clean = $m[0];
        }

        $data = json_decode($clean, true);
        if (!is_array($data)) {
            Log::warning('[Analytics] JSON parse gagal', ['raw' => substr($raw, 0, 300)]);
            return null;
        }

        return [
            'topic'                 => substr($data['topic'] ?? 'Tidak teridentifikasi', 0, 200),
            'sentiment'             => in_array($data['sentiment'] ?? '', ['positive', 'neutral', 'negative'])
                                        ? $data['sentiment'] : 'neutral',
            'issue_detected'        => (bool) ($data['issue_detected'] ?? false),
            'issue_type'            => $data['issue_type'] ? substr($data['issue_type'], 0, 200) : null,
            'is_faq_gap'            => (bool) ($data['is_faq_gap'] ?? false),
            'faq_gap_question'      => $data['faq_gap_question'] ?? null,
            'purchase_intent_score' => max(0, min(10, (int) ($data['purchase_intent_score'] ?? 0))),
            'customer_segment'      => in_array($data['customer_segment'] ?? '', array_keys(ConversationAnalysis::SEGMENTS))
                                        ? $data['customer_segment'] : 'unknown',
            'channel_source'        => $data['channel_source'] ? substr($data['channel_source'], 0, 100) : null,
            'keywords'              => array_slice((array) ($data['keywords'] ?? []), 0, 10),
            'summary'               => $data['summary'] ?? null,
            'resolved'              => (bool) ($data['resolved'] ?? true),
        ];
    }

    public function generateDailySummary(string $date): AnalyticsDailySummary
    {
        $analyses = ConversationAnalysis::forDate($date)->get();

        $topTopics = $analyses
            ->whereNotNull('topic')
            ->groupBy('topic')
            ->map(fn($g) => ['topic' => $g->first()->topic, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values()
            ->take(10)
            ->toArray();

        $faqGaps = $analyses
            ->where('is_faq_gap', true)
            ->whereNotNull('faq_gap_question')
            ->groupBy('faq_gap_question')
            ->map(fn($g) => ['question' => $g->first()->faq_gap_question, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values()
            ->take(10)
            ->toArray();

        $segments = $analyses
            ->whereNotNull('customer_segment')
            ->groupBy('customer_segment')
            ->map(fn($g) => $g->count())
            ->toArray();

        $channels = $analyses
            ->whereNotNull('channel_source')
            ->groupBy('channel_source')
            ->map(fn($g) => $g->count())
            ->sortDesc()
            ->toArray();

        $resolved  = $analyses->where('resolved', true)->count();
        $total     = $analyses->count();
        $effective = $total > 0 ? round(($resolved / $total) * 100, 2) : 0;

        $avgIntent = $analyses->whereNotNull('purchase_intent_score')->avg('purchase_intent_score') ?? 0;

        return AnalyticsDailySummary::updateOrCreate(
            ['summary_date' => $date],
            [
                'total_sessions'         => $total,
                'unique_customers'       => $analyses->unique('phone_number')->count(),
                'bot_effectiveness_rate' => $effective,
                'sentiment_positive'     => $analyses->where('sentiment', 'positive')->count(),
                'sentiment_neutral'      => $analyses->where('sentiment', 'neutral')->count(),
                'sentiment_negative'     => $analyses->where('sentiment', 'negative')->count(),
                'top_topics'             => $topTopics,
                'faq_gaps'               => $faqGaps,
                'avg_intent_score'       => round($avgIntent, 1),
                'customer_segments'      => $segments,
                'channel_sources'        => $channels,
            ]
        );
    }
}
