<?php

namespace App\Jobs;

use App\Models\BotConfig;
use App\Models\FaqMenu;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BuildFaqDigestJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 5;

    public function handle(): void
    {
        $perItemCap = 300;
        $totalCap   = 4000;
        $lines      = ['=== KNOWLEDGE BASE ==='];
        $totalLen   = 0;

        foreach (FaqMenu::active()
            ->whereNotNull('content')
            ->where('command', '!=', '0')
            ->orderBy('sort_order')
            ->get(['title', 'content']) as $item)
        {
            $text = preg_replace('/Ketik \*[^*]+\*[^\n]*/iu', '', $item->content);
            $text = preg_replace('/[─]+/u', '', $text);
            $text = preg_replace('/\*([^*\n]+)\*/u', '$1', $text);
            $text = preg_replace('/\n+/', ' ', trim($text));
            $text = trim(preg_replace('/\s{2,}/', ' ', $text));

            $snippet = '[' . $item->title . '] ' . mb_substr($text, 0, $perItemCap);

            if ($totalLen + mb_strlen($snippet) > $totalCap) {
                break;
            }

            $lines[]   = $snippet;
            $totalLen += mb_strlen($snippet);
        }

        // Append dynamic knowledge snippets from successful conversations (daily synthesized)
        $dynamic = BotConfig::get('dynamic_knowledge', '');
        if ($dynamic) {
            $lines[] = '';
            $lines[] = '=== PENGETAHUAN TAMBAHAN ===';
            $lines[] = mb_substr($dynamic, 0, 2000);
        }

        $lines[] = '=== END KNOWLEDGE BASE ===';

        // Catatan coaching sales (gaya/teknik) — dipisah dari blok KNOWLEDGE BASE di atas
        // supaya jelas ini instruksi cara penyampaian, bukan fakta produk.
        $coaching = BotConfig::get('sales_coaching_notes', '');
        if ($coaching) {
            $lines[] = '';
            $lines[] = '=== CATATAN GAYA & TEKNIK SALES ===';
            $lines[] = '(Catatan: ini panduan teknik, BUKAN skrip wajib. Tetap ikuti format & batas panjang balasan WA di system prompt utama — jawaban singkat untuk pertanyaan singkat, jangan otomatis dibuat panjang.)';
            $lines[] = mb_substr($coaching, 0, 2500);
            $lines[] = '=== END CATATAN GAYA & TEKNIK SALES ===';
        }

        $digest = implode("\n", $lines);

        BotConfig::updateOrCreate(
            ['key' => 'faq_digest'],
            ['key' => 'faq_digest', 'value' => $digest, 'type' => 'textarea', 'label' => 'FAQ Digest (Auto-generated)', 'group' => 'ai']
        );

        Cache::forget('bot_config_faq_digest');

        Log::info('BuildFaqDigestJob: done', ['chars' => mb_strlen($digest), 'entries' => count($lines) - 2]);
    }
}
