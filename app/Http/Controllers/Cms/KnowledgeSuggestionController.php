<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Jobs\BuildFaqDigestJob;
use App\Jobs\RebuildKnowledgeIndexJob;
use App\Models\BotConfig;
use App\Models\KnowledgeSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KnowledgeSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = KnowledgeSuggestion::query();

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $suggestions  = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $pendingCount = KnowledgeSuggestion::where('status', 'pending')->count();

        return view('cms.knowledge-suggestions.index', compact('suggestions', 'pendingCount'));
    }

    public function approve(Request $request, KnowledgeSuggestion $knowledgeSuggestion)
    {
        $updates = ['status' => 'approved'];
        if ($request->filled('question')) {
            $updates['question'] = mb_substr(trim((string) $request->input('question')), 0, 500);
        }
        if ($request->filled('answer')) {
            $updates['answer'] = mb_substr(trim((string) $request->input('answer')), 0, 500);
        }

        $knowledgeSuggestion->update($updates);

        if ($knowledgeSuggestion->type === 'coaching') {
            $this->rebuildSalesCoachingNotes();
            $message = 'Coaching disetujui dan ditambahkan ke catatan gaya & teknik sales bot.';
        } else {
            $this->rebuildDynamicKnowledge();
            RebuildKnowledgeIndexJob::dispatch();
            $message = 'Pengetahuan disetujui dan ditambahkan ke knowledge base.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function reject(KnowledgeSuggestion $knowledgeSuggestion)
    {
        $knowledgeSuggestion->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Saran ditolak.']);
    }

    private function rebuildDynamicKnowledge(): void
    {
        $knowledge = KnowledgeSuggestion::where('status', 'approved')
            ->where('type', 'knowledge')
            ->orderByDesc('id')
            ->get(['question', 'answer'])
            ->map(fn($s) => 'Q: ' . $s->question . ' → ' . $s->answer)
            ->implode("\n");

        BotConfig::updateOrCreate(
            ['key' => 'dynamic_knowledge'],
            ['key' => 'dynamic_knowledge', 'value' => mb_substr($knowledge, 0, 2000), 'type' => 'textarea', 'label' => 'Dynamic Knowledge (Auto-generated)', 'group' => 'ai']
        );

        Cache::forget('bot_config_dynamic_knowledge');

        BuildFaqDigestJob::dispatch();
    }

    private function rebuildSalesCoachingNotes(): void
    {
        $notes = KnowledgeSuggestion::where('status', 'approved')
            ->where('type', 'coaching')
            ->orderByDesc('id')
            ->get(['question', 'answer'])
            ->map(fn($s) => '- ' . $s->question . ': ' . $s->answer)
            ->implode("\n");

        BotConfig::updateOrCreate(
            ['key' => 'sales_coaching_notes'],
            ['key' => 'sales_coaching_notes', 'value' => mb_substr($notes, 0, 2500), 'type' => 'textarea', 'label' => 'Catatan Coaching Sales (Auto-generated)', 'group' => 'ai']
        );

        Cache::forget('bot_config_sales_coaching_notes');

        BuildFaqDigestJob::dispatch();
    }
}
