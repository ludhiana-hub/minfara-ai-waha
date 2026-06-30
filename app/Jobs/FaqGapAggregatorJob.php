<?php

namespace App\Jobs;

use App\Models\ConversationAnalysis;
use App\Models\FaqSuggestion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FaqGapAggregatorJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(private readonly string $date) {}

    public function handle(): void
    {
        $gaps = ConversationAnalysis::where('session_date', $this->date)
            ->where('is_faq_gap', true)
            ->whereNotNull('faq_gap_question')
            ->get(['phone_number', 'faq_gap_question']);

        if ($gaps->isEmpty()) {
            Log::info("FaqGapAggregatorJob [{$this->date}]: no gaps found");
            return;
        }

        // Group similar questions using word overlap (simple Jaccard-like)
        $groups = [];

        foreach ($gaps as $gap) {
            $question = trim($gap->faq_gap_question);
            if (empty($question)) {
                continue;
            }

            $matched = false;
            foreach ($groups as &$group) {
                if ($this->isSimilar($question, $group['canonical'])) {
                    $group['frequency']++;
                    $group['phones'][] = $gap->phone_number;
                    $matched = true;
                    break;
                }
            }
            unset($group);

            if (!$matched) {
                $groups[] = [
                    'canonical' => $question,
                    'frequency' => 1,
                    'phones'    => [$gap->phone_number],
                ];
            }
        }

        // Count occurrences from the past 7 days for high_priority detection
        $weekAgo = now()->subDays(7)->toDateString();

        foreach ($groups as $group) {
            $existing = FaqSuggestion::where('status', 'pending')
                ->get()
                ->first(fn($s) => $this->isSimilar($group['canonical'], $s->question));

            // Count how many times a similar question has appeared in the last 7 days
            $weeklyCount = ConversationAnalysis::whereBetween('session_date', [$weekAgo, $this->date])
                ->where('is_faq_gap', true)
                ->whereNotNull('faq_gap_question')
                ->get('faq_gap_question')
                ->filter(fn($r) => $this->isSimilar($group['canonical'], $r->faq_gap_question))
                ->count();

            $isHighPriority = $weeklyCount >= 3;

            if ($existing) {
                $existing->increment('frequency', $group['frequency']);
                $existing->update([
                    'example_phones' => array_unique(array_merge($existing->example_phones ?? [], $group['phones'])),
                    'high_priority'  => $existing->high_priority || $isHighPriority,
                ]);
            } else {
                FaqSuggestion::create([
                    'question'      => $group['canonical'],
                    'frequency'     => $group['frequency'],
                    'example_phones'=> array_unique($group['phones']),
                    'status'        => 'pending',
                    'high_priority' => $isHighPriority,
                ]);
            }
        }

        Log::info("FaqGapAggregatorJob [{$this->date}]: processed " . count($groups) . " gap groups");
    }

    private function isSimilar(string $a, string $b): bool
    {
        $wordsA = $this->tokenize($a);
        $wordsB = $this->tokenize($b);

        if (empty($wordsA) || empty($wordsB)) {
            return false;
        }

        $intersection = count(array_intersect($wordsA, $wordsB));
        $union        = count(array_unique(array_merge($wordsA, $wordsB)));

        // Jaccard similarity >= 0.4 means "similar enough"
        return $union > 0 && ($intersection / $union) >= 0.4;
    }

    private function tokenize(string $text): array
    {
        $text  = strtolower($text);
        $text  = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $words = array_filter(explode(' ', $text), fn($w) => mb_strlen($w) > 2);

        // Remove common Indonesian stopwords
        $stopwords = ['dan', 'yang', 'ada', 'untuk', 'apa', 'apakah', 'bagaimana', 'berapa', 'kapan', 'dimana', 'kenapa', 'siapa', 'bisa', 'cara', 'jika', 'kalau'];

        return array_values(array_diff($words, $stopwords));
    }
}
