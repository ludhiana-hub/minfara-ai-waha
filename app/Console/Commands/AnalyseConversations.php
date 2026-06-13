<?php

namespace App\Console\Commands;

use App\Models\ConversationAnalysis;
use App\Models\WhatsappLog;
use App\Services\ConversationAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
class AnalyseConversations extends Command
{
    protected $signature = 'analytics:analyse
                            {--date= : Tanggal spesifik (YYYY-MM-DD), default kemarin}
                            {--days=1 : Berapa hari ke belakang yang diproses}
                            {--force : Analisis ulang meski sudah ada}';

    protected $description = 'Analisis percakapan WA dengan AI — klasifikasi topik, sentiment, dan perilaku customer';

    public function __construct(private readonly ConversationAnalysisService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days     = (int) $this->option('days');
        $forceDate = $this->option('date');

        $dates = $forceDate
            ? [Carbon::parse($forceDate)->toDateString()]
            : collect(range(1, $days))
                ->map(fn($d) => Carbon::yesterday()->subDays($d - 1)->toDateString())
                ->toArray();

        foreach ($dates as $date) {
            $this->processDate($date);
        }

        return self::SUCCESS;
    }

    private function processDate(string $date): void
    {
        $this->info("[{$date}] Memulai analisis...");

        // Group logs by phone_number for this date
        $sessions = WhatsappLog::whereDate('created_at', $date)
            ->orderBy('created_at')
            ->get()
            ->groupBy('from_number');

        if ($sessions->isEmpty()) {
            $this->line("[{$date}] Tidak ada percakapan.");
            return;
        }

        $processed = 0;
        $skipped   = 0;

        foreach ($sessions as $phone => $logs) {
            $exists = ConversationAnalysis::where('phone_number', $phone)
                ->where('session_date', $date)
                ->exists();

            if ($exists && !$this->option('force')) {
                $skipped++;
                continue;
            }

            $this->line("  → Menganalisis {$phone} ({$logs->count()} pesan)...");

            $result = $this->service->analyseSession($logs);

            ConversationAnalysis::updateOrCreate(
                ['phone_number' => $phone, 'session_date' => $date],
                array_merge($result ?? $this->fallbackResult(), [
                    'contact_name'  => $logs->first()->contact_name,
                    'messages_count'=> $logs->count(),
                    'analyzed_at'   => now(),
                ])
            );

            $processed++;
            // Rate limit — hindari hammer API
            usleep(500_000); // 0.5 detik antar sesi
        }

        // Generate daily summary
        $summary = $this->service->generateDailySummary($date);

        $this->info("[{$date}] Selesai. Diproses: {$processed}, Skip: {$skipped}. Total sesi: {$summary->total_sessions}.");
    }

    private function fallbackResult(): array
    {
        return [
            'topic'                 => 'Gagal dianalisis',
            'sentiment'             => 'neutral',
            'issue_detected'        => false,
            'issue_type'            => null,
            'is_faq_gap'            => false,
            'faq_gap_question'      => null,
            'purchase_intent_score' => 0,
            'customer_segment'      => 'unknown',
            'channel_source'        => null,
            'keywords'              => [],
            'summary'               => 'Analisis gagal — semua AI provider tidak tersedia.',
            'resolved'              => true,
        ];
    }
}
