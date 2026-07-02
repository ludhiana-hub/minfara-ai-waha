<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Jobs\BuildFaqDigestJob;
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

        $suggestions  = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $pendingCount = KnowledgeSuggestion::where('status', 'pending')->count();

        return view('cms.knowledge-suggestions.index', compact('suggestions', 'pendingCount'));
    }

    public function approve(KnowledgeSuggestion $knowledgeSuggestion)
    {
        $knowledgeSuggestion->update(['status' => 'approved']);
        $this->rebuildDynamicKnowledge();

        return response()->json(['success' => true, 'message' => 'Pengetahuan disetujui dan ditambahkan ke knowledge base.']);
    }

    public function reject(KnowledgeSuggestion $knowledgeSuggestion)
    {
        $knowledgeSuggestion->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Saran pengetahuan ditolak.']);
    }

    private function rebuildDynamicKnowledge(): void
    {
        $knowledge = KnowledgeSuggestion::where('status', 'approved')
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
}
